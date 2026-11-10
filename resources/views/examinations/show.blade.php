@extends('layouts.app')
@section('title', 'Examination Details')
@section('page_title', 'Examination Details')
@section('content')

@php
  $isAdmin = auth()->user()->isAdmin();
  $indexRoute   = $isAdmin ? 'admin.examinations.index'   : 'teacher.examinations.index';
  $editRoute    = $isAdmin ? 'admin.examinations.edit'    : null;
  $resultsRoute = $isAdmin ? 'admin.examinations.results' : 'teacher.examinations.results';
  $showRoute    = $isAdmin ? 'admin.examinations.show'    : 'teacher.examinations.show';
  $statusMap = ['scheduled' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
  $expected = \App\Models\Student::where('school_class_id', $examination->school_class_id)->where('status','active')->count();
  $entered  = $examination->results->count();
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $examination->name }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <span class="crumb program"><i class="fas fa-graduation-cap"></i> {{ $examination->program->name ?? $examination->schoolClass->program->name ?? 'General' }}</span>
      <i class="fas fa-chevron-right"></i>
      <span class="crumb class"><i class="fas fa-users-rectangle"></i> {{ $examination->schoolClass->section_label ?? $examination->schoolClass->name ?? '—' }}</span>
      <i class="fas fa-chevron-right"></i>
      <span class="crumb subject"><i class="fas fa-book"></i> {{ $examination->subject->name ?? 'All Subjects' }}</span>
      <span class="tag">{{ $examination->type_label }}</span>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route($indexRoute) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="{{ route($resultsRoute, $examination) }}" class="btn btn-primary"><i class="fas fa-clipboard-list"></i> {{ $isAdmin ? 'View Marks' : 'Enter Marks' }}</a>
    @if($isAdmin)
      <a href="{{ route('admin.examinations.date-sheet.print', $examination->batch_id ? ['batch_id' => $examination->batch_id] : ['search' => $examination->name, 'school_class_id' => $examination->school_class_id]) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Print Date Sheet</a>
      <a href="{{ route($editRoute, $examination) }}" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
    @endif
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if($errors->has('exam_date'))<div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first('exam_date') }}</div>@endif
@if($isAdmin)
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-days"></i> Change Exam Date</div></div>
  <form action="{{ route('admin.examinations.date.update', $examination) }}" method="POST" class="card-body" style="display:flex;align-items:end;flex-wrap:wrap;gap:12px">
    @csrf @method('PATCH')
    <div class="form-group" style="margin:0;min-width:200px"><label class="form-label" for="exam-reschedule-date">Select new date</label>
      <input id="exam-reschedule-date" class="form-control" type="date" name="exam_date" required value="{{ old('exam_date', $examination->exam_date?->format('Y-m-d')) }}">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save New Date</button>
    <span style="font-size:12px;color:var(--text-muted)">Replaces the previous date for this subject, including on the printed date sheet.</span>
  </form>
</div>
@endif
<div class="grid grid-2" style="gap:20px;align-items:start">
  <div class="card">
    <div class="card-header"><div class="card-title">Overview</div></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px">
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Program</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->program->name ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Class</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->schoolClass->display_name ?? $examination->schoolClass->name ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Subject</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->subject->name ?? 'All Subjects' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Teacher</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->teacher->name ?? 'Unassigned' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Date</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->exam_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Time</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->time_label ?? '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Room</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->room ?: '—' }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Marks</div>
          <div style="font-weight:600;margin-top:2px">{{ $examination->total_marks }} <span style="color:var(--text-muted);font-weight:400">(Pass: {{ $examination->passing_marks }})</span></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Status</div>
          <div style="margin-top:4px">
            <span class="badge {{ $statusMap[$examination->status] ?? 'gray' }}">{{ ucfirst($examination->status) }}</span>
            @if($examination->isLocked())<span class="tag" style="margin-left:6px"><i class="fas fa-lock"></i> Locked</span>@endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Marks Entry Progress</div></div>
    <div class="card-body">
      @php $pct = $expected > 0 ? round(($entered / $expected) * 100) : 0; @endphp
      <div style="text-align:center;padding:10px 0 16px">
        <div style="font-size:34px;font-weight:800;color:var(--primary)">{{ $pct }}%</div>
        <div style="font-size:12.5px;color:var(--text-muted)">{{ $entered }} of {{ $expected }} students marked</div>
      </div>
      <div class="progress" style="margin-bottom:6px"><div class="progress-bar {{ $pct >= 100 ? 'success' : ($pct > 0 ? 'warning' : 'primary') }}" style="width:{{ $pct }}%"></div></div>
      @if(!$isAdmin)
        <div style="margin-top:16px">
          @if($canEnterMarks)
            <a href="{{ route('teacher.examinations.results', $examination) }}" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fas fa-pen"></i> Enter / Update Marks</a>
          @else
            <div class="alert alert-warning" style="margin-bottom:0"><i class="fas fa-lock"></i> {{ $examination->isLocked() ? 'Marks are locked by the admin.' : 'You are not the assigned teacher for this exam.' }}</div>
          @endif
        </div>
      @endif
    </div>
  </div>
</div>

@if($batchSiblings->count())
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title">Other Exams in This Schedule</div>
    <span class="tag">{{ $batchSiblings->count() }} more</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Sr. No.</th><th>Date &amp; Day</th><th>Subject</th><th>Teacher</th></tr></thead>
      <tbody>
        @foreach($batchSiblings->sortBy('exam_date')->values() as $sib)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $sib->exam_date?->format('d M Y, l') ?? '—' }} @if($sib->time_label)<div style="color:var(--text-muted);font-size:11px">{{ $sib->time_label }}</div>@endif</td>
          <td><a href="{{ route($showRoute, $sib) }}">{{ $sib->subject->name ?? '—' }}</a></td>
          <td>{{ $sib->teacher->name ?? 'Unassigned' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card" style="margin-top:20px"><div class="card-header"><div class="card-title">All Subjects in {{ $examination->schoolClass->section_label ?? 'Semester' }}</div></div>
<div class="card-body"><p class="text-sm text-muted">{{ $examination->program->name ?? $examination->schoolClass->program->name ?? '' }} · Session: {{ $examination->session_label }}</p>
<div class="table-wrap"><table><thead><tr><th>Subject</th><th>Assigned Teacher</th></tr></thead><tbody>
@forelse($semesterSubjects as $row)<tr><td>{{ $row->subject->name }}</td><td>{{ $row->teacher->name ?? 'Unassigned' }}</td></tr>
@empty<tr><td colspan="2">No subjects assigned to this semester.</td></tr>@endforelse
</tbody></table></div></div></div>
@endsection
