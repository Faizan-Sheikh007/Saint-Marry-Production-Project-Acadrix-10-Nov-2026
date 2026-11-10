@extends('layouts.app')
@section('title', 'General Ledger')
@section('page_title', 'General Ledger')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('ledger.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
      <div class="form-group" style="margin-bottom:0"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
      <button type="submit" class="btn btn-primary">Filter</button>
    </form>
  </div>
</div>

<div class="grid" style="grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px">
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:22px;font-weight:800;color:var(--success)">Rs. {{ number_format($totalIncome, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Income Collected (Period)</div>
  </div></div>
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:22px;font-weight:800;color:var(--danger)">Rs. {{ number_format($receivables, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Outstanding Receivables</div>
  </div></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Income by Payment Method</div></div>
  <div class="card-body">
    @forelse($byMethod as $method => $amount)
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
      <span>{{ ucfirst(str_replace('_',' ', $method)) }}</span><strong>Rs. {{ number_format($amount, 0) }}</strong>
    </div>
    @empty
    <p style="color:var(--text-muted)">No payments in this period.</p>
    @endforelse
  </div>
</div>

@if($overdueVouchers->isNotEmpty())
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--danger)">
  <div class="card-header"><div class="card-title" style="color:var(--danger)"><i class="fas fa-triangle-exclamation"></i> Overdue Vouchers ({{ $overdueVouchers->count() }})</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Voucher</th><th>Due Date</th><th>Overdue By</th><th>Balance</th><th></th></tr></thead>
      <tbody>
        @foreach($overdueVouchers as $v)
        <tr>
          <td>{{ $v->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $v->student])</td>
          <td>{{ $v->title }} ({{ $v->voucher_no }})</td>
          <td>{{ $v->due_date->format('M d, Y') }}</td>
          <td style="color:var(--danger)">{{ $v->due_date->diffForHumans() }}</td>
          <td style="font-weight:600">Rs. {{ number_format($v->balance(), 0) }}</td>
          <td><a href="{{ route('vouchers.show', $v) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@if($dueSoonVouchers->isNotEmpty())
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--warning)">
  <div class="card-header"><div class="card-title" style="color:var(--warning)"><i class="fas fa-clock"></i> Due Soon — Next {{ \App\Http\Controllers\LedgerController::UPCOMING_WINDOW_DAYS }} Days ({{ $dueSoonVouchers->count() }})</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Voucher</th><th>Due Date</th><th>Due In</th><th>Balance</th><th></th></tr></thead>
      <tbody>
        @foreach($dueSoonVouchers as $v)
        <tr>
          <td>{{ $v->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $v->student])</td>
          <td>{{ $v->title }} ({{ $v->voucher_no }})</td>
          <td>{{ $v->due_date->format('M d, Y') }}</td>
          <td>{{ $v->due_date->diffForHumans() }}</td>
          <td style="font-weight:600">Rs. {{ number_format($v->balance(), 0) }}</td>
          <td><a href="{{ route('vouchers.show', $v) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-days" style="color:var(--primary);margin-right:8px"></i>Monthly Breakdown</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Month</th><th>Billed</th><th>Collected</th><th>Outstanding</th></tr></thead>
      <tbody>
        @forelse($billingByMonth as $row)
        <tr>
          <td style="font-weight:600">{{ $row['label'] }}</td>
          <td>Rs. {{ number_format($row['billed'], 0) }}</td>
          <td style="color:var(--success)">Rs. {{ number_format($row['collected'], 0) }}</td>
          <td style="color:var(--danger);font-weight:600">Rs. {{ number_format($row['outstanding'], 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted)">No billing data for this range.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Transactions (Bank Deposit Reconciliation)</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Student</th><th>Voucher</th><th>Amount</th><th>Method</th><th>Ref.</th></tr></thead>
      <tbody>
        @forelse($payments as $p)
        <tr>
          <td>{{ $p->paid_at->format('M d, Y') }}</td>
          <td>{{ $p->student->name ?? $p->voucher->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $p->student ?? $p->voucher->student ?? null])</td>
          <td>{{ $p->voucher->voucher_no ?? ('Extra receipt CR-'.$p->id) }}</td>
          <td>Rs. {{ number_format($p->amount, 0) }}</td>
          <td>{{ ucfirst(str_replace('_',' ', $p->method)) }}</td>
          <td>{{ $p->reference_no ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted)">No transactions found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
