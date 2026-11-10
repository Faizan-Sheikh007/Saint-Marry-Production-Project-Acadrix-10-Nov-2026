@extends('layouts.app')
@section('title', 'Scholarship & Financial Aid Report')
@section('page_title', 'Scholarship & Financial Aid Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $reportRoute = $accountsReport ? 'accounts.reporting.scholarship' : 'admin.reporting.scholarship';
  $printRoute = $accountsReport ? 'accounts.reporting.scholarship.print' : 'admin.reporting.scholarship.print';
  $studentProfileRoute = $accountsReport ? 'accounts.students.show' : 'students.show';
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Reporting Center</a>
  <a href="{{ route($printRoute, request()->query()) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print / Download PDF</a>
</div>

@include('admin.reporting.partials.filter-form', [
  'action' => route($reportRoute),
  'programs' => $programs, 'programId' => $programId, 'period' => $period, 'from' => $from, 'to' => $to,
  'showSearch' => true, 'search' => $search,
  'showAcademicYear' => true, 'academicYears' => $academicYears, 'academicYearId' => $academicYearId,
])

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:20px">
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800">{{ $summary['students'] }}</div><div style="font-size:12px;color:var(--text-muted)">Students with Relief</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--success)">Rs. {{ number_format($summary['total_scholarship'], 0) }}</div><div style="font-size:12px;color:var(--text-muted)">Total Scholarship</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--warning)">Rs. {{ number_format($summary['total_financial_aid'], 0) }}</div><div style="font-size:12px;color:var(--text-muted)">Total Financial Aid</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--primary)">Rs. {{ number_format($summary['total_relief'], 0) }}</div><div style="font-size:12px;color:var(--text-muted)">Combined Relief</div></div></div>
  <div class="stat-card"><div><div style="font-size:22px;font-weight:800;color:var(--info)">{{ $summary['awaiting_plan'] }}</div><div style="font-size:12px;color:var(--text-muted)">Awaiting Fee Plan</div></div></div>
</div>

@if($programId === 'all' && $byProgram->count())
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Program-wise Breakdown</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Program</th><th>Students</th><th>Scholarship</th><th>Financial Aid</th><th>Total Relief</th></tr></thead>
      <tbody>
        @foreach($byProgram as $name => $p)
        <tr>
          <td>{{ $name }}</td>
          <td>{{ $p['students'] }}</td>
          <td>Rs. {{ number_format($p['scholarship'], 0) }}</td>
          <td>Rs. {{ number_format($p['financial_aid'], 0) }}</td>
          <td style="font-weight:800">Rs. {{ number_format($p['total_relief'], 0) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div class="card-title">Student Scholarship &amp; Financial Aid Detail</div>
    @if($search)
      <div style="font-size:12px;color:var(--text-muted)">Filtered for: <strong>{{ $search }}</strong></div>
    @endif
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>SR #</th><th>Student ID</th><th>Student</th><th>Program</th><th>Semester/Part</th><th>Cycle</th>
          <th>Net Payable</th><th>Scholarship</th><th>Financial Aid</th><th>Total Relief</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $i => $r)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td style="font-weight:700">{{ $r['student']->student_id ?? '—' }}</td>
          <td>
            <div style="font-weight:700">{{ $r['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r['student']])</div>
            <div style="font-size:11px;color:var(--text-muted)">{{ $r['student']->father_name ?? '—' }}</div>
          </td>
          <td>{{ $r['program'] }}</td>
          <td>{{ $r['student']->schoolClass->name ?? '—' }}</td>
          <td>{{ $r['cycle'] }}</td>
          <td>{{ !is_null($r['total']) ? 'Rs. '.number_format($r['total'], 0) : '—' }}</td>
          <td style="font-weight:800;color:var(--success)">{{ $r['status'] === 'Allocated' ? 'Rs. '.number_format($r['scholarship_amount'], 0) : '—' }}</td>
          <td style="font-weight:800;color:var(--warning)">{{ $r['status'] === 'Allocated' ? 'Rs. '.number_format($r['financial_aid_amount'], 0) : '—' }}</td>
          <td style="font-weight:800;color:var(--primary)">{{ $r['status'] === 'Allocated' ? 'Rs. '.number_format($r['relief_total'], 0) : '—' }}</td>
          <td>
            @if($r['status'] === 'Allocated')
              <span class="badge badge-success">Allocated</span>
            @else
              <span class="badge badge-info" title="{{ $r['type'] }}{{ $r['notes'] ? ' — '.$r['notes'] : '' }}">Awaiting Fee Plan</span>
            @endif
          </td>
          <td><a href="{{ route($studentProfileRoute, $r['student']) }}" class="btn btn-outline btn-sm"><i class="fas fa-user"></i> Profile</a></td>
        </tr>
        @empty
        <tr><td colspan="12" style="text-align:center;padding:30px;color:var(--text-muted)">No scholarship or financial-aid records found for the selected student/filter.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
