<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyLedgerClosing;
use App\Models\DailyLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyLedgerController extends Controller
{
    /**
     * Only Accounts (the 'accountant' role) may add entries or remarks.
     * Admin can reach every method below too, but strictly read-only —
     * abort_unless() guards every write action.
     */
    private function isAccountant(): bool
    {
        return auth()->user()->isAccountant();
    }

    /* ───────────────────────── Daily view ───────────────────────── */

    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();

        $entries = DailyLedgerEntry::with(['createdBy', 'remarksBy'])
            ->forDate($date)
            ->orderBy('created_at')
            ->get();

        $totals = [
            'in'      => $entries->where('type', 'in')->sum('amount'),
            'out'     => $entries->where('type', 'out')->sum('amount'),
            'expense' => $entries->where('type', 'expense')->sum('amount'),
        ];
        $closingBalance = $totals['in'] - $totals['out'] - $totals['expense'];

        $isOpen  = DailyLedgerEntry::isDateOpenForEntries($date);
        $lockAt  = DailyLedgerEntry::lockMomentFor($date);
        $closing = DailyLedgerClosing::where('entry_date', $date->toDateString())->first();

        return view('daily-ledger.index', compact(
            'date', 'entries', 'totals', 'closingBalance', 'isOpen', 'lockAt', 'closing'
        ));
    }

    /** Accounts adds a new In / Out / Expense entry. It is editable only on the same date. */
    public function store(Request $request)
    {
        abort_unless($this->isAccountant(), 403, 'Only Accounts can add daily ledger entries.');

        $validated = $request->validate([
            'entry_date'  => 'required|date',
            'type'        => 'required|in:in,out,expense',
            'particulars' => 'required|string|max:255',
            'category'    => 'nullable|string|max:100',
            'amount'      => 'required|integer|min:0|max:9999999999',
        ]);

        abort_unless(
            DailyLedgerEntry::isDateOpenForEntries($validated['entry_date']),
            403,
            'This day\'s ledger is locked (12 hours after day-end) and can no longer receive new entries.'
        );

        $entry = DailyLedgerEntry::create($validated + ['created_by' => auth()->id()]);
        $this->refreshClosingTotals($entry->entry_date);

        AuditLog::record(
            'daily_ledger.entry_created',
            DailyLedgerEntry::class,
            $entry->id,
            ucfirst($entry->type) . " entry of Rs. " . number_format($entry->amount, 2) . " on {$entry->entry_date->format('Y-m-d')}: {$entry->particulars}"
        );

        return back()->with('success', 'Entry recorded. You may edit its details until the end of this same date. From the next date onward, only remarks can be added.');
    }

    /**
     * Accounts may correct the financial details only on the entry's own date.
     * Once the calendar date changes, the original entry becomes immutable and
     * any later correction must be documented with a remark.
     */
    public function update(Request $request, DailyLedgerEntry $entry)
    {
        abort_unless($this->isAccountant(), 403, 'Only Accounts can edit daily ledger entries.');
        abort_unless(
            $entry->canEditDetails(),
            403,
            'This entry can no longer be edited because its entry date has passed. Add a remark instead.'
        );

        $validated = $request->validate([
            'type'        => 'required|in:in,out,expense',
            'particulars' => 'required|string|max:255',
            'category'    => 'nullable|string|max:100',
            'amount'      => 'required|integer|min:0|max:9999999999',
        ]);

        $before = [
            'type'        => $entry->type,
            'particulars' => $entry->particulars,
            'category'    => $entry->category,
            'amount'      => $entry->amount,
        ];

        // entry_date and created_by are deliberately not accepted here.
        $entry->update($validated);
        $this->refreshClosingTotals($entry->entry_date);

        AuditLog::record(
            'daily_ledger.entry_updated',
            DailyLedgerEntry::class,
            $entry->id,
            'Same-day correction on entry #' . $entry->id . ': ' . json_encode([
                'before' => $before,
                'after'  => $validated,
            ])
        );

        return back()->with('success', 'Entry updated. After today, its financial details will be locked and only remarks can be added.');
    }

    /**
     * Accounts flags an entry with a remark. Remarks are always available,
     * including after the entry date has passed and detail editing is locked.
     * The original entry is never deleted, preserving the audit trail.
     */
    public function remark(Request $request, DailyLedgerEntry $entry)
    {
        abort_unless($this->isAccountant(), 403, 'Only Accounts can add remarks on daily ledger entries.');

        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        $entry->update([
            'remarks'    => $validated['remarks'],
            'remarks_by' => auth()->id(),
            'remarks_at' => now(),
        ]);

        AuditLog::record('daily_ledger.remark_added', DailyLedgerEntry::class, $entry->id, "Remark added on entry #{$entry->id}: {$validated['remarks']}");

        return back()->with('success', 'Remark saved.');
    }

    /**
     * Keep an already-saved cash closing consistent when a same-day entry is
     * added or corrected. The physical denomination count is preserved; only
     * the calculated expected total and difference are refreshed.
     */
    private function refreshClosingTotals($date): void
    {
        $date = Carbon::parse($date)->toDateString();
        $closing = DailyLedgerClosing::where('entry_date', $date)->first();

        if (!$closing) {
            return;
        }

        $entries = DailyLedgerEntry::forDate($date)->get();
        $expectedTotal = $entries->where('type', 'in')->sum('amount')
            - $entries->where('type', 'out')->sum('amount')
            - $entries->where('type', 'expense')->sum('amount');

        $closing->update([
            'expected_total' => $expectedTotal,
            'difference'     => (float) $closing->counted_total - $expectedTotal,
        ]);
    }

    /* ───────────────────── Currency calculator (closing) ───────────────────── */

    /** Accounts saves the physical cash count (notes/coins x quantity) for the day's closing. */
    public function saveClosing(Request $request)
    {
        abort_unless($this->isAccountant(), 403, 'Only Accounts can record the daily closing count.');

        $validated = $request->validate([
            'entry_date'              => 'required|date',
            'denominations'           => 'required|array',
            'denominations.*'         => 'nullable|integer|min:0',
        ]);

        $date = Carbon::parse($validated['entry_date']);
        $countedTotal = 0;
        foreach ($validated['denominations'] as $note => $qty) {
            $countedTotal += ((int) $note) * ((int) $qty);
        }

        $entries = DailyLedgerEntry::forDate($date)->get();
        $expectedTotal = $entries->where('type', 'in')->sum('amount')
            - $entries->where('type', 'out')->sum('amount')
            - $entries->where('type', 'expense')->sum('amount');

        $closing = DailyLedgerClosing::updateOrCreate(
            ['entry_date' => $date->toDateString()],
            [
                'denominations'  => $validated['denominations'],
                'counted_total'  => $countedTotal,
                'expected_total' => $expectedTotal,
                'difference'     => $countedTotal - $expectedTotal,
                'closed_by'      => auth()->id(),
            ]
        );

        AuditLog::record('daily_ledger.closing_saved', DailyLedgerClosing::class, $closing->id, "Cash closing for {$date->toDateString()}: counted Rs. " . number_format($countedTotal, 2));

        return back()->with('success', 'Closing cash count saved.');
    }

    /* ───────────────────────── Weekly / Yearly summaries ───────────────────────── */

    public function weekly(Request $request)
    {
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : now();
        $from   = $anchor->copy()->startOfWeek();
        $to     = $anchor->copy()->endOfWeek();

        $days = $this->dailyBreakdown($from, $to);
        $totals = [
            'in'      => $days->sum('in'),
            'out'     => $days->sum('out'),
            'expense' => $days->sum('expense'),
        ];

        return view('daily-ledger.weekly', compact('from', 'to', 'days', 'totals'));
    }

    public function yearly(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : (int) now()->year;

        $months = collect(range(1, 12))->map(function ($m) use ($year) {
            $from = Carbon::create($year, $m, 1)->startOfMonth();
            $to   = $from->copy()->endOfMonth();

            $entries = DailyLedgerEntry::betweenDates($from, $to)->get();

            return [
                'label'   => $from->format('F'),
                'in'      => $entries->where('type', 'in')->sum('amount'),
                'out'     => $entries->where('type', 'out')->sum('amount'),
                'expense' => $entries->where('type', 'expense')->sum('amount'),
            ];
        });

        $totals = [
            'in'      => $months->sum('in'),
            'out'     => $months->sum('out'),
            'expense' => $months->sum('expense'),
        ];

        return view('daily-ledger.yearly', compact('year', 'months', 'totals'));
    }

    /** Per-day In/Out/Expense totals between two dates, used by the weekly view and its print report. */
    private function dailyBreakdown(Carbon $from, Carbon $to)
    {
        $entries = DailyLedgerEntry::betweenDates($from->toDateString(), $to->toDateString())->get()->groupBy(fn ($e) => $e->entry_date->toDateString());

        $days = collect();
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dayEntries = $entries->get($d->toDateString(), collect());
            $days->push([
                'date'    => $d->copy(),
                'in'      => $dayEntries->where('type', 'in')->sum('amount'),
                'out'     => $dayEntries->where('type', 'out')->sum('amount'),
                'expense' => $dayEntries->where('type', 'expense')->sum('amount'),
            ]);
        }
        return $days;
    }

    /* ───────────────────────────── Print / PDF export ───────────────────────────── */
    /* Same convention as the rest of Accounts' reports: a print-styled page the
       browser's "Print > Save as PDF" turns into a PDF, rather than a server-side
       PDF library. */

    public function printDaily(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $entries = DailyLedgerEntry::with(['createdBy', 'remarksBy'])->forDate($date)->orderBy('created_at')->get();
        $totals = [
            'in'      => $entries->where('type', 'in')->sum('amount'),
            'out'     => $entries->where('type', 'out')->sum('amount'),
            'expense' => $entries->where('type', 'expense')->sum('amount'),
        ];
        $closingBalance = $totals['in'] - $totals['out'] - $totals['expense'];
        $closing = DailyLedgerClosing::where('entry_date', $date->toDateString())->first();

        return view('daily-ledger.print.daily', compact('date', 'entries', 'totals', 'closingBalance', 'closing'));
    }

    public function printWeekly(Request $request)
    {
        $anchor = $request->filled('date') ? Carbon::parse($request->date) : now();
        $from   = $anchor->copy()->startOfWeek();
        $to     = $anchor->copy()->endOfWeek();
        $days   = $this->dailyBreakdown($from, $to);
        $totals = ['in' => $days->sum('in'), 'out' => $days->sum('out'), 'expense' => $days->sum('expense')];

        return view('daily-ledger.print.weekly', compact('from', 'to', 'days', 'totals'));
    }

    public function printYearly(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : (int) now()->year;
        $months = collect(range(1, 12))->map(function ($m) use ($year) {
            $from = Carbon::create($year, $m, 1)->startOfMonth();
            $to   = $from->copy()->endOfMonth();
            $entries = DailyLedgerEntry::betweenDates($from, $to)->get();
            return [
                'label'   => $from->format('F'),
                'in'      => $entries->where('type', 'in')->sum('amount'),
                'out'     => $entries->where('type', 'out')->sum('amount'),
                'expense' => $entries->where('type', 'expense')->sum('amount'),
            ];
        });
        $totals = ['in' => $months->sum('in'), 'out' => $months->sum('out'), 'expense' => $months->sum('expense')];

        return view('daily-ledger.print.yearly', compact('year', 'months', 'totals'));
    }
}
