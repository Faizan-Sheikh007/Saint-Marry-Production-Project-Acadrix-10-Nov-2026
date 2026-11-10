@extends('layouts.app')
@section('title', $student->name)
@section('content')
<div class="acct-breadcrumb" style="margin-bottom:14px">
  <a href="{{ route('accounts.students.index') }}"><i class="fas fa-user-graduate"></i> Students</a>
  <i class="fas fa-chevron-right"></i>
  <a href="{{ route('accounts.students.index', ['program_id' => $student->program_id]) }}">{{ $student->program->name ?? '-' }}</a>
  <i class="fas fa-chevron-right"></i>
  <a href="{{ route('accounts.students.index', ['school_class_id' => $student->school_class_id]) }}">{{ $student->schoolClass->section_label ?? '-' }}</a>
  <i class="fas fa-chevron-right"></i>
  <span>{{ $student->name }}</span>
</div>

{{-- ============ Attractive profile banner (photo, program, academic year, semester) ============ --}}
<div class="acct-banner">
  <div class="acct-banner-body">
    @if($student->photo)
      <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" class="acct-banner-avatar">
    @else
      <div class="acct-banner-avatar acct-banner-avatar-fallback">{{ strtoupper(substr($student->name,0,2)) }}</div>
    @endif
    <div class="acct-banner-info">
      <div class="acct-banner-name">{{ $student->name }} <span class="acct-banner-id">({{ $student->student_id }})</span></div>
      <div class="acct-banner-father"><span class="acct-banner-father-label">Father Name:</span> {{ filled($student->father_name) ? $student->father_name : 'Not provided' }}</div>
      <div class="acct-banner-chips">
        <span class="acct-banner-chip"><i class="fas fa-graduation-cap"></i> {{ $student->program->name ?? '—' }}</span>
        <span class="acct-banner-chip"><i class="fas fa-calendar-days"></i> {{ $student->session ?? ($student->academicYear->name ?? '—') }}</span>
        <span class="acct-banner-chip"><i class="fas fa-layer-group"></i> {{ $student->schoolClass->section_label ?? '—' }}</span>
      </div>
    </div>
    <div class="acct-banner-balance">
      <div class="acct-banner-balance-lbl">Outstanding Balance</div>
      <div class="acct-banner-balance-val" style="color:{{ $outstandingTotal > 0 ? '#ffd7d7' : '#d4f7dd' }}">Rs. {{ number_format($outstandingTotal, 0) }}</div>
      @if($overdueTotal > 0)
        <span class="acct-banner-badge danger"><i class="fas fa-triangle-exclamation"></i> Overdue: Rs. {{ number_format($overdueTotal, 0) }}</span>
      @elseif($outstandingTotal > 0)
        <span class="acct-banner-badge success"><i class="fas fa-calendar-check"></i> Scheduled payments — nothing overdue</span>
      @else
        <span class="acct-banner-badge success"><i class="fas fa-circle-check"></i> All fees settled</span>
      @endif
      @if($student->credit_balance > 0)
        <div style="font-size:11px;margin-top:8px;opacity:.9">Credited Amount (Available): <strong>Rs. {{ number_format($student->credit_balance, 2) }}</strong></div>
      @endif
    </div>
  </div>
</div>

<div class="acct-banner-note"><i class="fas fa-lock"></i> Profile details are read-only — edits are managed by EMS. Payments below apply only to this student's fee balance.</div>

@if((float) $student->credit_balance > 0)
<div class="card" style="margin-bottom:18px"><div class="card-body">
  <strong>Available credit: Rs. {{ number_format($student->credit_balance, 2) }}</strong>
  <p>This is money already received. Apply it to the oldest outstanding vouchers; do not receive it again.</p>
  <form method="POST" action="{{ route('payments.apply-credit', $student) }}">@csrf
    <button class="btn btn-primary" type="submit">Apply Existing Credit to Vouchers</button>
  </form>
</div></div>
@endif
<div style="margin-bottom:18px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
  <a href="#received-payments" class="btn btn-primary"><i class="fas fa-list"></i> View Received Payments</a>
  <a href="{{ route('payments.extra-receipts.index', $student) }}" class="btn btn-outline"><i class="fas fa-receipt"></i> Extra Payment Receipts / Reprint</a>
</div>
<details class="card" style="margin-bottom:18px">
  <summary class="card-header" style="cursor:pointer">Extra Payments &amp; Credit History</summary>
  <div class="card-body" style="overflow-x:auto"><table class="table">
    <thead><tr><th>Date</th><th>Type</th><th>Credit change</th><th>Credit after</th><th>Method / Reference</th><th>Notes</th><th>Student Receipt / Office Voucher</th></tr></thead>
    <tbody>@forelse($student->balanceTransactions()->latest('id')->limit(100)->get() as $entry)
      <tr><td>{{ $entry->created_at->format('d M Y H:i') }}</td><td>{{ ucfirst($entry->type) }}</td>
        <td>Rs. {{ number_format($entry->amount, 2) }}</td><td>Rs. {{ number_format($entry->balance_after, 2) }}</td>
        <td>{{ ucwords(str_replace('_', ' ', $entry->method ?? '')) }} {{ $entry->reference_no }}</td><td>{{ $entry->notes }}</td>
        <td>
          @if($entry->type === 'receipt' && (float) $entry->amount > 0)
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <a href="{{ route('payments.extra-receipts.show', [$student, $entry]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Print All 3 Copies</a>
              <a href="{{ route('payments.extra-receipts.voucher', [$student, $entry]) }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener" title="Print all three copies for this original payment only; does not create a charge">Print All 3 Copies</a>
            </div>
          @else &mdash; @endif
        </td></tr>
    @empty<tr><td colspan="7">No credit movements recorded since this update. Older credit remains available above.</td></tr>@endforelse</tbody>
  </table><p class="text-sm text-muted">Latest 100 movements. Receipts are money received; allocations only transfer that credit to vouchers and are not additional cash receipts. The office voucher uses the original received amount and does not create another fee demand.</p></div>
</details>


{{-- Post-admission relief applies only to unpaid amounts; no paid receipt is changed. --}}
@if($student->feeAssignments()->exists())
<div class="card" style="margin-bottom:20px"><div class="card-header"><div class="card-title"><i class="fas fa-hand-holding-heart"></i> Post-admission Scholarship / Financial Aid</div></div>
<div class="card-body">
  <p style="font-size:12px;color:var(--text-muted)">Grant additional scholarship or aid on an existing semester plan. This reduces unpaid installments, challans, and vouchers immediately; amounts already received remain unchanged.</p>
  <form method="POST" id="postAdmissionAidForm" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap" action="">
    @csrf
    <label>Installment Plan
      <select class="form-control" id="reliefPlanSelect" required onchange="document.getElementById('postAdmissionAidForm').action=this.selectedOptions[0].dataset.url">
        <option value="">Choose plan</option>
        @foreach($student->feeAssignments()->orderByDesc('id')->get() as $plan)
          <option value="{{ $plan->id }}" data-url="{{ route('accounts.students.relief.store', [$student, $plan]) }}">{{ $plan->feeTemplate?->name ?? 'Fee Plan' }} #{{ $plan->id }} — {{ $plan->cycleRangeLabel() }}</option>
        @endforeach
      </select>
    </label>
    <label>Type <select name="type" class="form-control" required><option value="scholarship">Scholarship</option><option value="financial_aid">Financial Aid</option></select></label>
    <label>Amount (Rs.) <input type="number" min="1" step="1" name="amount" class="form-control" required></label>
    <label>Reason <input name="notes" maxlength="500" class="form-control" placeholder="Approval / reference"></label>
    <button class="btn btn-primary" onclick="if(!document.getElementById('reliefPlanSelect').value){event.preventDefault();alert('Select a plan first')}">Apply Aid</button>
  </form>
</div></div>
@endif

{{-- ============ Semester Pending Fee + Ledger Number — quick-glance strip ============ --}}
<div class="acct-quickstrip">
  <div class="acct-quickcard">
    <div class="acct-quickcard-lbl"><i class="fas fa-layer-group"></i> Semester Remaining Fee</div>
    @if($currentSemesterLabel)
      <div class="acct-quickcard-val" style="color:{{ $currentSemesterPending > 0 ? 'var(--danger)' : 'var(--success)' }}">
        Rs. {{ number_format($currentSemesterPending, 0) }}
      </div>
      <div class="acct-quickcard-sub">{{ $currentSemesterLabel }}</div>
    @else
      <div class="acct-quickcard-val" style="color:var(--success)">Rs. 0</div>
      <div class="acct-quickcard-sub">No semester currently pending</div>
    @endif
  </div>

  <div class="acct-quickcard">
    <div class="acct-quickcard-lbl"><i class="fas fa-book"></i> Ledger Number</div>
    <div id="ledgerNumberDisplay" style="{{ request('edit_ledger') ? 'display:none' : '' }}">
      <div class="acct-quickcard-val" style="font-size:16px">{{ $student->ledger_number ?: '—' }}</div>
      <button type="button" class="btn btn-outline btn-sm" style="margin-top:8px" onclick="document.getElementById('ledgerNumberDisplay').style.display='none';document.getElementById('ledgerNumberForm').style.display='block';">
        <i class="fas fa-pen"></i> {{ $student->ledger_number ? 'Edit' : 'Add' }}
      </button>
    </div>
    <div id="ledgerNumberForm" style="{{ request('edit_ledger') ? '' : 'display:none' }}">
      <form action="{{ route('accounts.students.ledger-number.update', $student) }}" method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        @csrf
        @method('PUT')
        <input type="text" name="ledger_number" class="form-control" style="max-width:160px" maxlength="50" placeholder="e.g. LG-2026-014" value="{{ $student->ledger_number }}">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save</button>
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('ledgerNumberForm').style.display='none';document.getElementById('ledgerNumberDisplay').style.display='block';">Cancel</button>
      </form>
      @if($student->ledger_number)
      <form action="{{ route('accounts.students.ledger-number.destroy', $student) }}" method="POST" style="margin-top:8px" onsubmit="return confirm('Clear the ledger number for this student?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger)"><i class="fas fa-trash"></i> Clear</button>
      </form>
      @endif
    </div>
  </div>
</div>

@if($student->guardian_name || $student->guardian_phone)
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-user-shield"></i> Guardian</div></div>
  <div class="card-body" style="display:flex;gap:32px;flex-wrap:wrap;font-size:13px">
    <div><strong>Name:</strong> {{ $student->guardian_name ?? '—' }}</div>
    <div><strong>Phone:</strong> {{ $student->guardian_phone ?? '—' }}</div>
    <div><strong>Relation:</strong> {{ $student->guardian_relation ?? '—' }}</div>
  </div>
</div>
@endif

@if($outstandingTotal > 0)
<div class="card" style="margin-bottom:24px;border:1px solid var(--primary)">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
    <div class="card-title"><i class="fas fa-money-bill-wave"></i> Receive Payment</div>
    <a href="#received-payments" class="btn btn-outline btn-sm"><i class="fas fa-history"></i> View Payment History</a>
  </div>
  <div class="card-body">
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
      Record a payment received from this student (cash, bank transfer, cheque, or an online payment you're keying in yourself).
      It will be applied automatically to their oldest outstanding voucher(s) first, and their balance above will update immediately.
    </p>
    <form action="{{ route('payments.store-for-student', $student) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Amount *</label>
          <input type="number" step="1" min="1" max="{{ (int) $outstandingTotal }}" name="amount" class="form-control" required value="{{ (int) $outstandingTotal }}">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Method *</label>
          <select name="method" id="asMethod" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Date Received</label>
          <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Reference No.</label>
          <input type="text" name="reference_no" class="form-control" placeholder="Optional">
        </div>
      </div>

      <div class="form-group" id="asProofGroup" style="display:none;margin-top:14px;margin-bottom:0">
        <label class="form-label"><i class="fas fa-paperclip" style="color:var(--primary)"></i> Payment Proof <span style="font-weight:400;color:var(--text-muted)">(optional — screenshot or photo)</span></label>
        <div class="proof-upload" id="asProofDrop">
          <input type="file" name="proof" id="asProofInput" accept="image/*,.pdf" style="display:none">
          <div class="proof-upload-inner" id="asProofInner">
            <i class="fas fa-cloud-arrow-up"></i>
            <span>Click to attach a payment screenshot / receipt <small>(JPG, PNG or PDF, max 5MB)</small></span>
          </div>
          <div class="proof-preview" id="asProofPreview">
            <img id="asProofImg" style="display:none">
            <div class="proof-file-icon" id="asProofFileIcon" style="display:none"><i class="fas fa-file-pdf"></i></div>
            <span class="proof-name" id="asProofName"></span>
            <button type="button" class="proof-remove" id="asProofRemove" title="Remove"><i class="fas fa-xmark"></i></button>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-top:14px;margin-bottom:0">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:16px">
        <i class="fas fa-check"></i> Receive Payment &amp; Update Balance
      </button>
    </form>
  </div>
</div>
@endif

{{-- Payment receipts, shown by original collection (not collapsed into voucher totals).
     Keep this section visible even when the entire fee plan is paid. --}}
@php
  $directReceivedTotal = round((float) $receivedPayments->where('type', 'voucher_payment')->sum('amount'), 2);
  $extraReceivedTotal = round((float) $receivedPayments->where('type', 'extra_receipt')->sum('amount'), 2);
@endphp
<div class="card received-payment-card" id="received-payments" style="margin-bottom:24px">
  <div class="card-header" style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div class="card-title"><i class="fas fa-receipt"></i> Payment Received History — Individual Receipts <span style="margin-left:8px">By Date</span></div>
    <span class="badge success">{{ $receivedPayments->count() }} recorded collection{{ $receivedPayments->count() === 1 ? '' : 's' }}</span>
  </div>
  <div class="card-body" style="padding-bottom:12px">
    <div class="received-payment-metrics">
      <div class="received-payment-metric is-total"><div class="received-payment-caption">Total Money Received</div><div class="received-payment-value">Rs. {{ number_format($receivedPaymentsTotal, 2) }}</div></div>
      <div class="received-payment-metric"><div class="received-payment-caption">Direct Fee Payments</div><div class="received-payment-value">Rs. {{ number_format($directReceivedTotal, 2) }}</div></div>
      <div class="received-payment-metric"><div class="received-payment-caption">Extra Payments Received</div><div class="received-payment-value">Rs. {{ number_format($extraReceivedTotal, 2) }}</div></div>
    </div>
    <p class="text-sm text-muted" style="margin:9px 0 4px">Each row is one recorded collection, including payments that were split across multiple vouchers. Applied student credit is not counted again as a new payment.</p>
  </div>
  <div class="table-wrap">
    <table class="received-payment-table">
      <thead><tr>
        <th>Receipt # / Type</th><th>Date Received</th><th style="text-align:right">Amount Received</th>
        <th>Method / Reference</th><th>Received By</th><th>Applied To</th><th>Receipt / Proof</th>
      </tr></thead>
      <tbody>
      @forelse($receivedPayments as $receipt)
        <tr>
          <td><strong>{{ $receipt->receipt_number }}</strong><div class="received-payment-sub">{{ $receipt->type === 'extra_receipt' ? 'Extra payment / credit' : 'Fee payment' }}</div></td>
          <td style="white-space:nowrap">{{ $receipt->paid_at?->format('d M Y') ?? '—' }}<div class="received-payment-sub">Recorded {{ $receipt->recorded_at?->format('d M Y H:i') ?? '—' }}</div></td>
          <td class="received-payment-amount">Rs. {{ number_format($receipt->amount, 2) }}</td>
          <td>{{ ucwords(str_replace('_', ' ', $receipt->method ?? 'Not recorded')) }}<div class="received-payment-sub">{{ $receipt->reference_no ?: 'No reference' }}</div></td>
          <td>{{ $receipt->received_by ?: '—' }}</td>
          <td>
            @if($receipt->type === 'extra_receipt')
              <span class="received-payment-sub">Added to student credit; allocations are shown in the credit ledger</span>
            @else
              @foreach($receipt->voucher_numbers as $voucherNumber)
                <div class="received-payment-voucher">{{ $voucherNumber }}</div>
              @endforeach
            @endif
            @if($receipt->notes)<div class="received-payment-sub">{{ $receipt->notes }}</div>@endif
          </td>
          <td style="min-width:120px">
            @if($receipt->payment_id)
              <a class="btn btn-outline btn-sm" href="{{ route('payments.received-receipt.show', [$student, $receipt->payment_id]) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Receipt</a>
            @elseif($receipt->extra_receipt_id)
              <a class="btn btn-outline btn-sm" href="{{ route('payments.extra-receipts.show', [$student, $receipt->extra_receipt_id]) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Receipt</a>
            @endif
            @foreach($receipt->proof_paths as $path)
              <a class="received-payment-proof" href="{{ asset('storage/'.$path) }}" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> Proof</a>
            @endforeach
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)">No received payments recorded for this student yet.</td></tr>
      @endforelse
      </tbody>
      @if($receivedPayments->isNotEmpty())
        <tfoot><tr style="font-weight:800;background:var(--bg)"><td colspan="2">Total ({{ $receivedPayments->count() }} collections)</td><td style="text-align:right;white-space:nowrap">Rs. {{ number_format($receivedPaymentsTotal, 2) }}</td><td colspan="4"></td></tr></tfoot>
      @endif
    </table>
  </div>
  @if($originalPaymentActions->isNotEmpty())
  <div class="received-payment-audit">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
      <strong><i class="fas fa-clipboard-list"></i> Original Receive Payment Entries — Historical Audit</strong>
      <span style="font-size:11px;color:var(--text-muted)">{{ $originalPaymentActions->count() }} recorded action{{ $originalPaymentActions->count() === 1 ? '' : 's' }}</span>
    </div>
    <p class="received-payment-sub" style="margin-bottom:11px">These are the <strong>exact amounts originally entered</strong> in the Receive Payment form, including older payments that were divided between vouchers. For example, a single Rs. 22,000 collection may have been split into several voucher allocations above. <strong>Do not add these audit amounts to the total again.</strong> This log preserves receipt actions; it does not confirm whether a later reversal occurred.</p>
    <div class="table-wrap">
      <table class="received-payment-table">
        <thead><tr><th>Audit Entry</th><th>Time Recorded</th><th style="text-align:right">Original Amount Entered</th><th>Vouchers Applied To</th><th>Recorded By</th></tr></thead>
        <tbody>
        @foreach($originalPaymentActions as $action)
          <tr>
            <td>#{{ $action->audit_id }}</td>
            <td style="white-space:nowrap">{{ $action->recorded_at?->format('d M Y H:i') ?? '—' }}</td>
            <td class="received-payment-amount">Rs. {{ number_format($action->amount, 2) }}</td>
            <td>{{ implode(', ', $action->voucher_numbers) }}</td>
            <td>{{ $action->recorded_by ?: '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
  <div class="received-payment-footer">This is the money actually collected (verified direct payments plus extra-payment receipts), not the original voucher charges. Older payments recorded before collection grouping may appear as separate voucher allocations where the original split cannot be established. Previously recorded legacy extra-payment receipts are available through <a href="{{ route('payments.extra-receipts.index', $student) }}">Extra Payment Receipts / Reprint</a>.</div>
</div>

{{-- Previous balance / active / upcoming / paid — same automated buckets used everywhere else.
     Accountant gets real actions here (print, receive payment, cancel) plus a combined/by-semester toggle. --}}
@include('partials.fee-summary', [
    'feeSummary' => $feeSummary,
    'feeRouteName' => null,
    'showAccountantActions' => true,
    'printRouteName' => 'vouchers.print',
])

{{-- ============ Semester/Part-wise fee cards — one voucher number per installment ============ --}}
<div style="font-size:16px;font-weight:800;margin:28px 0 12px"><i class="fas fa-layer-group" style="color:var(--primary)"></i> Semester / Part-wise Fees</div>

<div class="acct-semester-grid">
  @for($cycle = 1; $cycle <= $totalCycles; $cycle++)
    @php
      $asg = $cycleOwner[$cycle] ?? null;
      $cycleVouchers = $asg ? $asg->vouchersForCycle($cycle)->reject(fn ($v) => $v->status === 'cancelled') : collect();
      $cyclePaid = $cycleVouchers->isNotEmpty() && $cycleVouchers->every(fn ($v) => $v->balance() <= 0);
      $cyclePending = $cycleVouchers->sum(fn ($v) => $v->balance());
      $cycleOverdue = $cycleVouchers->filter(fn ($v) => $v->balance() > 0 && $v->due_date && $v->due_date->lt(now()->startOfDay()))
        ->sum(fn ($v) => $v->balance());
      $label = $asg ? $asg->termLabelFor($cycle) : ('Semester ' . $cycle . ' of ' . $totalCycles);
      $cardStatusClass = !$asg ? '' : ($cyclePaid ? 'status-paid' : ($cyclePending > 0 ? 'status-due' : 'status-pending'));
    @endphp
    <div class="acct-semester-card {{ !$asg && (int) $cycle !== $creditOverview['display_cycle'] ? 'is-empty' : '' }} {{ $cardStatusClass }}">
      <div class="acct-semester-head">
        <div>
          <div class="acct-semester-title">{{ $label }}</div>
          @if($asg)
            <div class="acct-semester-sub">{{ $asg->feeTemplate->name ?? '-' }} &middot; {{ $cycleVouchers->pluck('part_no')->filter(fn ($n) => $n !== null)->unique()->count() ?: $asg->installments }} installment(s)</div>
          @endif
        </div>
        @if($cycleVouchers->isNotEmpty())
          @if($cyclePaid)
            <span class="badge success">Paid</span>
          @elseif($cyclePending > 0 && $cycleOverdue > 0)
            <span class="badge danger">Rs. {{ number_format($cycleOverdue, 0) }} overdue</span>
          @elseif($cyclePending > 0)
            <span class="badge warning">Rs. {{ number_format($cyclePending, 0) }} scheduled</span>
          @else
            <span class="badge gray">-</span>
          @endif
        @elseif($asg)
          <span class="badge warning">Not generated</span>
        @else
          <span class="badge gray">Not assigned</span>
        @endif
      </div>

      @if($cycleVouchers->isNotEmpty())
      <div class="acct-installments">
        @foreach($cycleVouchers->groupBy('part_no')->sortKeys() as $partNo => $lines)
          @php
            $first = $lines->first();
            $activeLines = $lines->reject(fn ($v) => $v->status === 'cancelled');
            $lineTotal = round($activeLines->sum(fn ($v) => $v->total()), 2);
            $lineReceived = round($activeLines->sum(fn ($v) => min($v->total(), max(0, (float) $v->paid_amount))), 2);
            $linePending = max(0, round($activeLines->sum(fn ($v) => $v->balance()), 2));
            $lineVoucherNumbers = $activeLines->pluck('voucher_no')->unique()->values();
            $lineEarliestDue = $activeLines->pluck('due_date')->filter()->sort()->first();
            $lineCarryForward = $activeLines->isNotEmpty() ? round($activeLines->first()->carryForwardBalance(), 2) : 0;
            // Main voucher amount is what is actually collectible now: the
            // current installment's REMAINING balance, plus arrears only once
            // this installment has reached its due date.
            $lineDisplayAmount = max(0, $linePending + $lineCarryForward);
            $lineStatus = $activeLines->isEmpty() ? 'Cancelled'
              : ($linePending <= 0.01 ? 'Paid'
              : ($lineReceived > 0 ? 'Partially Paid' : 'Unpaid'));
            $lineSchedule = null;
            if ($linePending > 0.01 && $activeLines->isNotEmpty()) {
              $lineSchedule = !$lineEarliestDue ? 'Upcoming'
                : ($lineEarliestDue->lt(now()->startOfDay()) ? 'Overdue'
                : ($lineEarliestDue->isSameDay(now()) ? 'Due Today'
                : ($lineEarliestDue->lte(now()->startOfDay()->addDays(7)) ? 'Due Soon' : 'Upcoming')));
            }
            $lineStatusClass = match (true) {
              $lineStatus === 'Paid' => 'success',
              $lineStatus === 'Cancelled' => 'gray',
              $lineSchedule === 'Overdue' => 'danger',
              default => 'warning',
            };
          @endphp
          <div class="acct-installment-row">
            <div class="acct-installment-main">
              <div class="acct-installment-title">{{ $asg->installmentLabelFor($cycle, $partNo ?? $first->installment_no) }}</div>
              <div class="acct-installment-sub">
                @foreach($lineVoucherNumbers as $voucherNumber)
                  <span style="display:inline-block;margin-right:9px">{{ $voucherNumber }}</span>
                @endforeach
                @if($lineVoucherNumbers->count() > 1)
                  <small style="opacity:.7">({{ $lineVoucherNumbers->count() }} vouchers for this installment)</small>
                @endif
              </div>
              <div class="acct-installment-sub">Due {{ $lineEarliestDue?->format('d M, Y') ?? '—' }}{{ $first->month ? ' - ' . $first->month : '' }}</div>
              <div class="acct-installment-sub">{{ $activeLines->pluck('feeHead.name')->filter()->unique()->implode(', ') ?: $activeLines->pluck('title')->unique()->implode(', ') }}</div>
              @if($lineCarryForward > 0)
                <div class="acct-installment-sub" style="margin-top:3px;font-weight:700;color:var(--danger)">+ Rs. {{ number_format($lineCarryForward, 0) }} previous balance carried forward</div>
              @endif
              @if($lineReceived > 0 || $lineVoucherNumbers->count() > 1)
                <div class="acct-installment-sub" style="margin-top:4px;font-weight:600">
                  Total: Rs. {{ number_format($lineTotal, 0) }} &middot;
                  <span style="color:var(--success)">Paid / credited: Rs. {{ number_format($lineReceived, 0) }}</span> &middot;
                  <span style="color:{{ $linePending > 0 ? 'var(--danger)' : 'var(--success)' }}">Remaining: Rs. {{ number_format($linePending, 0) }}</span>
                </div>
              @endif
            </div>
            <div class="acct-installment-amount">Rs. {{ number_format($lineDisplayAmount, 0) }}</div>
            <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start">
              <span class="badge {{ $lineStatusClass }} sm">{{ $lineStatus }}</span>
              @if($lineSchedule)
                <span class="badge {{ $lineSchedule === 'Overdue' ? 'danger' : 'gray' }} sm">{{ $lineSchedule }}</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>
      <div class="acct-semester-links">
        <a href="{{ route('fee-assignments.cycle-summary', [$asg, $cycle]) }}" target="_blank"><i class="fas fa-file-invoice-dollar"></i> Download Summary</a>
      </div>
      @endif
      @if((int) $cycle === $creditOverview['display_cycle'])
        @include('partials.student-credit')
      @elseif(!$asg && (int) $cycle === $creditOverview['display_cycle'] + 1 && $creditOverview['available_minor'] > 0)
        <div class="student-credit-next">Available student credit will be applied when this semester's vouchers are generated. No second payment is needed for the credited portion.</div>
      @endif
    </div>
  @endfor
</div>

@if($standaloneVouchers->isNotEmpty())
<div class="card" style="margin-top:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:6px"></i>Other / One-off Vouchers</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Voucher No</th><th>Title</th><th>Amount</th><th>Due Date</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($standaloneVouchers as $v)
        <tr>
          <td>{{ $v->voucher_no }}</td>
          <td>{{ $v->title }}</td>
          <td>Rs. {{ number_format($v->total(), 0) }}</td>
          <td>{{ optional($v->due_date)->format('M d, Y') }}</td>
          <td><span class="badge {{ $v->status === 'paid' ? 'success' : ($v->status === 'overdue' ? 'danger' : 'warning') }}">{{ $v->autoStatusLabel() }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

{{-- Reconciled current-voucher history and all verified/submitted payment activity.
     Uses exactly the same calculation and labels as the student portal;
     historical/superseded vouchers remain visible in the payment audit table. --}}
@include('partials.student-finance-history', [
  'feeLedger' => $feeLedger,
  'paymentHistory' => $paymentHistory,
  'financeShowTotals' => false,
  'financeShowPlans' => false,
  'financeVoucherRoute' => 'vouchers.show',
  'financeReceiptRoute' => 'vouchers.receipt',
])

<style>
  .received-payment-metrics { display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:10px; }
  .received-payment-metric { background:var(--bg);border:1px solid var(--border);padding:12px 14px;border-radius:10px; }
  .received-payment-metric.is-total { background:rgba(5,150,105,.06);border-color:rgba(5,150,105,.2); }
  .received-payment-caption { font-size:11px;letter-spacing:.025em;text-transform:uppercase;color:var(--text-muted);font-weight:700;margin-bottom:6px; }
  .received-payment-value { font-size:19px;font-weight:800;color:var(--text);white-space:nowrap; }
  .received-payment-metric.is-total .received-payment-value { color:#059669; }
  .received-payment-table { min-width:850px;width:100%;font-size:12px; }
  .received-payment-table th { font-size:11px;white-space:nowrap; }
  .received-payment-table td { vertical-align:top; }
  .received-payment-amount { text-align:right;white-space:nowrap;font-weight:800;color:#059669;font-variant-numeric:tabular-nums; }
  .received-payment-sub { font-size:11px;color:var(--text-muted);margin-top:3px;overflow-wrap:anywhere; }
  .received-payment-voucher { font-size:11px;font-weight:700;white-space:nowrap;margin-bottom:3px; }
  .received-payment-proof { display:inline-block;margin:7px 5px 0 0;font-size:11px;color:var(--primary); }
  .received-payment-audit { padding:16px 18px;border-top:1px solid var(--border);background:rgba(99,102,241,.025); }
  .received-payment-audit .received-payment-table { min-width:640px; }
  .received-payment-footer { padding:11px 18px;border-top:1px solid var(--border);color:var(--text-muted);font-size:11px;line-height:1.6; }

  .acct-breadcrumb { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-muted); flex-wrap:wrap; }
  .acct-breadcrumb a { color:var(--primary); font-weight:600; }
  .acct-breadcrumb i { font-size:9px; }

  .acct-banner { background:linear-gradient(120deg, var(--primary), #6d28d9); border-radius:var(--radius); box-shadow:var(--shadow-lg, 0 10px 30px rgba(0,0,0,.12)); padding:2px; margin-bottom:8px; }
  .acct-banner-body { display:flex; gap:18px; align-items:center; flex-wrap:wrap; padding:20px 24px; color:#fff; }
  .acct-banner-avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2.5px solid rgba(255,255,255,.65); box-shadow:0 4px 14px rgba(0,0,0,.15); }
  .acct-banner-avatar-fallback { display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.18); font-weight:800; font-size:21px; }
  .acct-banner-info { flex:1; min-width:220px; }
  .acct-banner-name { font-weight:800; font-size:19px; }
  .acct-banner-id { font-weight:500; font-size:13px; opacity:.85; }
  .acct-banner-father { margin-top:5px; font-size:13px; line-height:1.4; color:rgba(255,255,255,.95); overflow-wrap:anywhere; }
  .acct-banner-father-label { font-weight:700; }
  .acct-banner-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
  .acct-banner-chip { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:600; background:rgba(255,255,255,.16); padding:5px 12px; border-radius:20px; white-space:nowrap; }
  .acct-banner-chip i { opacity:.9; }
  .acct-banner-balance { margin-left:auto; text-align:right; }
  .acct-banner-balance-lbl { font-size:11px; text-transform:uppercase; letter-spacing:.06em; opacity:.85; font-weight:700; }
  .acct-banner-balance-val { font-size:25px; font-weight:800; margin-top:2px; }
  .acct-banner-badge { display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; margin-top:6px; }
  .acct-banner-badge.danger { background:rgba(220,38,38,.25); color:#fff; }
  .acct-banner-badge.success { background:rgba(22,163,74,.28); color:#fff; }
  .acct-banner-note { font-size:11px; color:var(--text-muted); margin:0 0 24px 4px; }

  .acct-quickstrip { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px; }
  .acct-quickcard { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:14px 18px; }
  .acct-quickcard-lbl { font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700; color:var(--text-muted); display:flex; align-items:center; gap:6px; margin-bottom:6px; }
  .acct-quickcard-val { font-size:20px; font-weight:800; }
  .acct-quickcard-sub { font-size:11.5px; color:var(--text-muted); margin-top:2px; }

  .acct-semester-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px; }
  .acct-semester-card { background:var(--card); border:1px solid var(--border); border-left:4px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:16px; display:flex; flex-direction:column; gap:10px; transition:box-shadow .15s ease, transform .15s ease; }
  .acct-semester-card:hover { box-shadow:0 10px 24px rgba(0,0,0,.08); transform:translateY(-2px); }
  .acct-semester-card.is-empty { opacity:.6; }
  .acct-semester-card.status-paid { border-left-color:var(--success); }
  .acct-semester-card.status-due { border-left-color:var(--danger); }
  .acct-semester-card.status-pending { border-left-color:var(--warning); }
  .acct-semester-head { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
  .acct-semester-title { font-weight:800; font-size:14px; color:var(--primary); }
  .acct-semester-sub { font-size:11px; color:var(--text-muted); margin-top:2px; }
  .acct-installments { display:flex; flex-direction:column; gap:6px; border-top:1px dashed var(--border); padding-top:8px; }
  .acct-installment-row { display:flex; align-items:center; gap:8px; font-size:12px; padding:6px 8px; flex-wrap:wrap; border-radius:8px; transition:background .12s ease; }
  .acct-installment-row:hover { background:var(--bg); }
  .acct-installment-main { flex:1; min-width:120px; }
  .acct-installment-title { font-weight:700; }
  .acct-installment-sub { font-size:10.5px; color:var(--text-muted); }
  .acct-installment-amount { font-weight:700; }
  .acct-semester-links { border-top:1px dashed var(--border); padding-top:8px; margin-top:2px; }
  .acct-semester-links a { color:var(--primary); font-weight:600; font-size:11.5px; }
  .badge.sm { font-size:10px; padding:2px 7px; }

  .proof-upload { border:1.5px dashed var(--border); border-radius:10px; background:var(--bg); transition:border-color .15s ease; }
  .proof-upload:hover { border-color:var(--primary); }
  .proof-upload-inner { display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer; font-size:12.5px; color:var(--text-muted); }
  .proof-upload-inner i { font-size:17px; color:var(--primary); flex-shrink:0; }
  .proof-upload-inner small { display:block; color:#94a3b8; font-size:11px; margin-top:2px; }
  .proof-preview { display:none; align-items:center; gap:12px; padding:9px 12px; }
  .proof-preview img { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border); flex-shrink:0; }
  .proof-preview .proof-file-icon { width:40px; height:40px; border-radius:8px; background:#fee2e2; color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
  .proof-preview .proof-name { font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:240px; }
  .proof-preview .proof-remove { margin-left:auto; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:4px; }
  .proof-preview .proof-remove:hover { color:var(--danger); }
</style>

@if($outstandingTotal > 0)
<script>
(function () {
  const methodSelect = document.getElementById('asMethod');
  const group    = document.getElementById('asProofGroup');
  const drop     = document.getElementById('asProofDrop');
  const input    = document.getElementById('asProofInput');
  const inner    = document.getElementById('asProofInner');
  const preview  = document.getElementById('asProofPreview');
  const img      = document.getElementById('asProofImg');
  const fileIcon = document.getElementById('asProofFileIcon');
  const nameEl   = document.getElementById('asProofName');
  const removeBtn = document.getElementById('asProofRemove');
  if (!methodSelect) return;

  function toggleGroup() {
    const needsProof = methodSelect.value !== 'cash';
    group.style.display = needsProof ? 'block' : 'none';
    if (!needsProof) clearFile();
  }
  function clearFile() {
    input.value = '';
    inner.style.display = 'flex';
    preview.style.display = 'none';
    img.style.display = 'none';
    fileIcon.style.display = 'none';
  }
  drop.addEventListener('click', function (e) {
    if (e.target !== removeBtn && !removeBtn.contains(e.target)) input.click();
  });
  input.addEventListener('change', function () {
    const file = input.files[0];
    if (!file) { clearFile(); return; }
    if (file.size > 5 * 1024 * 1024) {
      alert('File is larger than 5MB — please choose a smaller screenshot or PDF.');
      clearFile();
      return;
    }
    inner.style.display = 'none';
    preview.style.display = 'flex';
    nameEl.textContent = file.name;
    if (file.type.startsWith('image/')) {
      img.style.display = 'block';
      fileIcon.style.display = 'none';
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; };
      reader.readAsDataURL(file);
    } else {
      img.style.display = 'none';
      fileIcon.style.display = 'flex';
    }
  });
  removeBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    clearFile();
  });
  methodSelect.addEventListener('change', toggleGroup);
  toggleGroup();
})();
</script>
@endif

@endsection
