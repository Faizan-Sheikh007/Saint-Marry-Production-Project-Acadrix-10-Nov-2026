@extends('layouts.app')
@section('title', 'Pending Fee Report')
@section('page_title', 'Pending / Outstanding Fee Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $reportRoute = $accountsReport ? 'accounts.reporting.fee-pending' : 'admin.reporting.fee-pending';
  $printRoute = $accountsReport ? 'accounts.reporting.fee-pending.print' : 'admin.reporting.fee-pending.print';
@endphp

<div class="fee-report-actions">
  <a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Reporting Center</a>
  <a href="{{ route($printRoute, request()->query()) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print / Download PDF</a>
</div>

@include('admin.reporting.partials.filter-form', [
  'action' => route($reportRoute),
  'programs' => $programs, 'programId' => $programId,
  'semesters' => $semesters, 'semesterId' => $semesterId,
  'feeStatus' => $feeStatus,
  'period' => $period, 'from' => $from, 'to' => $to,
  'showSearch' => true, 'search' => $search,
  'showSemester' => true, 'showFeeStatus' => true,
])

<div class="fee-report-summary">
  <div class="fee-kpi">
    <div class="fee-kpi-icon"><i class="fas fa-layer-group"></i></div>
    <div><div class="fee-kpi-value">{{ $rows->count() }}</div><div class="fee-kpi-label">Fee Plans</div></div>
  </div>
  <div class="fee-kpi">
    <div class="fee-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
    <div><div class="fee-kpi-value">Rs. {{ number_format($totalFee, 0) }}</div><div class="fee-kpi-label">Total Fee</div></div>
  </div>
  <div class="fee-kpi is-paid">
    <div class="fee-kpi-icon"><i class="fas fa-circle-check"></i></div>
    <div><div class="fee-kpi-value">Rs. {{ number_format($totalPaid, 0) }}</div><div class="fee-kpi-label">Total Paid</div></div>
  </div>
  <div class="fee-kpi is-pending">
    <div class="fee-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
    <div><div class="fee-kpi-value">Rs. {{ number_format($totalPending, 0) }}</div><div class="fee-kpi-label">Total Pending</div></div>
  </div>
  <div class="fee-kpi is-overdue">
    <div class="fee-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
    <div><div class="fee-kpi-value">Rs. {{ number_format($totalOutstanding, 0) }}</div><div class="fee-kpi-label">Overdue Outstandings</div></div>
  </div>
</div>

@include('admin.reporting.partials.fee-plan-table', ['tableTitle' => 'Pending Fee Detail'])
@endsection
