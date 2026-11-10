{{--
    Shared "Student Fee without Heads" summary widget.
    Expects a $feeSummary array (see App\Models\Student::feeSummary()).

    Optional:
      $feeRouteName          'student.voucher' | 'parent.voucher' | null (read-only download link)
      $showAccountantActions bool  — true only on the Accounts (accountant) student page.
                                      Renders real actions: view/print + receive payment + cancel.
      $printRouteName        route name for a read-only "view / print voucher" link
                              (e.g. 'vouchers.print'). Safe to pass alongside $showAccountantActions
                              or on its own for a plain print link.
      $flattenFutureEstimates bool — when true, every "estimated" (not-yet-running)
                              upcoming semester shows the SAME flat total as the
                              current/running semester instead of that semester's
                              own (possibly annually-incremented) voucher total.
                              Gives parents a simple, non-alarming estimate rather
                              than one that jumps around with the plan's yearly
                              increase. Off by default everywhere except where
                              explicitly requested.
      $showInstallmentLabel  bool — when true, each grouped row's item text is
                              prefixed with its "Semester X | Nth Installment"
                              label, so the list reads by installment rather than
                              by raw fee-head names.
--}}
@php
    $feeRouteName            = $feeRouteName ?? null;
    $showAccountantActions   = $showAccountantActions ?? false;
    $printRouteName          = $printRouteName ?? null;
    $flattenFutureEstimates  = $flattenFutureEstimates ?? false;
    $showInstallmentLabel    = $showInstallmentLabel ?? false;

    // Every voucher across all four buckets, for the "By Semester" view —
    // relies on Student::feeSummary() having eager-loaded feeAssignment.
    $fsAll = collect($feeSummary['previous_balance'] ?? [])
        ->concat($feeSummary['active'] ?? [])
        ->concat($feeSummary['upcoming'] ?? [])
        ->concat($feeSummary['previous_paid'] ?? []);

    $fsLabel = function ($v) {
        return $v->feeAssignment
            ? ($v->cycle_no !== null ? $v->feeAssignment->termLabelFor((int) $v->cycle_no) : $v->feeAssignment->cycleRangeLabel())
            : 'Other / One-off Vouchers';
    };
    $fsSortKey = function ($v) {
        return $v->cycle_no ?? 9999;
    };
    $fsBadgeClass = function ($label) {
        return match (strtolower($label)) {
            'paid'                => 'success',
            'overdue', 'due'      => 'danger',
            'cancelled'           => 'gray',
            default               => 'warning', // active / upcoming
        };
    };

    $fsGrouped = $fsAll->sortBy($fsSortKey)->groupBy($fsLabel);
    $fsHasData = $fsAll->isNotEmpty();

    // Collapses several Voucher rows that share one voucher_no (i.e. one
    // installment split across several fee heads — Admission, Tuition,
    // Examination, etc.) into a single combined row: one line per
    // installment instead of one line per fee head. Used everywhere in this
    // partial except the accountant view, which still needs one row per fee
    // head so it can pay/cancel each individually.
    $fsGroupByInstallment = function ($vouchers, string $amountField = 'balance') use ($showInstallmentLabel) {
        return collect($vouchers)->groupBy(fn ($v) => $v->voucher_no !== null && (string)$v->voucher_no !== '' ? (string)$v->voucher_no : 'row:'.$v->id)
            ->map(function ($lines) use ($amountField, $showInstallmentLabel) {
                $first = $lines->first();
                $totalDue = round((float)$lines->sum(fn ($v) => $v->balance()), 2);
                $totalCharged = round((float)$lines->sum(fn ($v) => max(0, $v->total())), 2);
                // Use applied voucher amounts, not individual payment-claim rows.
                // Clamp legacy overallocations to the charge, as in StudentFeeLedger.
                $totalPaid = round((float)$lines->sum(fn ($v) => min(max(0, (float)$v->total()), max(0, (float)$v->paid_amount))), 2);
                $fullyCancelled = $lines->every(fn ($v) => $v->status === 'cancelled');
                $due = $lines->filter(fn ($v) => $v->balance() > 0)->pluck('due_date')->filter()->sort()->first()
                    ?? $lines->pluck('due_date')->filter()->sort()->first();
                $today = now()->startOfDay();
                $dueStatus = !$due ? 'Upcoming' : ($due->lt($today) ? 'Overdue'
                    : ($due->isSameDay($today) ? 'Due Today'
                    : ($due->lte($today->copy()->addDays(7)) ? 'Due Soon' : 'Upcoming')));
                $paymentState = $fullyCancelled ? 'Cancelled'
                    : ($totalDue <= 0.005 ? 'Paid'
                    : ($totalPaid > 0 ? 'Partially Paid' : 'Unpaid'));

                $items = $lines->pluck('title')->filter()->unique()->implode(', ');
                if ($showInstallmentLabel && $first->feeAssignment) {
                    $instLabel = $first->feeAssignment->installmentLabelFor((int)$first->cycle_no, $first->installment_no);
                    $items = $instLabel ? ($items ? "{$instLabel} — {$items}" : $instLabel) : $items;
                }
                return (object) [
                    'voucher_no' => $first->voucher_no ?? '—',
                    'items' => $items,
                    'amount' => $amountField === 'paid_amount' ? $totalPaid : $totalDue,
                    'due_date' => $due,
                    'status' => $paymentState === 'Partially Paid' ? 'Partially Paid'
                        : ($paymentState === 'Unpaid' ? $dueStatus : $paymentState),
                    'payment_state' => $paymentState,
                    'schedule' => $totalDue > 0.005 ? $dueStatus : null,
                    'paid_amount' => $totalPaid,
                    'voucher_amount' => $totalCharged,
                    'balance_amount' => $totalDue,
                    'representative' => $first,
                ];
            })->sortBy('due_date')->values();
    };
@endphp

@if($fsHasData)
<div class="fs-tabs">
  <button type="button" class="fs-tab active" data-fs-tab="combined"><i class="fas fa-list"></i> All Vouchers</button>
  <button type="button" class="fs-tab" data-fs-tab="semester"><i class="fas fa-layer-group"></i> By Semester</button>
</div>
@endif

<div class="fs-panel active" data-fs-panel="combined">

@if(($feeSummary['previous_balance_total'] ?? 0) > 0)
<div class="card fs-card fs-card-danger">
  <div class="card-header">
    <div class="card-title" style="color:var(--danger)">
      <i class="fas fa-triangle-exclamation"></i> Previous Balance — Rs.{{ number_format($feeSummary['previous_balance_total']) }}
    </div>
  </div>
  <div style="padding:0 16px 16px;font-size:13px;color:var(--text-muted)">
    Outstanding amount carried forward from earlier due dates. Please clear this balance as soon as possible.
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Voucher #</th><th>Title</th><th style="text-align:right">Balance</th><th>Due Date</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th></tr></thead>
      <tbody>
        @foreach($fsGroupByInstallment($feeSummary['previous_balance']) as $row)
        <tr>
          <td style="font-weight:600;font-size:13px">{{ $row->voucher_no }}</td>
          <td style="font-size:12px;color:var(--text-muted)">{{ $row->items }}</td>
          <td style="text-align:right;color:var(--danger);font-weight:700">Rs.{{ number_format($row->amount) }}</td>
          <td>{{ optional($row->due_date)->format('d M Y') }}</td>
          <td style="text-align:center"><span class="badge {{ $fsBadgeClass($row->status) }} sm">{{ $row->status }}</span>
              @if($row->payment_state === 'Partially Paid' && $row->schedule)
                <span class="badge {{ $row->schedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $row->schedule }}</span>
              @endif</td>
          <td style="text-align:center">@include('partials.fee-summary-actions', ['voucher' => $row->representative])</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@if(($feeSummary['active'] ?? collect())->isNotEmpty())
<div class="card fs-card fs-card-warning">
  <div class="card-header">
    <div class="card-title" style="color:var(--warning)"><i class="fas fa-bell"></i> Active Fee — Due Now</div>
  </div>
  @php
    // A quick look-ahead: the very next installment after the active one(s)
    // above, so accounts can see what's coming without switching to
    // "Upcoming Fees" / "By Semester". Shown as one extra row, visually
    // marked "Next" so it isn't mistaken for something due right now.
    $fsNextUp = $fsGroupByInstallment($feeSummary['upcoming'] ?? collect())->sortBy('due_date')->first();
  @endphp
  <div class="table-wrap">
    <table>
      <thead><tr><th>Voucher #</th><th>Title</th><th style="text-align:right">Amount Due</th><th>Due Date</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th></tr></thead>
      <tbody>
        @foreach($fsGroupByInstallment($feeSummary['active']) as $row)
        <tr>
          <td style="font-weight:600;font-size:13px">{{ $row->voucher_no }}</td>
          <td style="font-size:12px;color:var(--text-muted)">{{ $row->items }}</td>
          <td style="text-align:right;color:var(--warning);font-weight:700">Rs.{{ number_format($row->amount) }}</td>
          <td>{{ optional($row->due_date)->format('d M Y') }}</td>
          <td style="text-align:center"><span class="badge {{ $fsBadgeClass($row->status) }} sm">{{ $row->status }}</span>
              @if($row->payment_state === 'Partially Paid' && $row->schedule)
                <span class="badge {{ $row->schedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $row->schedule }}</span>
              @endif</td>
          <td style="text-align:center">@include('partials.fee-summary-actions', ['voucher' => $row->representative])</td>
        </tr>
        @endforeach
        @if($fsNextUp)
        <tr class="fs-next-row">
          <td style="font-weight:600;font-size:13px">{{ $fsNextUp->voucher_no }}</td>
          <td style="font-size:12px;color:var(--text-muted)">{{ $fsNextUp->items }}</td>
          <td style="text-align:right;font-weight:700">Rs.{{ number_format($fsNextUp->amount) }}</td>
          <td>{{ optional($fsNextUp->due_date)->format('d M Y') }}</td>
          <td style="text-align:center"><span class="badge gray sm">Next Up</span></td>
          <td style="text-align:center">@include('partials.fee-summary-actions', ['voucher' => $fsNextUp->representative])</td>
        </tr>
        @endif
      </tbody>
    </table>
  </div>
</div>
@endif

@php
    $fsUpcoming = collect($feeSummary['upcoming'] ?? []);
    $fsUpcomingGrouped = $fsUpcoming->sortBy($fsSortKey)->groupBy($fsLabel);
@endphp

<div class="card fs-card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-calendar-days" style="color:var(--primary)"></i> Upcoming Fees</div>
  </div>

  @php
    // Only the nearest semester/year (the one actually running / coming up
    // next) gets a full voucher-by-voucher breakdown here — a 9-semester
    // pre-generated plan would otherwise dump 30+ line items on a parent at
    // once. Everything further out is shown as a single estimated total;
    // its real, itemized breakdown is still available any time under the
    // "By Semester" tab or that plan's own installment plan download.
    $upFirstKey = $fsUpcomingGrouped->keys()->first();
    $upRunningTotal = $upFirstKey ? $fsUpcomingGrouped->get($upFirstKey)->sum(fn ($v) => $v->balance()) : 0;
  @endphp
  @forelse($fsUpcomingGrouped as $upSemLabel => $upSemVouchers)
    @php
      // Accountants/teachers browsing a student's account still see every
      // semester in full — only the parent/student self-service views collapse
      // future semesters down to an estimated total.
      $isRunningSem = $showAccountantActions || $upSemLabel === $upFirstKey;

      // A further-out semester's real vouchers may already bake in the plan's
      // annual increase. When $flattenFutureEstimates is on, show a flat
      // estimate (same as the current/running semester) instead, so the
      // number doesn't jump around before that semester is even generated.
      $upSemTotal = (!$isRunningSem && $flattenFutureEstimates)
          ? $upRunningTotal
          : $upSemVouchers->sum(fn ($v) => $v->balance());
    @endphp
    <div class="fs-upcoming-sem">
      <div class="fs-upcoming-sem-head">
        <div class="fs-upcoming-sem-title"><i class="fas fa-layer-group"></i> {{ $upSemLabel }}</div>
        @if($isRunningSem)
          <span class="badge warning">Rs. {{ number_format($upSemTotal) }} total</span>
        @else
          <span class="badge gray" title="Itemized breakdown available under 'By Semester' or that plan's installment download">Rs. {{ number_format($upSemTotal) }} estimated</span>
        @endif
      </div>

      @if($isRunningSem)
      <div class="table-wrap">
        <table>
          <thead><tr><th>Voucher #</th><th>Title</th><th style="text-align:right">Amount</th><th>Due Date</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th></tr></thead>
          <tbody>
            @foreach($fsGroupByInstallment($upSemVouchers) as $row)
            <tr>
              <td style="font-weight:600;font-size:13px">{{ $row->voucher_no }}</td>
              <td style="font-size:12px;color:var(--text-muted)">{{ $row->items }}</td>
              <td style="text-align:right">Rs.{{ number_format($row->amount) }}</td>
              <td>{{ optional($row->due_date)->format('d M Y') }}</td>
              <td style="text-align:center"><span class="badge {{ $fsBadgeClass($row->status) }} sm">{{ $row->status }}</span>
              @if($row->payment_state === 'Partially Paid' && $row->schedule)
                <span class="badge {{ $row->schedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $row->schedule }}</span>
              @endif</td>
              <td style="text-align:center">@include('partials.fee-summary-actions', ['voucher' => $row->representative])</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" style="text-align:right;font-weight:700">Combined Total</td>
              <td style="text-align:right;font-weight:700;color:var(--warning)">Rs.{{ number_format($upSemTotal) }}</td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      @else
      <div style="padding:10px 16px;font-size:12px;color:var(--text-muted)">
        @if($flattenFutureEstimates)
          Flat estimate based on the current semester's fee (no annual increase applied) — not yet due, and not yet generated. The actual amount will be confirmed when this semester's vouchers are issued.
        @else
          Estimated total for this semester/year — not yet due. See "By Semester" for the itemized breakdown once it's closer.
        @endif
      </div>
      @endif
    </div>
  @empty
    <div style="text-align:center;padding:24px;color:var(--text-muted)">No upcoming fees.</div>
  @endforelse
</div>

{{-- Keep complete voucher groups together: one paid fee-head + one unpaid
     fee-head is a partially paid voucher, never a fully paid installment. --}}
@php
  $fsHistoryRows = $fsGroupByInstallment($fsAll)
      ->filter(fn ($row) => $row->payment_state !== 'Cancelled' && $row->paid_amount > 0.005)
      ->sortByDesc(fn ($row) => $row->due_date?->getTimestamp() ?? 0)
      ->values();
@endphp
<div class="card fs-card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-clock-rotate-left" style="color:var(--text-muted)"></i> Fee History — Paid &amp; Partially Paid Installments</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Voucher #</th><th>Title</th>
        <th style="text-align:right">Voucher Amount</th>
        <th style="text-align:right">Amount Paid</th>
        <th style="text-align:right">Remaining</th>
        <th>Due Date</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th>
      </tr></thead>
      <tbody>
        @forelse($fsHistoryRows as $row)
        <tr>
          <td style="font-weight:600;font-size:13px">{{ $row->voucher_no }}</td>
          <td style="font-size:12px;color:var(--text-muted)">{{ $row->items }}</td>
          <td style="text-align:right">Rs.{{ number_format($row->voucher_amount, 2) }}</td>
          <td style="text-align:right;color:var(--success);font-weight:700">Rs.{{ number_format($row->paid_amount, 2) }}</td>
          <td style="text-align:right">Rs.{{ number_format($row->balance_amount, 2) }}</td>
          <td>{{ optional($row->due_date)->format('d M Y') }}</td>
          <td style="text-align:center">
            <span class="badge {{ $row->payment_state === 'Paid' ? 'success' : 'warning' }} sm">{{ $row->payment_state }}</span>
            @if($row->payment_state === 'Partially Paid' && $row->schedule)
              <span class="badge {{ $row->schedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $row->schedule }}</span>
            @endif
          </td>
          <td style="text-align:center">
            @if($printRouteName)
              <a href="{{ route($printRouteName, $row->representative) }}" target="_blank" rel="noopener" class="fs-action-btn" title="View / Print"><i class="fas fa-print"></i></a>
            @elseif($feeRouteName)
              <a href="{{ route($feeRouteName, $row->representative) }}" target="_blank" rel="noopener" class="fs-action-btn" title="View voucher"><i class="fas fa-download"></i></a>
            @else
              —
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted)">No recorded paid or partially paid installments yet.</td></tr>
        @endforelse
      </tbody>
      @if($fsHistoryRows->isNotEmpty())
      <tfoot><tr style="font-weight:700">
        <td colspan="2" style="text-align:right">Total ({{ $fsHistoryRows->count() }} vouchers with payments)</td>
        <td style="text-align:right">Rs.{{ number_format($fsHistoryRows->sum('voucher_amount'), 2) }}</td>
        <td style="text-align:right;color:var(--success)">Rs.{{ number_format($fsHistoryRows->sum('paid_amount'), 2) }}</td>
        <td style="text-align:right">Rs.{{ number_format($fsHistoryRows->sum('balance_amount'), 2) }}</td>
        <td colspan="3"></td>
      </tr></tfoot>
      @endif
    </table>
  </div>
  <div style="padding:10px 16px;color:var(--text-muted);font-size:12px">
    Amount Paid includes payments and previously received credit allocated to vouchers. Remaining is the unpaid portion;
    future balances are not overdue. This history includes the recent fully paid vouchers and all currently partially paid vouchers.
  </div>
</div>

</div>{{-- /combined panel --}}

@if($fsHasData)
<div class="fs-panel" data-fs-panel="semester">
  @foreach($fsGrouped as $semLabel => $semVouchers)
    @php
      $semTotalDue = $semVouchers->sum(fn ($v) => $v->balance());
    @endphp
    <div class="card fs-card fs-sem-card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-layer-group" style="color:var(--primary)"></i> {{ $semLabel }}</div>
        @if($semTotalDue > 0)
          <span class="badge danger">Rs. {{ number_format($semTotalDue, 0) }} due</span>
        @else
          <span class="badge success">All settled</span>
        @endif
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Voucher #</th><th>Title</th><th style="text-align:right">Amount</th><th>Due Date</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th></tr></thead>
          <tbody>
            @foreach($fsGroupByInstallment($semVouchers) as $row)
              <tr>
                <td style="font-weight:600;font-size:13px">{{ $row->voucher_no }}</td>
                <td style="font-size:12px;color:var(--text-muted)">{{ $row->items }}</td>
                <td style="text-align:right">Rs.{{ number_format($row->status === 'Paid' ? $row->paid_amount : $row->amount) }}</td>
                <td>{{ optional($row->due_date)->format('d M Y') }}</td>
                <td style="text-align:center"><span class="badge {{ $fsBadgeClass($row->status) }} sm">{{ $row->status }}</span>
              @if($row->payment_state === 'Partially Paid' && $row->schedule)
                <span class="badge {{ $row->schedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $row->schedule }}</span>
              @endif</td>
                <td style="text-align:center">@include('partials.fee-summary-actions', ['voucher' => $row->representative])</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endforeach
</div>
@endif

@if($showAccountantActions)
<div class="fs-modal-overlay" id="fsPayModal">
  <div class="fs-modal">
    <div class="fs-modal-head">
      <div><i class="fas fa-money-bill-wave"></i> Receive Payment</div>
      <button type="button" onclick="fsClosePayModal()"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" id="fsPayForm" enctype="multipart/form-data">
      @csrf
      <div class="fs-modal-body">
        <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:14px">Voucher <strong id="fsPayVoucherNo">-</strong> &middot; Balance <strong id="fsPayBalanceDisplay">-</strong></div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Amount *</label>
          <input type="number" step="1" min="1" name="amount" id="fsPayAmount" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Method *</label>
          <select name="method" id="fsPayMethod" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Date Received</label>
          <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Reference No.</label>
          <input type="text" name="reference_no" class="form-control" placeholder="Optional">
        </div>
      </div>
      <div class="fs-modal-foot">
        <button type="button" class="btn btn-outline btn-sm" onclick="fsClosePayModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Confirm Payment</button>
      </div>
    </form>
  </div>
</div>
@endif

<style>
  .fs-tabs { display:flex; gap:6px; margin-bottom:16px; border-bottom:1px solid var(--border); }
  .fs-tab { background:none; border:none; padding:10px 16px; font-size:13px; font-weight:700; color:var(--text-muted); cursor:pointer; border-bottom:2.5px solid transparent; display:flex; align-items:center; gap:7px; transition:color .15s ease, border-color .15s ease; }
  .fs-tab:hover { color:var(--primary); }
  .fs-tab.active { color:var(--primary); border-bottom-color:var(--primary); }
  .fs-panel { display:none; }
  .fs-panel.active { display:block; }

  .fs-card { margin-bottom:20px; transition:box-shadow .15s ease; }
  .fs-card:hover { box-shadow:0 10px 24px rgba(0,0,0,.07); }
  .fs-card-danger { border:1px solid var(--danger); background:rgba(239,68,68,0.06); }
  .fs-card-warning { border:1px solid var(--warning); background:rgba(245,158,11,0.06); }
  .fs-sem-card .card-header { display:flex; align-items:center; justify-content:space-between; gap:10px; }

  .fs-upcoming-sem { border-top:1px solid var(--border); }
  .fs-upcoming-sem:first-of-type { border-top:none; }
  .fs-upcoming-sem-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 16px; background:var(--bg); }
  .fs-upcoming-sem-title { font-weight:700; font-size:13px; display:flex; align-items:center; gap:7px; }
  .fs-upcoming-sem-title i { color:var(--primary); }
  .badge.warning { background:rgba(245,158,11,0.12); color:var(--warning); }

  .fs-actions { display:inline-flex; align-items:center; gap:6px; }
  .fs-action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; border:1px solid var(--border); background:var(--card); color:var(--primary); cursor:pointer; font-size:12px; transition:background .12s ease, transform .12s ease; }
  .fs-action-btn:hover { background:var(--bg); transform:translateY(-1px); }
  .fs-action-btn.fs-pay-btn { color:#fff; background:var(--primary); border-color:var(--primary); }
  .fs-action-btn.fs-pay-btn:hover { filter:brightness(1.08); }
  .fs-action-btn.fs-cancel-btn { color:var(--danger); }
  .fs-action-btn.fs-cancel-btn:hover { background:rgba(239,68,68,0.1); }
  .badge.sm { font-size:10px; padding:2px 7px; }
  .badge.gray { background:var(--bg); color:var(--text-muted); }
  .fs-next-row { background:var(--bg); border-top:1px dashed var(--border); }
  .fs-next-row td { opacity:.85; }

  .fs-modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,15,20,.55); z-index:1200; align-items:center; justify-content:center; padding:16px; }
  .fs-modal-overlay.active { display:flex; }
  .fs-modal { background:var(--card); border-radius:14px; width:100%; max-width:400px; box-shadow:0 24px 60px rgba(0,0,0,.3); overflow:hidden; }
  .fs-modal-head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; font-weight:800; font-size:14.5px; border-bottom:1px solid var(--border); }
  .fs-modal-head button { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:15px; }
  .fs-modal-body { padding:18px; }
  .fs-modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:14px 18px; border-top:1px solid var(--border); background:var(--bg); }
</style>

<script>
(function () {
  document.querySelectorAll('.fs-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.fs-tab').forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      var target = tab.getAttribute('data-fs-tab');
      document.querySelectorAll('.fs-panel').forEach(function (p) {
        p.classList.toggle('active', p.getAttribute('data-fs-panel') === target);
      });
    });
  });

  window.fsClosePayModal = function () {
    var m = document.getElementById('fsPayModal');
    if (m) m.classList.remove('active');
  };

  document.querySelectorAll('.fs-pay-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var form = document.getElementById('fsPayForm');
      if (!form) return;
      form.action = btn.getAttribute('data-pay-url');
      var balance = btn.getAttribute('data-balance');
      document.getElementById('fsPayVoucherNo').textContent = btn.getAttribute('data-voucher-no');
      document.getElementById('fsPayBalanceDisplay').textContent = 'Rs. ' + balance;
      var amountInput = document.getElementById('fsPayAmount');
      amountInput.value = balance;
      amountInput.max = balance;
      document.getElementById('fsPayModal').classList.add('active');
    });
  });
})();
</script>
