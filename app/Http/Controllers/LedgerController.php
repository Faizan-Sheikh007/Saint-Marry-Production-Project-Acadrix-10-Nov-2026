<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Voucher;
use App\Models\Payment;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /** How many days ahead counts as "upcoming / due soon" across the ledger module */
    const UPCOMING_WINDOW_DAYS = 7;

    /** Student picker / ledger overview */
    public function index(Request $request)
    {
        $query = Student::query()->withSum(['vouchers as total_charged' => fn ($q) => $q->whereNot('status', 'cancelled')], 'amount')
                                  ->withSum('vouchers as total_paid', 'paid_amount')
                                  ->with(['vouchers' => fn ($q) => $q->orderByDesc('due_date')->with(['payments' => fn ($p) => $p->orderByDesc('paid_at')])]);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")->orWhere('student_id', 'like', "%{$request->search}%")->orWhere('ledger_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->validate(['status'=>'in:active,inactive,suspended,graduated,dropped,transferred,terminated,left'])['status']);
        if ($request->filled('program_id')) $query->where('program_id', $request->validate(['program_id'=>'integer|exists:programs,id'])['program_id']);
        $students = $query->orderBy('name')->paginate(20)->withQueryString();

        // Institution-wide at-a-glance counters shown on the picker page
        $overdueCount  = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        $dueSoonCount  = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(self::UPCOMING_WINDOW_DAYS)->toDateString()])
            ->count();

        return view('ledger.index', compact('students', 'overdueCount', 'dueSoonCount'));
    }

    /** Student-wise ledger: all dues, payments, running balance, grouped by month with due-date status */
    public function student(Student $student)
    {
        $vouchers = $student->vouchers()->with('payments')->orderBy('due_date')->get();

        $entries = collect();
        foreach ($vouchers as $voucher) {
            $entries->push([
                'date' => $voucher->issued_at ?? $voucher->created_at,
                'type' => 'charge',
                'description' => $voucher->title . " ({$voucher->voucher_no})",
                'debit' => $voucher->total(),
                'credit' => 0,
                'due_date' => $voucher->due_date,
                'status' => $voucher->status,
                'month_key' => $voucher->due_date ? $voucher->due_date->format('Y-m') : 'undated',
                'month_label' => $voucher->month ?: ($voucher->due_date ? $voucher->due_date->format('F Y') : 'Undated'),
            ]);
            foreach ($voucher->payments as $payment) {
                $entries->push([
                    'date' => $payment->paid_at,
                    'type' => 'payment',
                    'description' => "Payment - {$voucher->voucher_no} (" . ucfirst(str_replace('_', ' ', $payment->method)) . ")",
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'due_date' => null,
                    'status' => null,
                    'month_key' => $payment->paid_at ? $payment->paid_at->format('Y-m') : 'undated',
                    'month_label' => $payment->paid_at ? $payment->paid_at->format('F Y') : 'Undated',
                ]);
            }
        }
        $entries = $entries->sortBy('date')->values();

        $balance = 0;
        $entries = $entries->map(function ($e) use (&$balance) {
            $balance += $e['debit'] - $e['credit'];
            $e['balance'] = $balance;
            return $e;
        });

        // Monthly-based breakdown (charged / paid / balance per calendar month, based on voucher due date)
        $monthlySummary = collect();
        foreach ($vouchers as $voucher) {
            $key   = $voucher->due_date ? $voucher->due_date->format('Y-m') : 'undated';
            $label = $voucher->month ?: ($voucher->due_date ? $voucher->due_date->format('F Y') : 'Undated');

            if (!$monthlySummary->has($key)) {
                $monthlySummary->put($key, [
                    'label' => $label,
                    'charged' => 0,
                    'paid' => 0,
                    'due_date' => $voucher->due_date,
                    'overdue' => false,
                    'due_soon' => false,
                ]);
            }
            $row = $monthlySummary->get($key);
            $row['charged'] += $voucher->total();
            $row['paid']    += (float) $voucher->paid_amount;
            if ($voucher->isOverdue()) {
                $row['overdue'] = true;
            } elseif (in_array($voucher->status, ['unpaid', 'partial']) && $voucher->due_date
                      && $voucher->due_date->between(now(), now()->addDays(self::UPCOMING_WINDOW_DAYS))) {
                $row['due_soon'] = true;
            }
            $monthlySummary->put($key, $row);
        }
        $monthlySummary = $monthlySummary->sortKeys();

        $totalCharged = $vouchers->sum(fn ($v) => $v->total());
        $totalPaid    = $vouchers->sum('paid_amount');

        // This student's own overdue / due-soon vouchers, for the alert banners at the top of the page
        $overdueVouchers = $vouchers->filter(fn ($v) => $v->isOverdue())->values();
        $dueSoonVouchers = $vouchers->filter(function ($v) {
            return in_array($v->status, ['unpaid', 'partial']) && $v->due_date
                   && $v->due_date->between(now(), now()->addDays(self::UPCOMING_WINDOW_DAYS));
        })->values();

        return view('ledger.student', compact(
            'student', 'entries', 'totalCharged', 'totalPaid', 'balance',
            'monthlySummary', 'overdueVouchers', 'dueSoonVouchers'
        ));
    }

    /** General ledger: institution-wide income vs receivables, broken down by month, plus due-date watchlist */
    public function general(Request $request)
    {
        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->to ? \Carbon\Carbon::parse($request->to) : now()->endOfMonth();

        $payments = \App\Services\CashReceiptLedger::entries($from->startOfDay(), $to->endOfDay());
        $totalIncome = $payments->sum('amount');
        $receivables = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->get()->sum(fn ($v) => $v->balance());

        $byMethod = $payments->groupBy('method')->map->sum('amount');

        // Monthly-based breakdown: income collected per month (by payment date) vs charges billed per month (by voucher due date)
        $incomeByMonth = $payments->groupBy(fn ($p) => $p->paid_at->format('Y-m'))
            ->map(function ($group) {
                return [
                    'label' => $group->first()->paid_at->format('F Y'),
                    'income' => $group->sum('amount'),
                ];
            })->sortKeys();

        $chargeVouchers = Voucher::whereNot('status', 'cancelled')->whereBetween('due_date', [$from->copy()->subMonths(5)->startOfMonth(), $to])->get();
        $billingByMonth = collect();
        foreach ($chargeVouchers as $v) {
            $key = $v->due_date ? $v->due_date->format('Y-m') : 'undated';
            $label = $v->due_date ? $v->due_date->format('F Y') : 'Undated';
            if (!$billingByMonth->has($key)) {
                $billingByMonth->put($key, ['label' => $label, 'billed' => 0, 'collected' => 0, 'outstanding' => 0]);
            }
            $row = $billingByMonth->get($key);
            $row['billed']      += $v->total();
            $row['collected']   += (float) $v->paid_amount;
            $row['outstanding'] += $v->balance();
            $billingByMonth->put($key, $row);
        }
        $billingByMonth = $billingByMonth->sortKeys();

        // Institution-wide due-date watchlist: overdue and due-soon vouchers
        $overdueVouchers = Voucher::with('student')
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();

        $dueSoonVouchers = Voucher::with('student')
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(self::UPCOMING_WINDOW_DAYS)->toDateString()])
            ->orderBy('due_date')
            ->get();

        return view('ledger.general', compact(
            'payments', 'totalIncome', 'receivables', 'byMethod', 'from', 'to',
            'incomeByMonth', 'billingByMonth', 'overdueVouchers', 'dueSoonVouchers'
        ));
    }
}
