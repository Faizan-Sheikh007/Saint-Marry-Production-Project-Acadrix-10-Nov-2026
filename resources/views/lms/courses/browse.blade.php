@extends('layouts.app')
@section('title', 'Browse Courses')
@section('page_title', 'Browse Courses')
@section('content')

@php $thumbClasses = ['math', 'eng', 'sci', 'cs', 'default']; @endphp

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-book-open" style="color:var(--primary)"></i> Browse Courses</div>
    <div class="page-subtitle">Courses published for your class.</div>
  </div>
</div>

@if($courses->isEmpty())
<div class="card">
  <div class="empty-state">
    <i class="fas fa-book-open"></i>
    <p>No courses have been published for your class yet.</p>
  </div>
</div>
@else
<div class="grid grid-3">
  @foreach($courses as $i => $course)
  @php
    $enrolled = $enrolledIds->contains($course->id);
    $thumb = $thumbClasses[$i % count($thumbClasses)];
  @endphp
  <div class="info-card">
    <div class="info-card-top">
      <div class="info-card-icon {{ $thumb }}"><i class="fas fa-book"></i></div>
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:8px;justify-content:space-between">
          <div class="info-card-title">{{ $course->title }}</div>
          @if($enrolled)<span class="badge success"><i class="fas fa-check"></i> Enrolled</span>@endif
        </div>
        <div class="info-card-desc">{{ $course->description ?: 'No description provided.' }}</div>
      </div>
    </div>
    <div class="info-card-tags">
      @if($course->subject)<span class="tag"><i class="fas fa-book"></i> {{ $course->subject->name }}</span>@endif
      @if($course->teacher)<span class="tag"><i class="fas fa-chalkboard-user"></i> {{ $course->teacher->name }}</span>@endif
    </div>
    <div class="info-card-stats" style="justify-content:space-between">
      <span><i class="fas fa-play-circle"></i> {{ $course->lessons_count }} lessons</span>
      @if($enrolled)
        <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline btn-sm">Continue</a>
      @else
        <form action="{{ route('lms.courses.enroll', $course) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Enroll</button>
        </form>
      @endif
    </div>
  </div>
  @endforeach
</div>
@endif

@endsection
