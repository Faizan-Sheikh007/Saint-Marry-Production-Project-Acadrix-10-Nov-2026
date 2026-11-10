@extends('layouts.app')
@section('title', $lesson->title)
@section('page_title', $lesson->title)
@section('content')

@php
  $isTeacher = auth()->user()->isTeacher();
  $isAdmin   = auth()->user()->isAdmin();
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $lesson->title }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <span class="crumb subject"><i class="fas fa-book"></i> {{ $course->title }}</span>
      @if($isAdmin)<span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>@endif
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
    @if($isTeacher)
      <a href="{{ route('lms.lessons.edit', [$course, $lesson]) }}" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
      <form action="{{ route('lms.lessons.destroy', [$course, $lesson]) }}" method="POST" onsubmit="return confirm('Delete this lesson?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i> Delete</button>
      </form>
    @endif
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    @if($lesson->video_url)
      <div style="margin-bottom:20px;border-radius:10px;overflow:hidden;background:#000">
        <video src="{{ $lesson->video_url }}" controls style="width:100%;display:block;max-height:480px"></video>
      </div>
    @endif

    @if($lesson->description)
      <p style="color:var(--text-muted);font-size:13.5px;margin-bottom:16px">{{ $lesson->description }}</p>
    @endif

    @if($lesson->file_attachment)
      <a href="{{ storage_asset($lesson->file_attachment) }}" target="_blank" class="btn btn-outline btn-sm" style="margin-bottom:16px">
        <i class="fas fa-file-arrow-down"></i> Download lecture notes
      </a>
    @endif

    <div class="lesson-content">{!! nl2br(e($lesson->content)) !!}</div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    @if($prev)
      <a href="{{ route('lms.lessons.show', [$course, $prev]) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> {{ $prev->title }}</a>
    @endif
  </div>
  <div style="display:flex;gap:10px">
    @if($student)
      @if($progress && $progress->completed)
        <span class="badge success"><i class="fas fa-check"></i> Completed</span>
      @else
        <form action="{{ route('lms.lessons.complete', [$course, $lesson]) }}" method="POST">
          @csrf
          <button class="btn btn-primary"><i class="fas fa-check"></i> Mark as Complete</button>
        </form>
      @endif
    @endif
    @if($next)
      <a href="{{ route('lms.lessons.show', [$course, $next]) }}" class="btn btn-outline">{{ $next->title }} <i class="fas fa-arrow-right"></i></a>
    @endif
  </div>
</div>

@endsection
