@extends('layouts.app')
@section('title','Activity Requests')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-inbox"></i> Teacher Responses &amp; Student Tasks</h1>
    <p style="opacity:.8">Track teacher responses and whether assigned work has been published to students.</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-file-lines"></i> Assignments</div></div>
  @forelse($assignments as $item)
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }}</div>
        <span class="badge {{ ($item->request_status ?? 'pending') === 'pending' ? 'warning' : (($item->request_status === 'submitted') ? 'primary' : 'success') }}">{{ ucfirst($item->request_status ?? 'pending') }}</span>
      </div>
      <p class="text-sm text-muted">{{ $item->course->title ?? 'Legacy request' }} · {{ $item->published_at ? 'Published to students' : 'Not published to students' }}</p>
      @if($item->teacher_response)
        <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px;margin-bottom:8px">{{ $item->teacher_response }}</div>
        @if($item->request_status !== 'reviewed')
          <form method="POST" action="{{ route('admin.activities.assignments.review', $item) }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-xs"><i class="fas fa-check"></i> Mark Reviewed</button>
          </form>
        @endif
      @else
        <div class="text-sm text-muted">Waiting on {{ $item->teacher->name ?? 'teacher' }} to submit their paragraph.</div>
      @endif
    </div>
  @empty
    <div class="empty-state"><i class="fas fa-file-lines"></i><p>No assignments requested yet.</p></div>
  @endforelse
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-question-circle"></i> Quizzes</div></div>
  @forelse($quizzes as $item)
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }}</div>
        <span class="badge {{ ($item->request_status ?? 'pending') === 'pending' ? 'warning' : (($item->request_status === 'submitted') ? 'primary' : 'success') }}">{{ ucfirst($item->request_status ?? 'pending') }}</span>
      </div>
      <p class="text-sm text-muted">{{ $item->course->title ?? 'Legacy request' }} · {{ $item->published_at ? 'Published to students' : 'Not published to students' }}</p>
      @if($item->teacher_response)
        <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px;margin-bottom:8px">{{ $item->teacher_response }}</div>
        @if($item->request_status !== 'reviewed')
          <form method="POST" action="{{ route('admin.activities.quizzes.review', $item) }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-xs"><i class="fas fa-check"></i> Mark Reviewed</button>
          </form>
        @endif
      @else
        <div class="text-sm text-muted">Waiting on {{ $item->teacher->name ?? 'teacher' }} to submit their paragraph.</div>
      @endif
    </div>
  @empty
    <div class="empty-state"><i class="fas fa-question-circle"></i><p>No quizzes requested yet.</p></div>
  @endforelse
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-flask"></i> Practical Paragraphs</div></div>
  @forelse($practicals as $item)
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }}</div>
        <span class="badge {{ $item->status === 'reviewed' ? 'success' : 'primary' }}">{{ ucfirst($item->status) }}</span>
      </div>
      <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px;margin-bottom:8px">{{ $item->paragraph }}</div>
      @if($item->status !== 'reviewed')
        <form method="POST" action="{{ route('admin.activities.practicals.review', $item) }}">
          @csrf
          <button type="submit" class="btn btn-outline btn-xs"><i class="fas fa-check"></i> Mark Reviewed</button>
        </form>
      @endif
    </div>
  @empty
    <div class="empty-state"><i class="fas fa-flask"></i><p>No practical paragraphs submitted yet.</p></div>
  @endforelse
</div>
@endsection
