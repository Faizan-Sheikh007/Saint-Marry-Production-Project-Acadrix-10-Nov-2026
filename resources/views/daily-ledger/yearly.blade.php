@extends('layouts.app')
@section('title', 'Daily Ledger — Yearly')
@section('page_title', 'Daily Ledger — Yearly Report')
@section('content')

@php $prefix = auth()->user()->isAdmin() ? 'admin.' : ''; @endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800">Yearly Report — {{ $year }}</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px" data-ajax-filter>
      <select name="year" class="form-control" onchange="this.form.requestSubmit()">
        @foreach(range(now()->year, now()->year - 5) as $y)
          <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
        @endforeach
      </select>
    </form>
    <a href="{{ route($prefix.'daily-ledger.index') }}" class="btn btn-outline"><i class="fas fa-calendar-day"></i> Daily View</a>
    <a href="{{ route($prefix.'daily-ledger.print.yearly', ['year' => $year]) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Export PDF</a>
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
  <div class="card-header"><div class="card-title">Month-by-Month Breakdown</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Month</th><th>In</th><th>Out</th><th>Expense</th><th>Net</th></tr></thead>
      <tbody>
        @foreach($months as $m)
        <tr>
          <td>{{ $m['label'] }}</td>
          <td>Rs. {{ number_format($m['in'], 0) }}</td>
          <td>Rs. {{ number_format($m['out'], 0) }}</td>
          <td>Rs. {{ number_format($m['expense'], 0) }}</td>
          <td>Rs. {{ number_format($m['in'] - $m['out'] - $m['expense'], 0) }}</td>
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
        </tr>
      </tfoot>
    </table>
  </div>
</div>

@endsection
