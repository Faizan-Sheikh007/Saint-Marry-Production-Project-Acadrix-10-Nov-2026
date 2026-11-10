@extends('layouts.app')
@section('title', 'My Subjects')
@section('page_title', 'My Subjects')
@section('content')

@php
  $isAdmin = auth()->user()->isAdmin();
  $isStudent = auth()->user()->isStudent();
  $canManage = auth()->user()->isTeacher();
  $thumbClasses = ['math', 'eng', 'sci', 'cs', 'default'];
@endphp

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-chalkboard-user" style="color:var(--primary)"></i> My Subjects</div>
    <div class="page-subtitle">
      @if($canManage) The subjects assigned to you — add lessons, materials, assignments and quizzes for each class you teach.
      @elseif($isAdmin) Monitoring view — read-only. Only the assigned teacher can manage subject content.
      @else Every subject in your curriculum, with the lessons, materials and assignments your teacher has added.
      @endif
    </div>
  </div>
  @if($canManage)
  <div>
    <a href="{{ route('lms.courses.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Extra Course</a>
  </div>
  @endif
</div>

@if($courses->isEmpty())
<div class="card">
  <div class="empty-state">
    <i class="fas fa-book-open"></i>
    @if($isStudent)
      <p>No subjects have been assigned to your class yet. Once the admin assigns subjects to your class, they'll appear here automatically.</p>
    @elseif($canManage)
      <p>You haven't been assigned any subjects yet. Once the admin assigns you a subject to teach, it will appear here so you can add content.</p>
    @else
      <p>No subjects found.</p>
    @endif
    @if($canManage)
    <a href="{{ route('lms.courses.create') }}" class="btn btn-primary" style="margin-top:14px">
      <i class="fas fa-plus"></i> Create an extra course
    </a>
    @endif
  </div>
</div>
@else
<div class="grid grid-3">
  @foreach($courses as $i => $course)
  @php
    $statusMap = ['draft' => 'gray', 'published' => 'success', 'archived' => 'warning'];
    $variant = $thumbClasses[$i % count($thumbClasses)];
  @endphp
  <a href="{{ route('lms.courses.show', $course) }}" class="info-card" style="text-decoration:none;color:inherit">
    <div class="info-card-top">
      <div class="info-card-icon {{ $variant }}"><i class="fas fa-book"></i></div>
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:8px;justify-content:space-between">
          <div class="info-card-title">{{ $course->title }}</div>
          <span class="badge {{ $statusMap[$course->status] ?? 'gray' }}">{{ ucfirst($course->status) }}</span>
        </div>
        <div class="info-card-desc">{{ $course->description ?: 'No description provided.' }}</div>
      </div>
    </div>
    <div class="info-card-tags">
      @if($course->subject)<span class="tag"><i class="fas fa-book"></i> {{ $course->subject->name }}</span>@endif
      @if($course->schoolClass)<span class="tag"><i class="fas fa-users-rectangle"></i> {{ $course->schoolClass->section_label ?? $course->schoolClass->name }}</span>@endif
    </div>
    <div class="info-card-stats">
      <span><i class="fas fa-play-circle"></i> {{ $course->lessons_count }} lessons</span>
      <span><i class="fas fa-file-lines"></i> {{ $course->assignments_count }} tasks</span>
      <span><i class="fas fa-users"></i> {{ $course->enrollments_count }} enrolled</span>
    </div>
  </a>
  @endforeach
</div>
@endif

@endsection
