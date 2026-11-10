<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Collection (Paid) Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
<style>@media print { @page { size: landscape; margin: 8mm; } } .sheet { max-width:1400px; }</style>
</head>
<body>
<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>
@php
  $periodLabel = match($period) {
    'monthly' => 'Monthly — '.$from->format('F Y'),
    'yearly' => 'Yearly — '.$from->format('Y'),
    'range' => 'Custom Range — '.$from->format('d M Y').' to '.$to->format('d M Y'),
    default => 'All Time',
  };
@endphp
<div class="sheet"><div class="sheet-inner">
@include('admin.reporting.partials.report-letterhead', [
  'reportTitle' => 'Fee Collection (Paid) Report',
  'reportSubtitle' => ($program->name ?? 'All Programs').' — '.($semester->section_label ?? 'All Semesters').' — '.$periodLabel,
  'metaLines' => [
    ['Program', $program->name ?? 'Overall (All Programs)'],
    ['Semester / Part', $semester->section_label ?? 'All Semesters'],
    ['Status', ucfirst($feeStatus)],
    ['Name / Roll No. / ID', $search ?: 'None'],
    ['Period', $periodLabel],
    ['Rows', $rows->count()],
  ],
])
<div class="stat-strip">
  <div class="stat"><div class="num">{{ $rows->count() }}</div><div class="lbl">Fee Plans</div></div>
  <div class="stat"><div class="num">{{ number_format($totalFee, 0) }}</div><div class="lbl">Total Fee (Rs.)</div></div>
  <div class="stat"><div class="num">{{ number_format($totalPaid, 0) }}</div><div class="lbl">Total Paid (Rs.)</div></div>
  <div class="stat"><div class="num">{{ number_format($totalPending, 0) }}</div><div class="lbl">Total Pending (Rs.)</div></div>
  <div class="stat"><div class="num">{{ number_format($totalOutstanding, 0) }}</div><div class="lbl">Outstandings (Rs.)</div></div>
</div>
@include('admin.reporting.partials.fee-plan-print-table')
<div class="foot-row">
  <div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div>
  <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
  <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
</div>
<div class="disclaimer">This is a system-generated report from the Reporting Center — for internal administrative use only.</div>
</div></div></body></html>