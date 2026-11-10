@extends('layouts.app')
@section('title', 'Student Report')
@section('page_title', 'Student Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $studentsRoute = $accountsReport ? 'accounts.reports.students' : 'admin.reports.students';
  $studentShowRoute = $accountsReport ? 'accounts.reports.students.show' : 'admin.reports.students.show';
  $studentPdfRoute = $accountsReport ? 'accounts.reports.students.pdf' : 'admin.reports.students.pdf';
  $exportRoute = $accountsReport ? 'reports.export' : 'admin.reports.export';
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div>
      <div style="font-size:22px;font-weight:800">Student Report</div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Academic performance, attendance and fee health in one report.</div>
    </div>
  </div>
  <a href="{{ route($exportRoute, array_merge(['type' => 'students'], request()->query())) }}" class="btn btn-outline">
    <i class="fas fa-file-csv"></i> Export CSV
  </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:22px">
  @foreach([
    ['Total Students', $totalStudents, 'fas fa-users', 'var(--primary)'],
    ['Active Students', $activeStudents, 'fas fa-user-check', 'var(--success)'],
    ['Fee Clear', $feeClearStudents, 'fas fa-circle-check', '#14804a'],
    ['Fee Pending / Overdue', $feePendingStudents, 'fas fa-file-invoice-dollar', 'var(--danger)'],
  ] as [$label,$value,$icon,$color])
    <div class="card"><div class="card-body" style="padding:18px;display:flex;gap:12px;align-items:center">
      <div style="width:42px;height:42px;border-radius:11px;background:#f3f0ff;display:flex;align-items:center;justify-content:center;color:{{ $color }}"><i class="{{ $icon }}"></i></div>
      <div><div style="font-size:24px;font-weight:800;color:{{ $color }}">{{ $value }}</div><div style="font-size:11px;color:var(--text-muted)">{{ $label }}</div></div>
    </div></div>
  @endforeach
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:grid;grid-template-columns:minmax(220px,2fr) repeat(6,minmax(125px,1fr)) auto auto;gap:10px;align-items:end" data-ajax-filter>
      <div class="form-group" style="margin:0">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name, ID, roll no, father, guardian, phone..." value="{{ request('search') }}">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-control">
          <option value="">All Programs</option>
          @foreach($programs as $program)<option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>@endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Semester/Part</label>
        <select name="class_id" class="form-control">
          <option value="">All</option>
          @foreach($classes as $class)<option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->display_name }}</option>@endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Academic Year</label>
        <select name="academic_year_id" class="form-control">
          <option value="">All Years</option>
          @foreach($academicYears as $year)<option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Student Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          @foreach(\App\Support\StudentStatus::VALUES as $status)
            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-control">
          <option value="">All</option>
          <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Male</option>
          <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Female</option>
          <option value="other" {{ request('gender') === 'other' ? 'selected' : '' }}>Other</option>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Fee Status</label>
        <select name="fee_status" class="form-control">
          <option value="">All</option>
          <option value="clear" {{ request('fee_status') === 'clear' ? 'selected' : '' }}>Clear</option>
          <option value="pending" {{ request('fee_status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="overdue" {{ request('fee_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route($studentsRoute) }}" class="btn btn-outline" data-ajax-filter-link><i class="fas fa-times"></i> Reset</a>
      </div>
    </form>
  </div>
</div>
<style>@media(max-width:1300px){form[data-ajax-filter]{grid-template-columns:repeat(3,minmax(150px,1fr))!important}} @media(max-width:760px){form[data-ajax-filter]{grid-template-columns:1fr!important}}</style>

<div class="card">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);font-weight:700">{{ $students->total() }} student(s) found</div>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>#</th><th>Student</th><th>Program / Semester</th><th>Academic Year</th><th>Attendance</th><th>Exam Performance</th><th>Fee Status</th><th>Paid Fee</th><th>Pending Fee</th><th>Student Status</th><th>Report</th>
      </tr></thead>
      <tbody>
        @forelse($students as $student)
          @php
            $attendancePct = $student->attendance_total_count > 0 ? round(($student->attendance_present_count / $student->attendance_total_count) * 100, 1) : 0;
            $pending = (float) $student->pendingDues();
            $paid = (float) $student->totalPaid();
            $feeLabel = $pending <= 0 ? 'Clear' : ($paid > 0 ? 'Partial' : 'Pending');
            $feeBadge = $pending <= 0 ? 'success' : ($paid > 0 ? 'warning' : 'danger');
            $attendanceColor = $attendancePct >= 90 ? 'var(--success)' : ($attendancePct >= 75 ? 'var(--warning)' : 'var(--danger)');
          @endphp
          <tr>
            <td style="color:var(--text-muted);font-weight:600">{{ ($students->currentPage()-1)*$students->perPage()+$loop->iteration }}</td>
            <td style="min-width:190px">
              <div style="display:flex;align-items:center;gap:10px">
                @if($student->photo)
                  <img src="{{ storage_asset($student->photo) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
                @else
                  <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:800">{{ strtoupper(substr($student->name,0,2)) }}</div>
                @endif
                <div><div style="font-weight:750">{{ $student->name }} @include('partials.student-status-badge', ['statusStudent' => $student])</div><div style="font-size:11px;color:var(--text-muted)">{{ $student->student_id }}{{ $student->roll_number ? ' • Roll '.$student->roll_number : '' }}</div></div>
              </div>
            </td>
            <td><div style="font-weight:600">{{ $student->program->name ?? '—' }}</div><div style="font-size:11px;color:var(--text-muted)">{{ $student->schoolClass->section_label ?? '—' }}</div></td>
            <td>{{ $student->academicYear->name ?? '—' }}</td>
            <td><div style="font-weight:800;color:{{ $attendanceColor }}">{{ $attendancePct }}%</div><div style="font-size:10px;color:var(--text-muted)">{{ $student->attendance_present_count }}/{{ $student->attendance_total_count }} present</div></td>
            <td><div style="font-weight:800">{{ number_format((float)$student->exam_average,1) }}%</div><div style="font-size:10px;color:var(--text-muted)">{{ $student->exam_results_count }} exam result(s)</div></td>
            <td><span class="badge {{ $feeBadge }}">{{ $feeLabel }}</span></td>
            <td style="font-weight:700;color:var(--success);white-space:nowrap">Rs. {{ number_format($paid,0) }}</td>
            <td style="font-weight:800;color:{{ $pending > 0 ? 'var(--danger)' : 'var(--success)' }};white-space:nowrap">Rs. {{ number_format($pending,0) }}</td>
            <td><span class="badge {{ $student->statusBadge() }}">{{ $student->statusLabel() }}</span></td>
            <td style="white-space:nowrap">
              <a href="{{ route($studentShowRoute, $student) }}" class="btn btn-outline btn-sm" title="View complete report"><i class="fas fa-eye"></i></a>
              <a href="{{ route($studentPdfRoute, $student) }}" class="btn btn-outline btn-sm" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="11" style="text-align:center;padding:44px;color:var(--text-muted)"><i class="fas fa-user-graduate" style="display:block;font-size:28px;opacity:.3;margin-bottom:8px"></i>No students match these filters.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $students->links() }}</div>
</div>
@endsection
