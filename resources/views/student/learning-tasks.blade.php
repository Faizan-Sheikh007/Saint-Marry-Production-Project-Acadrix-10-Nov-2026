@extends('layouts.app')
@section('title', 'Quizzes & Assignments')
@section('page_title', 'Quizzes & Assignments')
@section('content')
<div class="page-header"><div><div class="page-title">Quizzes &amp; Assignments</div>
<p class="page-subtitle">Your teachers → subjects → published learning tasks.</p></div>
<a class="btn btn-outline" href="{{ route('lms.courses.index') }}">My Subjects</a></div>
<form method="GET" class="card" style="padding:16px;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap">
<select name="teacher_id" class="form-control" style="max-width:320px"><option value="">All my teachers</option>
@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach</select>
<button class="btn btn-primary">Filter</button><a class="btn btn-outline" href="{{ route('student.learning-tasks.index') }}">Reset</a></form>
@forelse($byTeacher as $teacherCourses)
<section class="card" style="margin-bottom:24px">
<div class="card-header"><h2 style="font-size:20px;margin:0"><i class="fas fa-chalkboard-teacher"></i> {{ $teacherCourses->first()->teacher->name ?? 'Teacher not assigned' }}</h2></div>
<div class="card-body">
@foreach($teacherCourses as $course)
<div style="border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:16px">
<h3 style="margin:0 0 6px">{{ $course->subject->name ?? $course->title }}</h3>
<p class="text-sm text-muted">{{ $course->schoolClass->program->name ?? '' }} · {{ $course->schoolClass->section_label ?? '' }}</p>
<div class="grid grid-2" style="gap:20px;margin-top:16px">
<div><h4>Assignments ({{ $course->assignments->count() }})</h4>
@forelse($course->assignments as $task)
@php $submission = $task->submissions->first(); @endphp
<div style="padding:10px 0;border-bottom:1px solid var(--border)"><a href="{{ route('lms.assignments.show', [$course, $task]) }}"><strong>{{ $task->title }}</strong></a>
<div class="text-sm text-muted">{{ $task->due_date ? 'Due '.$task->due_date->format('d M Y H:i') : 'No due date' }} · {{ $task->total_marks }} marks</div>
<span class="badge {{ $submission ? 'success' : 'warning' }}">{{ $submission ? ucfirst($submission->status) : ($task->status === 'closed' ? 'Closed' : 'To submit') }}</span></div>
@empty<p class="text-sm text-muted">No published assignments assigned to you.</p>@endforelse</div>
<div><h4>Quizzes ({{ $course->quizzes->count() }})</h4>
@forelse($course->quizzes as $task)
@php $attempt = $task->attempts->first(); @endphp
<div style="padding:10px 0;border-bottom:1px solid var(--border)"><a href="{{ route('lms.quizzes.show', [$course, $task]) }}"><strong>{{ $task->title }}</strong></a>
<div class="text-sm text-muted">{{ $task->due_date ? 'Due '.$task->due_date->format('d M Y H:i') : 'No due date' }} · {{ $task->time_limit }} minutes · {{ $task->total_marks }} marks</div>
<span class="badge {{ $attempt && $attempt->status === 'submitted' ? 'success' : 'warning' }}">{{ $attempt ? ucwords(str_replace('_', ' ', $attempt->status)) : ($task->status === 'closed' ? 'Closed' : 'To attempt') }}</span></div>
@empty<p class="text-sm text-muted">No published quizzes assigned to you.</p>@endforelse</div>
</div>
<p style="margin-top:14px"><a href="{{ route('lms.courses.show', $course) }}">Open subject materials and results →</a></p>
</div>
@endforeach
</div></section>
@empty<div class="card"><div class="card-body">No active subjects match this selection. Published work will appear here once your teacher assigns it.</div></div>@endforelse
@endsection
