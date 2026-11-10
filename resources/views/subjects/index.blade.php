@extends('layouts.app')
@section('title', 'Subjects & Curriculum')
@section('page_title', 'Subjects & Curriculum')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

.sj-scope * { box-sizing:border-box; }
.sj-scope { font-family:'Outfit', sans-serif; }
.sj-scope i.fas, .sj-scope i.far, .sj-scope i.fab { font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands'; }
.sj-scope {
  --crimson:      #890c25;
  --crimson-soft: rgba(137,12,37,.08);
  --border:       rgba(137,12,37,.12);
  --surface:      #ffffff;
  --text:         #1a0608;
  --muted:        #8a5860;
  --radius:       14px;
  --shadow:       0 2px 16px rgba(137,12,37,.07);
}

/* ── Header ── */
.sj-header { display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:22px; }
.sj-header h1 { font-size:22px; font-weight:800; color:var(--text); letter-spacing:-.01em; }
.sj-header p { font-size:13.5px; color:var(--muted); margin-top:5px; }
.sj-actions { display:flex; gap:10px; flex-wrap:wrap; }

.btn-sj-outline, .btn-sj-primary {
  display:inline-flex; align-items:center; gap:8px; border-radius:10px;
  padding:10px 18px; font-size:13.5px; font-weight:700; cursor:pointer;
  text-decoration:none; transition:transform .15s, box-shadow .15s, background .15s;
  border:1.5px solid transparent;
}
.btn-sj-outline { background:var(--surface); border-color:var(--border); color:var(--crimson); box-shadow:var(--shadow); }
.btn-sj-outline:hover { border-color:var(--crimson); transform:translateY(-1px); }
.btn-sj-primary { background:var(--crimson); color:#fff; box-shadow:0 4px 14px rgba(137,12,37,.30); }
.btn-sj-primary:hover { background:#a81030; transform:translateY(-1px); box-shadow:0 6px 20px rgba(137,12,37,.35); }

/* ── Flash ── */
.sj-flash { display:flex; align-items:center; gap:10px; background:#e8f9ef; border:1px solid rgba(13,158,110,.25);
  border-radius:10px; padding:12px 18px; margin-bottom:18px; font-size:13.5px; font-weight:500; color:#065f46; animation:sjFade .3s ease; }
.sj-flash i { color:#0d9e6e; }
@keyframes sjFade { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

/* ── Stat strip ── */
.sj-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:26px; }
.sj-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:16px 18px; box-shadow:var(--shadow); display:flex; align-items:center; gap:14px; }
.sj-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sj-stat-num { font-size:20px; font-weight:800; color:var(--text); line-height:1; }
.sj-stat-label { font-size:11.5px; color:var(--muted); font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.04em; }

/* ── Section title ── */
.sj-section-title { display:flex; align-items:center; justify-content:space-between; margin:30px 0 14px; }
.sj-section-title h2 { font-size:16px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:8px; }
.sj-section-title h2 i { color:var(--crimson); font-size:14px; }
.sj-toggle-link { font-size:12.5px; font-weight:700; color:var(--crimson); cursor:pointer; user-select:none; }

/* ── Class cards (Class -> Subjects -> Teacher) ── */
.sj-program-group { margin-bottom:26px; }
.sj-program-group-header { display:flex; align-items:center; gap:10px; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--border); }
.sj-program-group-header i { color:var(--crimson); font-size:16px; }
.sj-program-group-name { font-size:15.5px; font-weight:800; color:var(--text); }
.sj-program-group-count { margin-left:auto; font-size:11.5px; font-weight:700; color:var(--muted); background:var(--crimson-soft); padding:4px 12px; border-radius:20px; }
.sj-class-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:16px; }
.sj-class-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; animation:sjUp .35s ease; }
@keyframes sjUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.sj-class-head { padding:16px 18px; background:linear-gradient(135deg,var(--crimson) 0%,#a81030 100%); color:#fff; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.sj-class-head-name { font-size:15px; font-weight:800; }
.sj-class-head-program { font-size:11.5px; opacity:.85; margin-top:2px; font-weight:500; }
.sj-class-count { background:rgba(255,255,255,.18); border-radius:20px; padding:4px 12px; font-size:11.5px; font-weight:700; white-space:nowrap; }

.sj-subject-row { display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border); transition:background .12s; }
.sj-subject-row:last-child { border-bottom:none; }
.sj-subject-row:hover { background:#fdf6f7; }
.sj-subj-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
.sj-subj-name { font-size:13.5px; font-weight:700; color:var(--text); flex:1; min-width:0; }
.sj-teacher-pill { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; padding:5px 10px; border-radius:20px; background:var(--crimson-soft); color:var(--crimson); white-space:nowrap; }
.sj-teacher-pill.unassigned { background:#fff4e6; color:#b45309; }
.sj-teacher-pill i { font-size:10px; }
.sj-row-actions { display:flex; gap:4px; flex-shrink:0; }
.sj-row-btn { width:26px; height:26px; border-radius:7px; border:none; background:transparent; color:var(--muted); display:flex; align-items:center; justify-content:center; font-size:11px; cursor:pointer; transition:background .12s,color .12s; }
.sj-row-btn:hover { background:var(--crimson-soft); color:var(--crimson); }
.sj-row-btn.danger:hover { background:#fde8e8; color:#c0192e; }

.sj-class-empty { padding:22px 18px; text-align:center; color:var(--muted); font-size:12.5px; }
.sj-class-foot { padding:12px 18px; }
.sj-add-subject-btn { width:100%; padding:9px; border-radius:9px; border:1.5px dashed var(--border); background:transparent; color:var(--muted); font-size:12.5px; font-weight:700; cursor:pointer; transition:border-color .15s,color .15s; }
.sj-add-subject-btn:hover { border-color:var(--crimson); color:var(--crimson); }

/* ── Subject catalog table ── */
.sj-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
.sj-table { width:100%; border-collapse:collapse; }
.sj-table th { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); padding:14px 16px; background:#fdf6f7; border-bottom:1.5px solid var(--border); text-align:left; }
.sj-table td { padding:13px 16px; border-bottom:1px solid var(--border); font-size:13.5px; color:var(--text); vertical-align:middle; }
.sj-table tr:last-child td { border-bottom:none; }
.sj-badge { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:20px; background:var(--crimson-soft); color:var(--crimson); }
.sj-empty { text-align:center; padding:50px 20px; color:var(--muted); }
.sj-empty i { font-size:40px; opacity:.25; display:block; margin-bottom:12px; }

/* ── Modal ── */
.sj-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(15,5,7,.45); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; }
.sj-modal-backdrop.open { display:flex; }
.sj-modal-box { background:var(--surface); border-radius:16px; width:100%; max-width:520px; margin:20px; max-height:88vh; display:flex; flex-direction:column; box-shadow:0 24px 60px rgba(137,12,37,.20); animation:sjModal .25s cubic-bezier(.34,1.56,.64,1); }
@keyframes sjModal { from{opacity:0;transform:scale(.94) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
.sj-modal-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid var(--border); flex-shrink:0; }
.sj-modal-title { font-size:17px; font-weight:800; }
.sj-modal-close { width:32px; height:32px; border-radius:8px; border:none; background:var(--crimson-soft); color:var(--muted); display:flex; align-items:center; justify-content:center; font-size:16px; cursor:pointer; }
.sj-modal-close:hover { background:var(--crimson); color:#fff; }
.sj-modal-body { padding:20px 24px; overflow-y:auto; }
.sj-modal-footer { padding:16px 24px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px; flex-shrink:0; }

.sj-form-group { margin-bottom:16px; }
.sj-form-label { display:block; font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
.sj-form-control { width:100%; padding:10px 14px; border-radius:9px; border:1.5px solid var(--border); background:var(--surface); color:var(--text); font-size:13.5px; }
.sj-form-control:focus { outline:none; border-color:var(--crimson); box-shadow:0 0 0 3px rgba(137,12,37,.10); }
.sj-class-checks { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:8px; max-height:220px; overflow-y:auto; padding:12px; border:1.5px solid var(--border); border-radius:9px; }
.sj-class-check-group-label { grid-column:1 / -1; font-size:10.5px; font-weight:800; letter-spacing:.05em; text-transform:uppercase; color:var(--crimson); margin-top:6px; padding-bottom:2px; border-bottom:1px solid var(--border); }
.sj-class-check-group-label:first-child { margin-top:0; }
.sj-class-check { display:flex; align-items:center; gap:8px; font-size:12.5px; font-weight:500; padding:6px 8px; border-radius:7px; cursor:pointer; }
.sj-class-check:hover { background:var(--crimson-soft); }
.sj-class-check input { accent-color:var(--crimson); width:15px; height:15px; }
</style>

@php
  if (!function_exists('sj_color')) {
      function sj_color(?string $name): string {
          $palette = ['#c0192e', '#0d9e6e', '#d97706', '#9d174d', '#6d28d9', '#0369a1', '#92400e', '#3730a3', '#5b21b6'];
          $idx = $name ? (crc32($name) % count($palette)) : 0;
          return $palette[$idx];
      }
  }
@endphp

<div class="sj-scope">

  {{-- Flash --}}
  @if(session('success'))
  <div class="sj-flash"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>
  @endif

  {{-- Header --}}
  <div class="sj-header">
    <div>
      <h1>Subjects &amp; Curriculum</h1>
      <p>Manage your subject catalog and see who teaches what, in every class.</p>
    </div>
    <div class="sj-actions">
      <button type="button" class="btn-sj-outline" onclick="openAssignModal()"><i class="fas fa-diagram-project"></i> Assign to Classes</button>
      <a href="{{ route('subjects.create') }}" class="btn-sj-primary"><i class="fas fa-plus"></i> Add Subject</a>
    </div>
  </div>

  {{-- Stats --}}
  @php
    $totalAssignments = $classes->sum(fn($c) => $c->subjectAssignments->count());
    $unassignedCount  = $items->where('class_assignments_count', 0)->count();
  @endphp
  <div class="sj-stats">
    <div class="sj-stat">
      <div class="sj-stat-icon" style="background:var(--crimson-soft);color:var(--crimson)"><i class="fas fa-book"></i></div>
      <div><div class="sj-stat-num">{{ $items->count() }}</div><div class="sj-stat-label">Subjects</div></div>
    </div>
    <div class="sj-stat">
      <div class="sj-stat-icon" style="background:#e8eafd;color:#3730a3"><i class="fas fa-school"></i></div>
      <div><div class="sj-stat-num">{{ $classes->count() }}</div><div class="sj-stat-label">Classes</div></div>
    </div>
    <div class="sj-stat">
      <div class="sj-stat-icon" style="background:#e8f9ef;color:#0d9e6e"><i class="fas fa-diagram-project"></i></div>
      <div><div class="sj-stat-num">{{ $totalAssignments }}</div><div class="sj-stat-label">Assignments Made</div></div>
    </div>
    <div class="sj-stat">
      <div class="sj-stat-icon" style="background:#fff4e6;color:#b45309"><i class="fas fa-triangle-exclamation"></i></div>
      <div><div class="sj-stat-num">{{ $unassignedCount }}</div><div class="sj-stat-label">Unassigned Subjects</div></div>
    </div>
  </div>

  {{-- Class -> Subjects -> Teacher --}}
  <div class="sj-section-title">
    <h2><i class="fas fa-sitemap"></i> Curriculum by Class</h2>
  </div>

  @if($classes->count() === 0)
    <div class="sj-card"><div class="sj-empty"><i class="fas fa-school-flag"></i><p>No classes have been created yet. Add classes first, then assign subjects to them here.</p></div></div>
  @else
    @foreach($classesByProgram as $programId => $programClasses)
      @php $program = $programClasses->first()->program ?? null; @endphp
      <div class="sj-program-group">
        <div class="sj-program-group-header">
          <i class="fas fa-graduation-cap"></i>
          <span class="sj-program-group-name">{{ $program->name ?? 'No Program Assigned' }}</span>
          <span class="sj-program-group-count">{{ $programClasses->count() }} class{{ $programClasses->count() === 1 ? '' : 'es' }}</span>
        </div>
        <div class="sj-class-grid">
          @foreach($programClasses as $class)
            @php $count = $class->subjectAssignments->count(); @endphp
            <div class="sj-class-card">
              <div class="sj-class-head">
                <div>
                  <div class="sj-class-head-name">{{ $class->name }}{{ $class->section ? ' ('.$class->section.')' : '' }}</div>
                </div>
                <div class="sj-class-count">{{ $count }} subject{{ $count === 1 ? '' : 's' }}</div>
              </div>

        @if($count === 0)
          <div class="sj-class-empty">No subjects assigned to this class yet.</div>
        @else
          @foreach($class->subjectAssignments as $assignment)
            @php $dot = sj_color($assignment->subject->name ?? ''); @endphp
            <div class="sj-subject-row">
              <span class="sj-subj-dot" style="background:{{ $dot }}"></span>
              <span class="sj-subj-name">{{ $assignment->subject->name ?? '—' }}</span>

              <form action="{{ route('subjects.assignment.update', $assignment) }}" method="POST" style="display:contents">
                @csrf @method('PUT')
                <select name="teacher_id" class="sj-teacher-pill {{ $assignment->teacher ? '' : 'unassigned' }}"
                        style="border:none;appearance:none;cursor:pointer"
                        onchange="this.form.submit()">
                  <option value="">— Unassigned —</option>
                  @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ $assignment->teacher_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                  @endforeach
                </select>
              </form>

              <div class="sj-row-actions">
                @php $courseForRow = $courseMap["{$class->id}-{$assignment->subject_id}"] ?? null; @endphp
                @if($courseForRow)
                  <a href="{{ route('lms.courses.show', $courseForRow->id) }}" class="sj-row-btn" title="View subject content" style="text-decoration:none">
                    <i class="fas fa-book-open"></i>
                  </a>
                @endif
                <form action="{{ route('subjects.assignment.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Remove this subject from the class?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="sj-row-btn danger" title="Remove from class"><i class="fas fa-xmark"></i></button>
                </form>
              </div>
            </div>
          @endforeach
        @endif

        <div class="sj-class-foot">
          <button type="button" class="sj-add-subject-btn" onclick="openAssignModal(null, {{ $class->id }})">
            <i class="fas fa-plus"></i> Assign Subject to This Class
          </button>
        </div>
      </div>
          @endforeach
        </div>
      </div>
    @endforeach
  @endif

  {{-- Subject catalog --}}
  <div class="sj-section-title">
    <h2><i class="fas fa-layer-group"></i> Subject Catalog</h2>
  </div>
  <div class="sj-card">
    <div style="overflow-x:auto">
      <table class="sj-table">
        <thead><tr><th>#</th><th>Name</th><th>Code</th><th>Credit Hrs</th><th>Assigned To</th><th>Actions</th></tr></thead>
        <tbody>
          @forelse($items as $item)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td><strong>{{ $item->name }}</strong>{{ $item->description ? ' — '.\Illuminate\Support\Str::limit($item->description, 50) : '' }}</td>
            <td>{{ $item->code ?? '—' }}</td>
            <td>{{ $item->credit_hours ?? '—' }}</td>
            <td>
              @if($item->class_assignments_count > 0)
                <span class="sj-badge"><i class="fas fa-school"></i> {{ $item->class_assignments_count }} class{{ $item->class_assignments_count === 1 ? '' : 'es' }}</span>
              @else
                <span class="sj-badge" style="background:#fff4e6;color:#b45309">Not assigned</span>
              @endif
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <button type="button" class="btn btn-outline btn-sm" onclick="openAssignModal({{ $item->id }})" title="Assign to classes"><i class="fas fa-diagram-project"></i></button>
                <a href="{{ route('subjects.edit', $item) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                <form action="{{ route('subjects.destroy', $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this subject? This also removes its class assignments.')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline btn-sm" style="color:var(--crimson)" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6"><div class="sj-empty"><i class="fas fa-book"></i><p>No subjects yet. Click "Add Subject" to create your first one.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── Assign Modal ── --}}
  <div id="assignModal" class="sj-modal-backdrop">
    <div class="sj-modal-box">
      <div class="sj-modal-header">
        <span class="sj-modal-title">Assign Subject to Classes</span>
        <button type="button" class="sj-modal-close" onclick="closeAssignModal()">&times;</button>
      </div>
      <form action="{{ route('subjects.assign') }}" method="POST">
        @csrf
        <div class="sj-modal-body">
          <div class="sj-form-group">
            <label class="sj-form-label">Subject *</label>
            <select name="subject_id" id="assign_subject_id" class="sj-form-control" required>
              <option value="">Select Subject</option>
              @foreach($items as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="sj-form-group">
            <label class="sj-form-label">Teacher</label>
            <select name="teacher_id" id="assign_teacher_id" class="sj-form-control">
              <option value="">— Unassigned for now —</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="sj-form-group">
            <label class="sj-form-label">Classes * <span style="text-transform:none;font-weight:500">(select one or more)</span></label>
            <div class="sj-class-checks" id="assign_class_checks">
              @foreach($classesByProgram as $programId => $programClasses)
                <div class="sj-class-check-group-label">{{ $programClasses->first()->program->name ?? 'No Program' }}</div>
                @foreach($programClasses as $class)
                  <label class="sj-class-check">
                    <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" class="assign-class-checkbox">
                    {{ $class->name }}{{ $class->section ? ' ('.$class->section.')' : '' }}
                  </label>
                @endforeach
              @endforeach
            </div>
          </div>
        </div>
        <div class="sj-modal-footer">
          <button type="button" class="btn-sj-outline" onclick="closeAssignModal()">Cancel</button>
          <button type="submit" class="btn-sj-primary"><i class="fas fa-save"></i> Save Assignment</button>
        </div>
      </form>
    </div>
  </div>

</div>

@push('scripts')
<script>
function openAssignModal(subjectId = null, classId = null) {
  document.getElementById('assign_subject_id').value = subjectId ?? '';
  document.querySelectorAll('.assign-class-checkbox').forEach(cb => cb.checked = false);
  if (classId) {
    const cb = document.querySelector('.assign-class-checkbox[value="' + classId + '"]');
    if (cb) cb.checked = true;
  }
  document.getElementById('assignModal').classList.add('open');
}
function closeAssignModal() { document.getElementById('assignModal').classList.remove('open'); }
document.getElementById('assignModal').addEventListener('click', e => { if (e.target.id === 'assignModal') closeAssignModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAssignModal(); });

@if($errors->any())
  openAssignModal();
@endif
</script>
@endpush

@endsection
