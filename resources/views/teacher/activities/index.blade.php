@extends('layouts.app')
@section('title','My Activities')
@section('content')
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-diagram-project"></i> Activities Assigned by Admin</h1>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
  <button class="act-tab-btn active" data-tab="tours" onclick="actTab('tours')"><i class="fas fa-bus"></i> Tours</button>
  <button class="act-tab-btn" data-tab="projects" onclick="actTab('projects')"><i class="fas fa-graduation-cap"></i> Projects</button>
  <button class="act-tab-btn" data-tab="practicals" onclick="actTab('practicals')"><i class="fas fa-flask"></i> Practicals</button>
  <button class="act-tab-btn" data-tab="assignments" onclick="actTab('assignments')"><i class="fas fa-file-lines"></i> Assignments</button>
  <button class="act-tab-btn" data-tab="quizzes" onclick="actTab('quizzes')"><i class="fas fa-question-circle"></i> Quizzes</button>
  <button class="act-tab-btn" data-tab="surveys" onclick="actTab('surveys')"><i class="fas fa-clipboard-list"></i> Surveys</button>
</div>

<style>
  .act-tab-btn{ border:1px solid var(--border); background:var(--surface,#fff); padding:8px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--text-muted); }
  .act-tab-btn.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
  .act-tabpanel{ display:none; }
  .act-tabpanel.active{ display:block; }
  .act-textarea{ width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; resize:vertical; margin-bottom:8px; }
</style>

{{-- TOURS --}}
<div class="act-tabpanel active" data-tabpanel="tours">
  <div class="card">
    @forelse($tours as $item)
      <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px">
        <div style="width:38px;height:38px;border-radius:8px;background:rgba(137,12,37,.1);color:var(--primary);display:flex;align-items:center;justify-content:center"><i class="fas fa-bus"></i></div>
        <div style="flex:1">
          <div style="font-weight:600">{{ $item->name }}</div>
          <div class="text-sm text-muted">{{ $item->place }} &middot; {{ optional($item->tour_date)->format('d M Y') }} @if($item->subject) &middot; {{ $item->subject->name }} @endif</div>
          @if($item->notes)<div class="text-sm" style="margin-top:4px">{{ $item->notes }}</div>@endif
        </div>
        @if($item->status === 'assigned')
          <form method="POST" action="{{ route('teacher.activities.tours.acknowledge', $item) }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-xs">Acknowledge</button>
          </form>
        @else
          <span class="badge success">{{ ucfirst($item->status) }}</span>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-bus"></i><p>No tours assigned to you.</p></div>
    @endforelse
  </div>
</div>

{{-- PROJECTS --}}
<div class="act-tabpanel" data-tabpanel="projects">
  <div class="card">
    @forelse($projects as $item)
      <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <b>{{ $item->title }}</b><span class="badge">{{ str($item->type)->headline() }}</span>
          <span class="badge {{ $item->status === 'assigned' ? 'warning' : 'success' }}" style="margin-left:auto">{{ ucfirst($item->status) }}</span>
        </div>
        @if($item->description)<div class="text-sm text-muted" style="margin-bottom:8px">{{ $item->description }}</div>@endif
        @if($item->teacher_submission)
          <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->teacher_submission }}</div>
        @else
          <form method="POST" action="{{ route('teacher.activities.projects.submit', $item) }}">
            @csrf
            <textarea name="teacher_submission" rows="3" class="act-textarea" placeholder="Write your project submission..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
          </form>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No projects assigned to you.</p></div>
    @endforelse
  </div>
</div>

{{-- PRACTICALS --}}
<div class="act-tabpanel" data-tabpanel="practicals">
  <div class="card">
    @forelse($practicals as $item)
      <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <b>{{ $item->title }}</b>
          <span class="badge {{ $item->paragraph ? 'success' : 'warning' }}" style="margin-left:auto">{{ $item->paragraph ? ucfirst($item->status) : 'Pending' }}</span>
        </div>
        @if($item->instructions)<div class="text-sm text-muted" style="margin-bottom:8px">{{ $item->instructions }}</div>@endif
        @if($item->paragraph)
          <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->paragraph }}</div>
        @else
          <form method="POST" action="{{ route('teacher.activities.practicals.submit', $item) }}">
            @csrf
            <textarea name="paragraph" rows="3" class="act-textarea" placeholder="Write the practical paragraph..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
          </form>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-flask"></i><p>No practicals requested from you.</p></div>
    @endforelse
  </div>
</div>

{{-- ASSIGNMENTS & QUIZZES --}}
<div class="act-tabpanel" data-tabpanel="assignments">
 @include('teacher.activities._learning-tasks', ['kind' => 'assignments', 'tasks' => $assignments])
</div>
<div class="act-tabpanel" data-tabpanel="quizzes">
 @include('teacher.activities._learning-tasks', ['kind' => 'quizzes', 'tasks' => $quizzes])
</div>

{{-- SURVEYS --}}
<div class="act-tabpanel" data-tabpanel="surveys">
  <div class="card">
    @forelse($surveys as $item)
      <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
          <b>{{ $item->title }}</b><span class="badge">{{ ucfirst($item->type) }}</span>
          <span class="badge {{ $item->status === 'assigned' ? 'warning' : 'success' }}" style="margin-left:auto">{{ ucfirst($item->status) }}</span>
        </div>
        @if($item->description)<div class="text-sm text-muted" style="margin-bottom:8px">{{ $item->description }}</div>@endif
        @if($item->teacher_submission)
          <div class="text-sm" style="padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->teacher_submission }}</div>
        @else
          <form method="POST" action="{{ route('teacher.activities.surveys.submit', $item) }}">
            @csrf
            <textarea name="teacher_submission" rows="3" class="act-textarea" placeholder="Write your survey findings..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
          </form>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No surveys assigned to you.</p></div>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
function actTab(name) {
  document.querySelectorAll('.act-tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  document.querySelectorAll('.act-tabpanel').forEach(p => p.classList.toggle('active', p.dataset.tabpanel === name));
}
</script>
@endpush
@endsection
