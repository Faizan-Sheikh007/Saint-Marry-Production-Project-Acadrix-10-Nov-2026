@extends('layouts.app')
@section('title', 'Ledger - ' . $student->name)
@section('page_title', 'Student Ledger')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('ledger.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<div class="grid" style="grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:20px;font-weight:800">Rs. {{ number_format($totalCharged, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Charged</div>
  </div></div>
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:20px;font-weight:800;color:var(--success)">Rs. {{ number_format($totalPaid, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Paid</div>
  </div></div>
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:20px;font-weight:800;color:var(--danger)">Rs. {{ number_format($totalCharged - $totalPaid, 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Outstanding Balance</div>
  </div></div>
</div>

@if($overdueVouchers->isNotEmpty())
<div class="card" style="margin-bottom:16px;border-left:4px solid var(--danger)">
  <div class="card-body">
    <div style="font-weight:700;color:var(--danger);margin-bottom:6px"><i class="fas fa-triangle-exclamation"></i> Overdue ({{ $overdueVouchers->count() }})</div>
    @foreach($overdueVouchers as $v)
    <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0">
      <span>{{ $v->title }} ({{ $v->voucher_no }}) &middot; due {{ $v->due_date->format('M d, Y') }} &middot; {{ $v->due_date->diffForHumans() }}</span>
      <strong style="color:var(--danger)">Rs. {{ number_format($v->balance(), 0) }}</strong>
    </div>
    @endforeach
  </div>
</div>
@endif

@if($dueSoonVouchers->isNotEmpty())
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--warning)">
  <div class="card-body">
    <div style="font-weight:700;color:var(--warning);margin-bottom:6px"><i class="fas fa-clock"></i> Due Soon (next 7 days)</div>
    @foreach($dueSoonVouchers as $v)
    <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0">
      <span>{{ $v->title }} ({{ $v->voucher_no }}) &middot; due {{ $v->due_date->format('M d, Y') }} &middot; {{ $v->due_date->diffForHumans() }}</span>
      <strong>Rs. {{ number_format($v->balance(), 0) }}</strong>
    </div>
    @endforeach
  </div>
</div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-days" style="color:var(--primary);margin-right:8px"></i>Monthly Summary</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Month</th><th>Charged</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($monthlySummary as $row)
        <tr>
          <td style="font-weight:600">{{ $row['label'] }}</td>
          <td>Rs. {{ number_format($row['charged'], 0) }}</td>
          <td style="color:var(--success)">Rs. {{ number_format($row['paid'], 0) }}</td>
          <td style="font-weight:600">Rs. {{ number_format($row['charged'] - $row['paid'], 0) }}</td>
          <td>{{ $row['due_date'] ? $row['due_date']->format('M d, Y') : '—' }}</td>
          <td>
            @if($row['overdue'])
              <span class="badge danger">Overdue</span>
            @elseif($row['due_soon'])
              <span class="badge warning">Due Soon</span>
            @elseif($row['charged'] <= $row['paid'])
              <span class="badge success">Settled</span>
            @else
              <span class="badge gray">Upcoming</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">No months to summarize yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">{{ $student->name }} — {{ $student->student_id }}</div>@include('partials.report-student-status', ['statusStudent' => $student])</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Due Date</th><th>Debit (Charge)</th><th>Credit (Payment)</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($entries as $e)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($e['date'])->format('M d, Y') }}</td>
          <td>{{ $e['description'] }}</td>
          <td>{{ $e['due_date'] ? $e['due_date']->format('M d, Y') : '—' }}</td>
          <td>{{ $e['debit'] > 0 ? 'Rs. '.number_format($e['debit'],0) : '—' }}</td>
          <td style="color:var(--success)">{{ $e['credit'] > 0 ? 'Rs. '.number_format($e['credit'],0) : '—' }}</td>
          <td style="font-weight:600">Rs. {{ number_format($e['balance'], 0) }}</td>
          <td>
            @if($e['type'] === 'charge')
              @php $s = ['paid'=>'success','partial'=>'warning','overdue'=>'danger','unpaid'=>'gray','cancelled'=>'gray'][$e['status']] ?? 'gray'; @endphp
              <span class="badge {{ $s }}">{{ ucfirst($e['status']) }}</span>
            @else
              <span class="badge success">Received</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No ledger entries yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
