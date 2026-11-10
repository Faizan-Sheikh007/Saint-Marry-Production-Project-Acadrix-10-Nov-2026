@extends('layouts.app')
@section('title','Teacher Dashboard')
@section('content')

@include('partials.ai-insights-teaser', ['teaserScopeLabel' => 'Classes'])

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:24px;font-weight:800;margin-bottom:6px">Welcome, {{ explode(' ', auth()->user()->name)[0] }}! 📚</h1>
    <p style="opacity:.8">{{ now()->format('l, F j') }} — Manage your courses and students</p>
  </div>
</div>

@if($announcements->isNotEmpty())
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-bullhorn"></i> Announcements from Admin</div>
  </div>
  @foreach($announcements as $a)
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
      @if($a->pinned)<span class="badge warning"><i class="fas fa-thumbtack"></i> Pinned</span>@endif
      <div style="font-weight:700;font-size:13.5px">{{ $a->title }}</div>
      <div class="text-sm text-muted" style="margin-left:auto">{{ $a->created_at->diffForHumans() }}</div>
    </div>
    <div class="text-sm" style="color:var(--text-muted)">{{ $a->content }}</div>
  </div>
  @endforeach
</div>
@endif

<div class="grid grid-3" style="margin-bottom:24px">
  <a href="{{ route('teacher.messages.index') }}" class="stat-card" style="text-decoration:none;position:relative">
    <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
    <div><div class="stat-value">{{ $unreadMessagesCount }}</div><div class="stat-label">Unread Messages</div></div>
    @if($unreadMessagesCount > 0)<span style="position:absolute;top:12px;right:12px;width:10px;height:10px;border-radius:50%;background:var(--danger,#dc2626)"></span>@endif
  </a>
  <a href="{{ route('teacher.activities.index') }}" class="stat-card" style="text-decoration:none">
    <div class="stat-icon yellow"><i class="fas fa-diagram-project"></i></div>
    <div><div class="stat-value">{{ array_sum($activityCounts) }}</div><div class="stat-label">Pending Activities</div></div>
  </a>
  <a href="{{ route('teacher.activities.index') }}" class="stat-card" style="text-decoration:none">
    <div class="stat-icon red"><i class="fas fa-flask"></i></div>
    <div><div class="stat-value">{{ $activityCounts['practicals'] + $activityCounts['assignments'] + $activityCounts['quizzes'] }}</div><div class="stat-label">Paragraphs Requested</div></div>
  </a>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-diagram-project"></i> Activities Assigned by Admin</div>
    <a href="{{ route('teacher.activities.index') }}" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div style="padding:16px 20px;display:flex;gap:12px;flex-wrap:wrap">
    @foreach([
      ['Tours', 'tours', 'fa-bus', $activityCounts['tours']],
      ['Projects', 'projects', 'fa-graduation-cap', $activityCounts['projects']],
      ['Practicals', 'practicals', 'fa-flask', $activityCounts['practicals']],
      ['Assignments', 'assignments', 'fa-file-lines', $activityCounts['assignments']],
      ['Surveys', 'surveys', 'fa-clipboard-list', $activityCounts['quizzes'] + $activityCounts['surveys']],
    ] as $t)
    <a href="{{ route('teacher.activities.index') }}" class="badge {{ $t[3] > 0 ? 'warning' : 'success' }}" style="text-decoration:none;padding:8px 14px;font-size:12.5px">
      <i class="fas {{ $t[2] }}"></i> {{ $t[0] }}: {{ $t[3] }}
    </a>
    @endforeach
  </div>
  @if($latestMessage)
  <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px">
    <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0"><i class="fas fa-comment-dots"></i></div>
    <div style="flex:1;min-width:0">
      <div class="text-sm text-muted">Latest message</div>
      <div style="font-weight:600;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $latestMessage->message }}</div>
    </div>
    <a href="{{ route('teacher.messages.index') }}" class="btn btn-outline btn-xs">Open Chat</a>
  </div>
  @endif
</div>

<div class="grid grid-3" style="margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-laptop-code"></i></div>
    <div><div class="stat-value">{{ $courses->count() }}</div><div class="stat-label">My Courses</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
    <div><div class="stat-value">{{ $myStudents }}</div><div class="stat-label">My Students</div></div></div>
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-tasks"></i></div>
    <div><div class="stat-value">{{ $pending->sum('ungraded') }}</div><div class="stat-label">Pending Grades</div></div></div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-file-pen"></i> Exam Duties</div>
    <a href="{{ route('teacher.examinations.index') }}" class="btn btn-outline btn-sm">All Exams</a>
  </div>
  @if(isset($pendingPapers) && $pendingPapers->isNotEmpty())
    <div style="padding:10px 20px 0">
      <div class="alert alert-danger" style="margin-bottom:0;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <span><i class="fas fa-file-circle-exclamation"></i> Please create or upload your question paper — {{ $pendingPapers->count() }} paper(s) waiting.</span>
        <a href="{{ route('teacher.examinations.papers.index') }}" class="btn btn-primary btn-sm">Go to Papers</a>
      </div>
    </div>
  @endif
  @if($pendingMarksExams->isNotEmpty())
    <div style="padding:10px 20px 0">
      <div class="alert alert-warning" style="margin-bottom:0"><i class="fas fa-clipboard-list"></i> You have {{ $pendingMarksExams->count() }} exam(s) awaiting marks entry.</div>
    </div>
  @endif
  @forelse($upcomingExams as $exam)
  <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
    <div style="width:40px;height:40px;border-radius:8px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:14px;flex-shrink:0"><i class="fas fa-file-pen"></i></div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:13.5px">{{ $exam->name }} — {{ $exam->subject->name ?? 'All Subjects' }}</div>
      <div class="text-sm text-muted">{{ $exam->schoolClass->display_name ?? $exam->schoolClass->name ?? '—' }} @if($exam->room) &middot; {{ $exam->room }} @endif</div>
    </div>
    <div style="text-align:right;font-size:12.5px;font-weight:700;color:var(--text-muted)">
      {{ $exam->exam_date?->format('d M') }}
      @if($exam->time_label)<div style="font-weight:500">{{ $exam->time_label }}</div>@endif
    </div>
    <a href="{{ route('teacher.examinations.results', $exam) }}" class="btn btn-outline btn-xs">Marks</a>
  </div>
  @empty
  <div class="empty-state"><i class="fas fa-calendar-check"></i><p>No upcoming exam duties assigned to you.</p></div>
  @endforelse
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-calendar-day"></i> Today's Classes — {{ $todayName }}</div>
    <a href="{{ route('teacher.timetable.index') }}" class="btn btn-outline btn-sm">Full Timetable</a>
  </div>
  @forelse($todaysClasses as $p)
  <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
    <div style="width:40px;height:40px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0"><i class="fas fa-chalkboard-teacher"></i></div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:13.5px">{{ $p->subject->name ?? '—' }}</div>
      <div class="text-sm text-muted">{{ $p->schoolClass->display_name ?? ($p->schoolClass->name ?? '—') }}</div>
    </div>
    <div style="text-align:right;font-size:12.5px;font-weight:700;color:var(--text-muted)">
      {{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
    </div>
  </div>
  @empty
  <div class="empty-state"><i class="fas fa-calendar-check"></i><p>No classes scheduled for today.</p></div>
  @endforelse
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-header">
      <div class="card-title">My Courses</div>
      <a href="{{ route('lms.courses.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New</a>
    </div>
    @forelse($courses as $course)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="width:40px;height:40px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px"><i class="fas fa-book-open"></i></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px">{{ $course->title }}</div>
        <div class="text-sm text-muted">{{ $course->lessons_count }} lessons · {{ $course->enrollments_count }} students</div>
      </div>
      <span class="badge {{ $course->status === 'published' ? 'success' : 'warning' }}">{{ $course->status }}</span>
      <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline btn-xs">Open</a>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-laptop-code"></i><p>No courses yet. <a href="{{ route('lms.courses.create') }}" style="color:var(--primary)">Create your first course</a></p></div>
    @endforelse
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Pending Grading</div></div>
    @forelse($pending->filter(fn($a) => $a->ungraded > 0) as $assignment)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="width:36px;height:36px;border-radius:8px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;color:var(--warning)"><i class="fas fa-tasks"></i></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px">{{ $assignment->title }}</div>
        <div class="text-sm text-muted">{{ $assignment->course->title ?? '' }}</div>
      </div>
      <span class="badge warning">{{ $assignment->ungraded }} pending</span>
      <a href="{{ route('lms.assignments.show', [$assignment->course, $assignment]) }}" class="btn btn-outline btn-xs">Grade</a>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-check-circle"></i><p>All caught up! No pending grades.</p></div>
    @endforelse
  </div>
</div>
@endsection
