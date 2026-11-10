@extends('layouts.app')
@section('title', 'Daily Ledger — Weekly')
@section('page_title', 'Daily Ledger — Weekly Report')
@section('content')

@php $prefix = auth()->user()->isAdmin() ? 'admin.' : ''; @endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800">Weekly Report — {{ $from->format('M d') }} to {{ $to->format('M d, Y') }}</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px" data-ajax-filter>
      <input type="date" name="date" class="form-control" value="{{ request('date', now()->format('Y-m-d')) }}" onchange="this.form.requestSubmit()">
    </form>
    <a href="{{ route($prefix.'daily-ledger.index') }}" class="btn btn-outline"><i class="fas fa-calendar-day"></i> Daily View</a>
    <a href="{{ route($prefix.'daily-ledger.print.weekly', request()->query()) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Export PDF</a>
  </div>
</div>

<div class="grid grid-3" style="margin-bottom:20px">
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800;color:var(--success,#16a34a)">Rs. {{ number_format($totals['in'], 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total In</div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800;color:var(--danger)">Rs. {{ number_format($totals['out'], 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Out</div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:20px;font-weight:800;color:var(--danger)">Rs. {{ number_format($totals['expense'], 0) }}</div>
    <div style="font-size:12px;color:var(--text-muted)">Total Expense</div>
  </div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Day-by-Day Breakdown</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Expense</th><th>Net</th><th></th></tr></thead>
      <tbody>
        @foreach($days as $d)
        <tr>
          <td>{{ $d['date']->format('D, M d') }}</td>
          <td>Rs. {{ number_format($d['in'], 0) }}</td>
          <td>Rs. {{ number_format($d['out'], 0) }}</td>
          <td>Rs. {{ number_format($d['expense'], 0) }}</td>
          <td>Rs. {{ number_format($d['in'] - $d['out'] - $d['expense'], 0) }}</td>
          <td><a href="{{ route($prefix.'daily-ledger.index', ['date' => $d['date']->format('Y-m-d')]) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr style="font-weight:700">
          <td>Total</td>
          <td>Rs. {{ number_format($totals['in'], 0) }}</td>
          <td>Rs. {{ number_format($totals['out'], 0) }}</td>
          <td>Rs. {{ number_format($totals['expense'], 0) }}</td>
          <td>Rs. {{ number_format($totals['in'] - $totals['out'] - $totals['expense'], 0) }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@endsection
