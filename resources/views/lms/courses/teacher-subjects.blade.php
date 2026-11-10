@extends('layouts.app')
@section('title', 'My Subjects')
@section('page_title', 'My Subjects')
@section('content')
<style>
  .subject-list {display:grid;gap:18px}
  .subject-panel {border:1px solid var(--border, #dce3ea);border-radius:12px;background:var(--card-bg, #fff);overflow:hidden}
  .subject-panel-top {padding:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .subject-panel h3 {margin:0;font-size:1.15rem}
  .subject-meta {display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
  .subject-meta span {background:var(--bg, #f3f6fa);border-radius:6px;padding:6px 9px;font-size:.86rem}
  .subject-panel-body {padding:0 18px 18px}
  .subject-table-wrap {width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
  .subject-table {width:100%;border-collapse:collapse;white-space:nowrap;font-size:.9rem}
  .subject-table th,.subject-table td {text-align:left;padding:10px 12px;border-bottom:1px solid var(--border,#e2e8f0)}
  .subject-table th {background:var(--bg,#f6f8fb);font-weight:600}
  .student-toggle {cursor:pointer;display:flex;gap:8px;align-items:center;padding:12px 0;font-weight:600}
  .student-toggle::marker {color:var(--primary,#2563eb)}
  .empty-note {padding:14px;color:var(--muted,#64748b)}
  @media(max-width:640px){.subject-panel-top,.subject-panel-body{padding:12px}.subject-meta{gap:6px}.subject-meta span{font-size:.8rem}.subject-table{font-size:.8rem}}
  @media print {.subject-list{display:block}.subject-panel{break-inside:avoid;margin-bottom:14px}details>div{display:block!important}.subject-table-wrap{overflow:visible}}
</style>
<div class="page-header">
 <div>
  <div class="page-title"><i class="fas fa-book-open" style="color:var(--primary)"></i> My Subjects</div>
  <div class="page-subtitle">Only subjects assigned to you by Admin, with scheduled classes and enrolled students.</div>
 </div>
</div>
@if($assignments->isEmpty())
 <div class="card"><div class="empty-state"><i class="fas fa-book-open"></i><p>No subjects have been allotted to you by Admin yet.</p></div></div>
@else
 <div class="subject-list">
 @foreach($assignments as $assignment)
 @php
   $class = $assignment->schoolClass;
   $students = $studentsByClass->get($assignment->school_class_id, collect());
   $sessions = $timetableByAssignment->get($assignment->school_class_id.':'.$assignment->subject_id, collect());
 @endphp
 <section class="subject-panel">
  <div class="subject-panel-top">
   <div>
    <h3>{{ $assignment->subject->name ?? 'Subject unavailable' }} @if($assignment->subject?->code)<small>({{ $assignment->subject->code }})</small>@endif</h3>
    <div class="subject-meta">
     <span><strong>Program:</strong> {{ $class?->program?->name ?? 'Not assigned' }}</span>
     <span><strong>Semester / Part:</strong> {{ $class?->name ?? 'Not assigned' }}{{ $class?->section ? ' ('.$class->section.')' : '' }}</span>
     <span><strong>Students:</strong> {{ $students->count() }}</span>
    </div>
   </div>
  </div>
  <div class="subject-panel-body">
   @php $subjectCourse = $coursesByAssignment->get($assignment->school_class_id.':'.$assignment->subject_id); @endphp
   @if($subjectCourse)
   <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
     <a class="btn btn-outline btn-sm" href="{{ route('lms.courses.show', $subjectCourse) }}">Open Subject</a>
     <a class="btn btn-primary btn-sm" href="{{ route('lms.assignments.create', $subjectCourse) }}">Assign Work to Students</a>
     <a class="btn btn-primary btn-sm" href="{{ route('lms.quizzes.create', $subjectCourse) }}">Create Student Quiz</a>
   </div>
   @endif
   <h4 style="margin:4px 0 10px">Class Schedule</h4>
   @if($sessions->isEmpty())
    <div class="empty-note">Day and time have not been scheduled by Admin yet.</div>
   @else
    <div class="subject-table-wrap"><table class="subject-table">
     <thead><tr><th>Day</th><th>Start Time</th><th>End Time</th><th>Room</th></tr></thead>
     <tbody>@foreach($sessions as $session)
      <tr><td>{{ $session->day }}</td><td>{{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }}</td><td>{{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }}</td><td>{{ $session->room ?: '—' }}</td></tr>
     @endforeach</tbody>
    </table></div>
   @endif
   <details style="margin-top:14px">
    <summary class="student-toggle">Students in this class ({{ $students->count() }})</summary>
    <div>
    @if($students->isEmpty())
     <div class="empty-note">No active students enrolled in this class.</div>
    @else
     <div class="subject-table-wrap"><table class="subject-table">
      <thead><tr><th>#</th><th>Student Name</th><th>Roll No.</th><th>Student ID</th><th>Father's Name</th><th>Phone</th><th>Email</th><th>Shift</th><th>Session</th></tr></thead>
      <tbody>@foreach($students as $student)
       <tr><td>{{ $loop->iteration }}</td><td>{{ $student->name }}</td><td>{{ $student->roll_number ?: '—' }}</td><td>{{ $student->student_id ?: '—' }}</td><td>{{ $student->father_name ?: '—' }}</td><td>{{ $student->phone ?: '—' }}</td><td>{{ $student->email ?: '—' }}</td><td>{{ $student->shift ?: '—' }}</td><td>{{ $student->session ?: '—' }}</td></tr>
      @endforeach</tbody>
     </table></div>
    @endif
    </div>
   </details>
  </div>
 </section>
 @endforeach
 </div>
@endif
@endsection
