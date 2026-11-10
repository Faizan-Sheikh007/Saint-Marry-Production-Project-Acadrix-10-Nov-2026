@extends('layouts.app')
@section('title', 'Student Status Report')
@section('page_title', 'Student Status Report')
@section('content')
@php
  $baseQuery = request()->except(['status','page']);
  $selectedLabel = $selectedStatus ? \App\Support\StudentStatus::label($selectedStatus) : 'All Statuses';
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div>
      <div style="font-size:22px;font-weight:800">Student Status Report</div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Admin report for active, dropped, terminated, left and all other enrollment statuses.</div>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="{{ route('admin.reports.export', array_merge(['type'=>'students'], request()->query())) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Export CSV</a>
    <a href="{{ route('admin.reports.student-status.print', request()->query()) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Print / Save PDF</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px;margin-bottom:20px">
  @foreach(\App\Support\StudentStatus::VALUES as $status)
    @php $active = $selectedStatus === $status; @endphp
    <a href="{{ route('admin.reports.student-status', array_merge($baseQuery, ['status'=>$status])) }}" class="card" style="text-decoration:none;color:inherit;border:{{ $active ? '2px solid var(--primary)' : '1px solid var(--border)' }}">
      <div class="card-body" style="padding:15px 16px">
        <div style="font-size:22px;font-weight:850;color:{{ in_array($status,['dropped','terminated','left']) ? 'var(--danger)' : 'var(--primary)' }}">{{ $statusCounts[$status] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ \App\Support\StudentStatus::label($status) }}</div>
      </div>
    </a>
  @endforeach
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:grid;grid-template-columns:minmax(220px,2fr) repeat(5,minmax(135px,1fr)) auto;gap:10px;align-items:end" data-ajax-filter>
      <div class="form-group" style="margin:0"><label class="form-label">Search</label><input type="text" name="search" class="form-control" placeholder="Name, ID, roll no, father, phone..." value="{{ request('search') }}"></div>
      <div class="form-group" style="margin:0"><label class="form-label">Student Status</label><select name="status" class="form-control"><option value="">All Statuses</option>@foreach(\App\Support\StudentStatus::VALUES as $status)<option value="{{ $status }}" {{ request('status')===$status?'selected':'' }}>{{ \App\Support\StudentStatus::label($status) }}</option>@endforeach</select></div>
      <div class="form-group" style="margin:0"><label class="form-label">Program</label><select name="program_id" class="form-control"><option value="">All Programs</option>@foreach($programs as $program)<option value="{{ $program->id }}" {{ request('program_id')==$program->id?'selected':'' }}>{{ $program->name }}</option>@endforeach</select></div>
      <div class="form-group" style="margin:0"><label class="form-label">Semester/Part</label><select name="class_id" class="form-control"><option value="">All</option>@foreach($classes as $class)<option value="{{ $class->id }}" {{ request('class_id')==$class->id?'selected':'' }}>{{ $class->display_name }}</option>@endforeach</select></div>
      <div class="form-group" style="margin:0"><label class="form-label">Academic Year</label><select name="academic_year_id" class="form-control"><option value="">All Years</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" {{ request('academic_year_id')==$year->id?'selected':'' }}>{{ $year->name }}</option>@endforeach</select></div>
      <div class="form-group" style="margin:0"><label class="form-label">Gender</label><select name="gender" class="form-control"><option value="">All</option><option value="male" {{ request('gender')==='male'?'selected':'' }}>Male</option><option value="female" {{ request('gender')==='female'?'selected':'' }}>Female</option><option value="other" {{ request('gender')==='other'?'selected':'' }}>Other</option></select></div>
      <div style="display:flex;gap:8px"><button class="btn btn-primary"><i class="fas fa-filter"></i> Generate</button><a href="{{ route('admin.reports.student-status') }}" class="btn btn-outline"><i class="fas fa-times"></i> Reset</a></div>
    </form>
  </div>
</div>
<style>@media(max-width:1250px){form[data-ajax-filter]{grid-template-columns:repeat(3,minmax(150px,1fr))!important}} @media(max-width:760px){form[data-ajax-filter]{grid-template-columns:1fr!important}}</style>

<div class="card">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
    <strong>{{ $selectedLabel }} — {{ $students->total() }} student(s)</strong>
    <span style="font-size:11px;color:var(--text-muted)">Generated {{ now()->format('d M Y, h:i A') }}</span>
  </div>
  <div class="table-wrap"><table><thead><tr><th>#</th><th>Student ID</th><th>Student</th><th>Father Name</th><th>Program</th><th>Semester/Part</th><th>Academic Year</th><th>Phone</th><th>Admission Date</th><th>Status</th></tr></thead><tbody>
    @forelse($students as $student)
      <tr>
        <td>{{ ($students->currentPage()-1)*$students->perPage()+$loop->iteration }}</td>
        <td style="font-weight:700">{{ $student->student_id ?: '—' }}</td>
        <td><a href="{{ route('admin.reports.students.show',$student) }}" style="font-weight:750;color:inherit">{{ $student->name }}</a></td>
        <td>{{ $student->father_name ?: '—' }}</td>
        <td>{{ $student->program->name ?? '—' }}</td>
        <td>{{ $student->schoolClass->section_label ?? '—' }}</td>
        <td>{{ $student->academicYear->name ?? '—' }}</td>
        <td>{{ $student->phone ?: '—' }}</td>
        <td>{{ $student->admission_date?->format('d M Y') ?? '—' }}</td>
        <td><span class="badge {{ $student->statusBadge() }}">{{ $student->statusLabel() }}</span></td>
      </tr>
    @empty
      <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-muted)">No students found for the selected status and filters.</td></tr>
    @endforelse
  </tbody></table></div>
  <div style="padding:16px 20px">{{ $students->links() }}</div>
</div>
@endsection
