@extends('layouts.app')
@section('title', $teacher->name . ' — Subjects')
@section('page_title', 'LMS Monitor')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ $teacher->name }}'s Subjects</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <a href="{{ route('admin.lms.teachers') }}" style="color:var(--text-muted);text-decoration:none"><i class="fas fa-arrow-left"></i> Teachers</a>
      <span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>
    </div>
  </div>
</div>

@if($subjects->isEmpty())
<div class="card">
  <div class="empty-state">
    <i class="fas fa-book"></i>
    <p>{{ $teacher->name }} doesn't have any LMS courses yet.</p>
  </div>
</div>
@else
<div class="grid grid-4">
  @foreach($subjects as $i => $row)
  @php $variant = ['math','eng','sci','cs','default'][$i % 5]; @endphp
  <a href="{{ route('admin.lms.teacher-courses', [$teacher, $row->subject?->id ?? 0]) }}" class="info-card" style="text-decoration:none;color:inherit">
    <div class="info-card-top">
      <div class="info-card-icon {{ $variant }}"><i class="fas fa-book"></i></div>
      <div>
        <div class="info-card-title">{{ $row->subject->name ?? 'No Subject' }}</div>
        <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">Taught by {{ $teacher->name }}</div>
      </div>
    </div>
    <div class="info-card-stats">
      <span><i class="fas fa-chalkboard"></i> {{ $row->courses_count }} class{{ $row->courses_count === 1 ? '' : 'es' }}</span>
      <span><i class="fas fa-play-circle"></i> {{ $row->lessons_count }} lessons</span>
      <span><i class="fas fa-users"></i> {{ $row->students_count }} students</span>
    </div>
  </a>
  @endforeach
</div>
@endif

@endsection
