@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page_title', 'Attendance Report — Monthly / Yearly')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $reportRoute = $accountsReport ? 'accounts.reporting.attendance-summary' : 'admin.reporting.attendance-summary';
  $printRoute = $accountsReport ? 'accounts.reporting.attendance-summary.print' : 'admin.reporting.attendance-summary.print';
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Reporting Center</a>
  <a href="{{ route($printRoute, request()->query()) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print / Download PDF</a>
</div>

@include('admin.reporting.partials.filter-form', [
  'action' => route($reportRoute),
  'programs' => $programs, 'programId' => $programId, 'period' => $period, 'from' => $from, 'to' => $to,
  'showSearch' => false,
])

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px">
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800">{{ $overall['total'] }}</div><div style="font-size:12px;color:var(--text-muted)">Total Records</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--success)">{{ $overall['present'] }}</div><div style="font-size:12px;color:var(--text-muted)">Present</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--danger)">{{ $overall['absent'] }}</div><div style="font-size:12px;color:var(--text-muted)">Absent</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800">{{ $overall['pct'] }}%</div><div style="font-size:12px;color:var(--text-muted)">Attendance Rate</div></div></div>
</div>

@if($programId === 'all' && $byProgram->count())
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Program-wise Attendance</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Program</th><th>Total Records</th><th>Present</th><th>Attendance %</th></tr></thead>
      <tbody>
        @foreach($byProgram as $name => $p)
        <tr><td>{{ $name }}</td><td>{{ $p['total'] }}</td><td>{{ $p['present'] }}</td><td>{{ $p['pct'] }}%</td></tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card">
  <div class="card-header"><div class="card-title">Student-wise Attendance</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Semester/Part</th><th>Academic Year</th><th>Total Days</th><th>Present</th><th>Absent</th><th>Leave</th><th>%</th></tr></thead>
      <tbody>
        @forelse($perStudent as $r)
        <tr>
          <td>{{ $r['student']->student_id ?? '—' }}</td>
          <td>{{ $r['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r['student']])</td>
          <td>{{ $r['student']->program->name ?? '—' }}</td>
          <td>{{ $r['student']->schoolClass->name ?? '—' }}</td>
          <td>{{ $r['student']->academicYear->name ?? '—' }}</td>
          <td>{{ $r['total'] }}</td>
          <td>{{ $r['present'] }}</td>
          <td>{{ $r['absent'] }}</td>
          <td>{{ $r['leave'] }}</td>
          <td><span class="badge badge-{{ $r['pct'] >= 75 ? 'success' : 'danger' }}">{{ $r['pct'] }}%</span></td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--text-muted)">No attendance records found for the selected filters.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
