@extends('layouts.app')
@section('title', $teacher->name . ' — Courses')
@section('page_title', 'LMS Monitor')
@section('content')

@php $statusMap = ['draft' => 'gray', 'published' => 'success', 'archived' => 'warning']; @endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $teacher->name }} — {{ $subject->name ?? 'No Subject' }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <a href="{{ route('admin.lms.teachers') }}" style="color:var(--text-muted);text-decoration:none"><i class="fas fa-arrow-left"></i> Teachers</a>
      <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:11px"></i>
      <a href="{{ route('admin.lms.teacher-subjects', $teacher) }}" style="color:var(--text-muted);text-decoration:none">{{ $teacher->name }}</a>
      <span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>
    </div>
  </div>
</div>

@if($courses->isEmpty())
<div class="card">
  <div class="empty-state">
    <i class="fas fa-book-open"></i>
    <p>{{ $teacher->name }} hasn't created any courses yet.</p>
  </div>
</div>
@else
<div class="grid grid-3">
  @foreach($courses as $i => $course)
  @php $variant = ['math','eng','sci','cs','default'][$i % 5]; @endphp
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
