@extends('layouts.app')
@section('title','Assign Activities')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-diagram-project"></i> Assign Activities to Teachers</h1>
    <p style="opacity:.8">Tours, Projects, Practicals, Assignments, Quizzes &amp; Surveys — all show up on the teacher's dashboard.</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center">
  <a href="{{ route('admin.activities.requests') }}" class="btn btn-outline btn-sm"><i class="fas fa-inbox"></i> Review Teacher Responses</a>

  <form method="GET" action="{{ route('admin.activities.index') }}" style="display:flex;align-items:center;gap:8px;margin-left:auto" data-ajax-filter>
    <label class="text-sm text-muted" style="white-space:nowrap"><i class="fas fa-filter"></i> Show activities for:</label>
    <select name="teacher_id" onchange="this.form.requestSubmit()" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;min-width:200px">
      <option value="">All Teachers</option>
      @foreach($teachers as $t)
        <option value="{{ $t->id }}" {{ (string) $filterTeacherId === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
      @endforeach
    </select>
    @if($filterTeacherId)
      <a href="{{ route('admin.activities.index') }}" class="btn btn-outline btn-xs" title="Clear filter" data-ajax-filter-link><i class="fas fa-xmark"></i></a>
    @endif
  </form>
</div>

@if($filterTeacherId)
  @php $filteredTeacherName = $teachers->firstWhere('id', $filterTeacherId)->name ?? 'this teacher'; @endphp
  <div class="alert" style="background:rgba(37,99,235,.08);color:#2563eb;margin-bottom:16px;display:flex;align-items:center;gap:8px">
    <i class="fas fa-user-check"></i> Showing activities already assigned to <b>{{ $filteredTeacherName }}</b> only — counts below reflect this filter.
  </div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-header" style="gap:6px;flex-wrap:wrap">
    <button class="act-tab-btn active" data-tab="tours" onclick="actTab('tours')"><i class="fas fa-bus"></i> Tours <span class="act-tab-count">{{ $tours->count() }}</span></button>
    <button class="act-tab-btn" data-tab="projects" onclick="actTab('projects')"><i class="fas fa-graduation-cap"></i> Projects <span class="act-tab-count">{{ $projects->count() }}</span></button>
    <button class="act-tab-btn" data-tab="practicals" onclick="actTab('practicals')"><i class="fas fa-flask"></i> Practicals <span class="act-tab-count">{{ $practicals->count() }}</span></button>
    <button class="act-tab-btn" data-tab="assignments" onclick="actTab('assignments')"><i class="fas fa-file-lines"></i> Assignments <span class="act-tab-count">{{ $assignments->count() }}</span></button>
    <button class="act-tab-btn" data-tab="quizzes" onclick="actTab('quizzes')"><i class="fas fa-circle-question"></i> Quizzes <span class="act-tab-count">{{ $quizzes->count() }}</span></button>
    <button class="act-tab-btn" data-tab="surveys" onclick="actTab('surveys')"><i class="fas fa-clipboard-list"></i> Surveys <span class="act-tab-count">{{ $surveys->count() }}</span></button>
  </div>

  <style>
    .card-header{ display:flex; }
    .act-tab-btn{ border:1px solid var(--border); background:var(--surface,#fff); padding:8px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--text-muted); }
    .act-tab-btn.active{ background:var(--primary); border-color:var(--primary); color:#fff; }
    .act-tab-count{ display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:9px; background:rgba(0,0,0,.08); font-size:10.5px; font-weight:700; margin-left:3px; }
    .act-tab-btn.active .act-tab-count{ background:rgba(255,255,255,.25); }
    .act-tabpanel{ display:none; padding:20px; }
    .act-tabpanel.active{ display:block; }
    .act-field{ width:100%; padding:10px 12px; margin:6px 0 14px; border:1px solid var(--border); border-radius:8px; }
  </style>

  {{-- TOURS --}}
  <div class="act-tabpanel active" data-tabpanel="tours">
    <form method="POST" action="{{ route('admin.activities.tours.store') }}" style="margin-bottom:22px">
      @csrf
      <div class="grid grid-3" style="gap:12px">
        <div><label class="text-sm text-muted">Teacher</label>
          <select name="teacher_id" required class="act-field">@foreach($teachers as $t)<option value="{{ $t->id }}" {{ (string) $filterTeacherId === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Subject (optional)</label>
          <select name="subject_id" class="act-field"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Tour Name</label><input type="text" name="name" required class="act-field"></div>
        <div><label class="text-sm text-muted">Date</label><input type="date" name="tour_date" required class="act-field"></div>
        <div><label class="text-sm text-muted">Place</label><input type="text" name="place" required class="act-field"></div>
        <div><label class="text-sm text-muted">Notes (optional)</label><input type="text" name="notes" class="act-field"></div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Assign Tour</button>
    </form>
    @forelse($tours as $item)
      <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid var(--border)">
        <div style="flex:1"><b>{{ $item->name }}</b> — {{ $item->teacher->name ?? '—' }} <span class="text-sm text-muted">{{ $item->place }} &middot; {{ optional($item->tour_date)->format('d M Y') }}</span></div>
        <span class="badge {{ $item->status === 'assigned' ? 'warning' : 'success' }}">{{ ucfirst($item->status) }}</span>
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-bus"></i><p>No tours assigned yet.</p></div>
    @endforelse
  </div>

  {{-- PROJECTS --}}
  <div class="act-tabpanel" data-tabpanel="projects">
    <form method="POST" action="{{ route('admin.activities.projects.store') }}" style="margin-bottom:22px">
      @csrf
      <div class="grid grid-3" style="gap:12px">
        <div><label class="text-sm text-muted">Teacher</label>
          <select name="teacher_id" required class="act-field">@foreach($teachers as $t)<option value="{{ $t->id }}" {{ (string) $filterTeacherId === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Subject (optional)</label>
          <select name="subject_id" class="act-field"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Type</label>
          <select name="type" required class="act-field"><option value="thesis">Thesis</option><option value="review_article">Review Article</option></select></div>
        <div><label class="text-sm text-muted">Title</label><input type="text" name="title" required class="act-field"></div>
        <div><label class="text-sm text-muted">Due Date (optional)</label><input type="date" name="due_date" class="act-field"></div>
        <div style="grid-column:span 3"><label class="text-sm text-muted">Description</label><textarea name="description" rows="2" class="act-field"></textarea></div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Assign Project</button>
    </form>
    @forelse($projects as $item)
      <div style="padding:12px 0;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }} <span class="badge">{{ str($item->type)->headline() }}</span></div>
          <span class="badge {{ $item->status === 'assigned' ? 'warning' : 'success' }}">{{ ucfirst($item->status) }}</span>
        </div>
        @if($item->teacher_submission)
          <div class="text-sm" style="margin-top:6px;padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->teacher_submission }}</div>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No projects assigned yet.</p></div>
    @endforelse
  </div>

  {{-- PRACTICALS --}}
  <div class="act-tabpanel" data-tabpanel="practicals">
    <form method="POST" action="{{ route('admin.activities.practicals.store') }}" style="margin-bottom:22px">
      @csrf
      <div class="grid grid-3" style="gap:12px">
        <div><label class="text-sm text-muted">Teacher</label>
          <select name="teacher_id" required class="act-field">@foreach($teachers as $t)<option value="{{ $t->id }}" {{ (string) $filterTeacherId === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Subject (optional)</label>
          <select name="subject_id" class="act-field"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Title</label><input type="text" name="title" required class="act-field"></div>
        <div><label class="text-sm text-muted">Due Date (optional)</label><input type="date" name="due_date" class="act-field"></div>
        <div style="grid-column:span 2"><label class="text-sm text-muted">Instructions</label><textarea name="instructions" rows="2" class="act-field" placeholder="What should the teacher write about?"></textarea></div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Request Practical</button>
    </form>
    @forelse($practicals as $item)
      <div style="padding:12px 0;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }}</div>
          <span class="badge {{ $item->paragraph ? 'success' : 'warning' }}">{{ $item->paragraph ? 'Submitted' : 'Pending' }}</span>
        </div>
        @if($item->paragraph)
          <div class="text-sm" style="margin-top:6px;padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->paragraph }}</div>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-flask"></i><p>No practicals requested yet.</p></div>
    @endforelse
  </div>

  <div class="act-tabpanel" data-tabpanel="assignments">
    @include('admin.activities._learning-task-form', ['kind' => 'assignments'])
    @include('admin.activities._learning-task-list', ['kind' => 'assignments', 'tasks' => $assignments])
  </div>
  <div class="act-tabpanel" data-tabpanel="quizzes">
    @include('admin.activities._learning-task-form', ['kind' => 'quizzes'])
    @include('admin.activities._learning-task-list', ['kind' => 'quizzes', 'tasks' => $quizzes])
  </div>

  {{-- SURVEYS --}}
  <div class="act-tabpanel" data-tabpanel="surveys">
    <form method="POST" action="{{ route('admin.activities.surveys.store') }}" style="margin-bottom:22px">
      @csrf
      <div class="grid grid-3" style="gap:12px">
        <div><label class="text-sm text-muted">Teacher</label>
          <select name="teacher_id" required class="act-field">@foreach($teachers as $t)<option value="{{ $t->id }}" {{ (string) $filterTeacherId === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Subject (optional)</label>
          <select name="subject_id" class="act-field"><option value="">—</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <div><label class="text-sm text-muted">Type</label>
          <select name="type" required class="act-field"><option value="hospital">Hospital Survey</option><option value="industry">Industry Survey</option></select></div>
        <div><label class="text-sm text-muted">Title</label><input type="text" name="title" required class="act-field"></div>
        <div><label class="text-sm text-muted">Due Date (optional)</label><input type="date" name="due_date" class="act-field"></div>
        <div style="grid-column:span 3"><label class="text-sm text-muted">Description</label><textarea name="description" rows="2" class="act-field"></textarea></div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Assign Survey</button>
    </form>
    @forelse($surveys as $item)
      <div style="padding:12px 0;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="flex:1"><b>{{ $item->title }}</b> — {{ $item->teacher->name ?? '—' }} <span class="badge">{{ ucfirst($item->type) }}</span></div>
          <span class="badge {{ $item->status === 'assigned' ? 'warning' : 'success' }}">{{ ucfirst($item->status) }}</span>
        </div>
        @if($item->teacher_submission)
          <div class="text-sm" style="margin-top:6px;padding:10px;background:var(--bg,#f8fafc);border-radius:8px">{{ $item->teacher_submission }}</div>
        @endif
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-clipboard-list"></i><p>No surveys assigned yet.</p></div>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
function actTab(name) {
  document.querySelectorAll('.act-tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  document.querySelectorAll('.act-tabpanel').forEach(p => p.classList.toggle('active', p.dataset.tabpanel === name));
}
document.querySelectorAll('[data-learning-request]').forEach(form => {
  const teacher = form.querySelector('[data-task-teacher]');
  const course = form.querySelector('[data-task-course]');
  function updateOptions() {
    Array.from(course.options).forEach(option => {
      if (!option.value) return;
      const allowed = option.dataset.teacher === teacher.value;
      option.hidden = !allowed;
      option.disabled = !allowed;
      if (!allowed && option.selected) course.value = '';
    });
  }
  teacher.addEventListener('change', updateOptions);
  updateOptions();
});
const restoreTab = @json(old('activity_tab'));
if (restoreTab) actTab(restoreTab);
</script>
@endpush
@endsection
