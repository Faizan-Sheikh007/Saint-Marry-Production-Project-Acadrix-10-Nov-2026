@extends('layouts.app')
@section('title', $quiz->title)
@section('page_title', $quiz->title)
@section('content')

@php
  $isTeacher = auth()->user()->isTeacher();
  $isAdmin   = auth()->user()->isAdmin();
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $quiz->title }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <span class="crumb subject"><i class="fas fa-book"></i> {{ $course->title }}</span>
      <span class="badge {{ $quiz->status === 'active' ? 'info' : 'gray' }}">{{ ucfirst($quiz->status) }}</span>
      @if($isAdmin)<span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>@endif
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
    @if($isTeacher)
      <a href="{{ route('lms.quizzes.edit', [$course, $quiz]) }}" class="btn btn-outline"><i class="fas fa-edit"></i> Edit / Publish</a>
      <form action="{{ route('lms.quizzes.destroy', [$course, $quiz]) }}" method="POST" onsubmit="return confirm('Delete this quiz?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i> Delete</button>
      </form>
    @endif
  </div>
</div>

@if(session('error'))<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="grid grid-4" style="margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-circle-question"></i></div><div><div class="stat-value">{{ $quiz->questions->count() }}</div><div class="stat-label">Questions</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-star"></i></div><div><div class="stat-value">{{ $quiz->total_marks }}</div><div class="stat-label">Total Marks</div></div></div>
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-clock"></i></div><div><div class="stat-value">{{ $quiz->time_limit }}</div><div class="stat-label">Minutes</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-rotate"></i></div><div><div class="stat-value">{{ $quiz->attempts_allowed }}</div><div class="stat-label">Attempts Allowed</div></div></div>
</div>

@if($quiz->description)
<div class="card" style="margin-bottom:20px"><div class="card-body" style="font-size:13.5px;color:var(--text-muted)">{{ $quiz->description }}</div></div>
@endif

@if($student)
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="text-align:center;padding:28px">
    @if($attempt && $attempt->status === 'submitted')
      <div style="font-size:34px;font-weight:800;color:var(--primary)">{{ $attempt->score }} / {{ $attempt->total_marks }}</div>
      <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:16px">{{ $attempt->percentage }}% &middot; Submitted {{ $attempt->submitted_at?->diffForHumans() }}</div>
      <span class="badge {{ $attempt->percentage >= ($quiz->passing_marks / max($quiz->total_marks,1) * 100) ? 'success' : 'danger' }}">
        {{ $attempt->percentage >= ($quiz->passing_marks / max($quiz->total_marks,1) * 100) ? 'Passed' : 'Not Passed' }}
      </span>
    @elseif($canAttempt)
      <p style="color:var(--text-muted);margin-bottom:16px">You have {{ $quiz->time_limit }} minutes to complete this quiz once started.</p>
      <a href="{{ route('lms.quizzes.take', [$course, $quiz]) }}" class="btn btn-primary"><i class="fas fa-play"></i> {{ $attempt ? 'Continue Quiz' : 'Start Quiz' }}</a>
    @else
      <p>This quiz is closed, past its due date, or has no attempts remaining.</p>
    @endif
    @if($attempt && $attempt->status === 'submitted' && $canAttempt)
      <p style="margin-top:14px"><a href="{{ route('lms.quizzes.take', [$course, $quiz]) }}" class="btn btn-primary">Start Next Attempt</a></p>
    @endif
    <p class="text-sm text-muted">{{ $attemptsUsed }} / {{ $quiz->attempts_allowed }} attempts submitted.</p>
  </div>
</div>
@endif

@if($isTeacher || $isAdmin)
<div class="card">
  <div class="card-header">
    <div class="card-title">Attempts</div>
    <span class="tag">{{ $allAttempts->count() }}</span>
  </div>
  @if($allAttempts->isEmpty())
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No attempts yet.</p></div>
  @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Score</th><th>Percentage</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
          @foreach($allAttempts as $a)
          <tr>
            <td>{{ $a->student->name ?? '—' }}</td>
            <td>{{ $a->score ?? '—' }} / {{ $a->total_marks }}</td>
            <td>{{ $a->percentage ?? '—' }}%</td>
            <td><span class="badge {{ $a->status === 'submitted' ? 'success' : 'warning' }}">{{ ucfirst($a->status) }}</span></td>
            <td style="font-size:12.5px">{{ $a->submitted_at?->format('d M Y, h:i A') ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endif

@endsection
