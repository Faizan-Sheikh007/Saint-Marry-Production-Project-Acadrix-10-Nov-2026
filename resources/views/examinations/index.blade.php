@extends('layouts.app')
@section('title', 'Examinations & Results')
@section('page_title', 'Examinations & Results')
@section('content')

@php
  $isAdmin      = auth()->user()->isAdmin();
  $indexRoute   = $isAdmin ? 'admin.examinations.index'   : 'teacher.examinations.index';
  $showRoute    = $isAdmin ? 'admin.examinations.show'    : 'teacher.examinations.show';
  $editRoute    = $isAdmin ? 'admin.examinations.edit'    : null;
  $destroyRoute = $isAdmin ? 'admin.examinations.destroy' : null;
  $batchDestroyRoute = $isAdmin ? 'admin.examinations.batch.destroy' : null;
  $resultsRoute = $isAdmin ? 'admin.examinations.results' : 'teacher.examinations.results';
  $lockRoute    = $isAdmin ? 'admin.examinations.lock'    : null;
  $unlockRoute  = $isAdmin ? 'admin.examinations.unlock'  : null;
@endphp

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-file-pen" style="color:var(--primary)"></i> Examinations & Results</div>
    <div class="page-subtitle">
      @if($isAdmin)
        Schedule exams by program, avoid clashes automatically, and track results across every class.
      @else
        Your assigned exam duties — enter marks for the classes and subjects you teach.
      @endif
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    @if($isAdmin)
      <a href="{{ route('admin.examinations.report') }}" class="btn btn-outline">
        <i class="fas fa-chart-column"></i> Reports
      </a>
      <a href="{{ route('admin.examinations.schedule') }}" class="btn btn-primary">
        <i class="fas fa-calendar-plus"></i> Schedule Examination
      </a>
    @endif
  </div>
</div>

{{-- Stats --}}
<div class="grid grid-4" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-layer-group"></i></div>
    <div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total Exams</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
    <div><div class="stat-value">{{ $stats['upcoming'] }}</div><div class="stat-label">Upcoming</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div><div class="stat-value">{{ $stats['completed'] }}</div><div class="stat-label">Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-clipboard-list"></i></div>
    <div><div class="stat-value">{{ $stats['pending_marks'] }}</div><div class="stat-label">Awaiting Marks</div></div>
  </div>
</div>

@if($isAdmin)
@php
  $excName  = \App\Models\Setting::get('exam_controller_name');
  $excTitle = \App\Models\Setting::get('exam_controller_title', 'Controller of Examinations');
  $excSigRaw = \App\Models\Setting::get('exam_controller_signature');
  // Newer signatures are saved as a base64 data URI straight in the database;
  // older ones (from before this feature stored images in the DB) may still
  // be a path on the storage disk, so render either correctly.
  $excSig = $excSigRaw
      ? (str_starts_with($excSigRaw, 'data:') ? $excSigRaw : storage_asset($excSigRaw))
      : null;
@endphp
{{-- Examination Controller signature — shown on every printed marksheet --}}
<details class="card" style="margin-bottom:20px">
  <summary style="cursor:pointer;padding:14px 20px;font-weight:600;list-style:none;display:flex;align-items:center;gap:8px">
    <i class="fas fa-signature" style="color:var(--primary)"></i> Examination Controller Signature
    <span style="font-weight:400;font-size:12px;color:var(--text-muted)">— appears on every marksheet</span>
  </summary>
  <div class="card-body" style="border-top:1px solid var(--border)">
    @if(session('success'))
      <div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.examinations.controller-signature.update') }}" enctype="multipart/form-data">
      @csrf
      <div class="grid grid-2" style="gap:12px;margin-bottom:12px">
        <div class="form-group"><label class="form-label">Name</label>
          <input type="text" name="exam_controller_name" class="form-control" placeholder="e.g. Dr. Ahmed Raza"
                 value="{{ old('exam_controller_name', $excName) }}"></div>
        <div class="form-group"><label class="form-label">Designation</label>
          <input type="text" name="exam_controller_title" class="form-control" placeholder="e.g. Controller of Examinations"
                 value="{{ old('exam_controller_title', $excTitle) }}"></div>
      </div>

      @if($excSig)
        <div style="display:flex;align-items:center;gap:14px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:14px;background:var(--bg)">
          <img src="{{ $excSig }}" alt="Examination controller signature"
               style="height:44px;max-width:160px;object-fit:contain;background:#fff;border-radius:4px;padding:4px">
          <div style="flex:1;font-size:12px;color:var(--text-muted)">Signature on file (stored in the database) — used on every printed marksheet.</div>
          <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--danger);cursor:pointer;white-space:nowrap">
            <input type="checkbox" name="remove_exam_controller_signature" value="1"> Remove
          </label>
        </div>
      @endif

      <div class="sig-tabs" style="display:flex;gap:8px;margin-bottom:10px">
        <button type="button" class="btn btn-outline btn-sm excon-tab-btn active" data-tab="draw" onclick="switchExconTab('draw')"><i class="fas fa-pen-nib"></i> Draw</button>
        <button type="button" class="btn btn-outline btn-sm excon-tab-btn" data-tab="upload" onclick="switchExconTab('upload')"><i class="fas fa-upload"></i> Upload Image</button>
      </div>

      <div id="excon-tab-draw">
        <div style="border:2px dashed var(--border);border-radius:8px;overflow:hidden;background:#fff">
          <canvas id="excon-pad" width="500" height="140" style="width:100%;height:140px;cursor:crosshair;touch-action:none;display:block"></canvas>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
          <span style="font-size:11px;color:var(--text-muted)">Sign above with mouse / touch / stylus.</span>
          <button type="button" class="btn btn-outline btn-sm" onclick="clearExconPad()"><i class="fas fa-eraser"></i> Clear</button>
        </div>
        <input type="hidden" name="exam_controller_signature_data" id="exam_controller_signature_data">
      </div>

      <div id="excon-tab-upload" style="display:none">
        <input type="file" name="exam_controller_signature" class="form-control" accept="image/png,image/jpeg" onchange="previewExconUpload(this)">
        <img id="excon-upload-preview" style="display:none;height:50px;margin-top:10px;background:#fff;border:1px solid var(--border);border-radius:4px;padding:4px">
        <div style="font-size:11px;color:var(--text-muted);margin-top:6px">PNG with a transparent background works best.</div>
      </div>
      @error('exam_controller_signature')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror

      <div style="margin-top:16px;text-align:right">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Signature</button>
      </div>
    </form>
  </div>
</details>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" id="exam-filter-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div style="flex:1;min-width:160px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Exam name…" value="{{ request('search') }}">
      </div>
      <div style="min-width:165px">
        <label class="form-label">Session</label>
        <select name="academic_year_id" id="exam-filter-session" class="form-control">
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
              {{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}
            </option>
          @endforeach
        </select>
      </div>
      <div style="min-width:190px">
        <label class="form-label">Program</label>
        <select name="program_id" id="exam-filter-program" class="form-control" data-selected="{{ request('program_id') }}">
          <option value="">Select Session first</option>
        </select>
      </div>
      <div style="min-width:175px">
        <label class="form-label">Semester / Part</label>
        <select name="school_class_id" id="exam-filter-class" class="form-control" data-selected="{{ request('school_class_id') }}">
          <option value="">Select Program first</option>
        </select>
      </div>
      <div style="min-width:175px">
        <label class="form-label">Subject</label>
        <select name="subject_id" id="exam-filter-subject" class="form-control" data-selected="{{ request('subject_id') }}">
          <option value="">Select Semester / Part first</option>
        </select>
      </div>
      @if($isAdmin)
      <div style="min-width:175px">
        <label class="form-label">Teacher</label>
        <select name="teacher_id" id="exam-filter-teacher" class="form-control" data-selected="{{ request('teacher_id') }}">
          <option value="">Select Subject first</option>
        </select>
      </div>
      @endif
      <div style="min-width:130px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">Any</option>
          <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
          <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route($indexRoute) }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
      </div>
      <div style="flex-basis:100%;font-size:12px;color:var(--text-muted);margin-top:1px">
        Select <strong>Session → Program → Semester / Part → Subject</strong>. Each dropdown only shows records linked to the selection before it.
      </div>
    </form>
  </div>
</div>
@endif

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->has('exam_date'))
  <div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first('exam_date') }}</div>
@endif
@if($selectedCurriculumClass)
<div class="card" style="margin-bottom:20px">
 <div class="card-header"><div class="card-title">All Subjects — {{ $selectedCurriculumClass->display_name }} ({{ $selectedCurriculum->count() }})</div></div>
 <div class="card-body"><p class="text-sm text-muted">The complete assigned curriculum for this semester, including subjects without a scheduled examination. Examination filters below do not remove subjects from this list.</p>
 <div class="table-wrap"><table><thead><tr><th>Subject</th><th>Code</th><th>Assigned Teacher</th></tr></thead><tbody>
 @forelse($selectedCurriculum as $row)<tr><td>{{ $row->subject->name }}</td><td>{{ $row->subject->code }}</td><td>{{ $row->teacher->name ?? 'Unassigned' }}</td></tr>
 @empty<tr><td colspan="3">No subjects assigned to this semester.</td></tr>@endforelse
 </tbody></table></div></div>
</div>
@endif
{{-- Batches --}}
@php
  $statusMap = ['scheduled' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
@endphp
@forelse($batches as $batchKey => $group)
@php
  $first = $group->first();
  $isBatch = str_starts_with($batchKey, 'single-') === false;
  // Structure this schedule as Program -> Class/Section -> Subjects, instead of one flat table.
  $byProgram = $group->groupBy(fn ($e) => $e->program->id ?? $e->schoolClass->program_id ?? 0);
@endphp
<div class="card" style="margin-bottom:18px">
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <div style="width:42px;height:42px;border-radius:10px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:17px;flex-shrink:0">
        <i class="fas fa-file-pen"></i>
      </div>
      <div>
        <div class="card-title">{{ $first->name }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
          <span class="tag">{{ $first->type_label }}</span>
          <span style="margin-left:6px">{{ $group->count() }} class/subject pairing{{ $group->count() === 1 ? '' : 's' }}</span>
        </div>
      </div>
    </div>
    @if($isAdmin)
      <a href="{{ route('admin.examinations.date-sheet.print', $isBatch ? ['batch_id' => $first->batch_id] : ['search' => $first->name, 'school_class_id' => $first->school_class_id]) }}" target="_blank" class="btn btn-outline btn-sm" title="Print Date Sheet">
        <i class="fas fa-print"></i> Print Date Sheet
      </a>
    @endif
    @if($isAdmin && $isBatch)
      <form action="{{ route($batchDestroyRoute, $first->batch_id) }}" method="POST" onsubmit="return confirm('Delete all {{ $group->count() }} exams in this schedule?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-trash"></i> Remove Schedule</button>
      </form>
    @endif
  </div>

  @foreach($byProgram as $programId => $programItems)
  @php $programName = $programItems->first()->program->name ?? $programItems->first()->schoolClass->program->name ?? 'General'; @endphp
  <div class="program-group">
    <div class="program-group-header">
      <div class="program-icon">{{ strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $programName), 0, 4)) ?: '—' }}</div>
      <div>
        <div class="program-group-title">{{ $programName }}</div>
        <div class="program-group-sub">{{ $programItems->pluck('school_class_id')->unique()->count() }} class{{ $programItems->pluck('school_class_id')->unique()->count() === 1 ? '' : 'es' }} &middot; {{ $programItems->count() }} exam{{ $programItems->count() === 1 ? '' : 's' }}</div>
      </div>
    </div>

    @foreach($programItems->groupBy('school_class_id') as $classId => $classItems)
    @php
      $sc = $classItems->first()->schoolClass;
      $missingSubjects = $curriculumRows->get($classId, collect())->reject(fn ($row) => $classItems->contains('subject_id', $row->subject_id));
      if (request()->filled('subject_id') || request()->filled('status') || request()->filled('teacher_id') || request()->filled('search')) $missingSubjects = collect();
      $visibleSubjectCount = $classItems->pluck('subject_id')->unique()->count() + $missingSubjects->count();
    @endphp
    <div class="class-block">
      <div class="class-block-header">
        <span class="class-pill"><i class="fas fa-users-rectangle"></i> {{ $sc->section_label ?? $sc->name ?? '—' }}</span>
        <span class="class-block-count">{{ $visibleSubjectCount }} subjects · {{ $classItems->count() }} scheduled exam rows</span>
      </div>

      <div style="display:grid;grid-template-columns:54px minmax(185px,1.1fr) minmax(200px,1.6fr) minmax(135px,1fr) minmax(90px,.8fr) minmax(90px,.7fr) auto;gap:12px;padding:9px 15px;font-size:11px;font-weight:700;color:var(--text-muted);background:var(--bg);border-bottom:1px solid var(--border)">
        <span>Sr. No.</span><span>Date &amp; Day</span><span>Subject</span><span>Teacher</span><span>Progress</span><span>Status</span><span>Actions</span>
      </div>
      @foreach($classItems->sortBy('exam_date')->values() as $item)
      @php
        $expected = \App\Models\Student::where('school_class_id', $item->school_class_id)->where('status','active')->count();
        $entered  = $item->results_count;
        $pct      = $expected > 0 ? round(($entered / $expected) * 100) : 0;
      @endphp
      <div class="subject-row" style="display:grid;grid-template-columns:54px minmax(185px,1.1fr) minmax(200px,1.6fr) minmax(135px,1fr) minmax(90px,.8fr) minmax(90px,.7fr) auto;gap:12px;align-items:center">
        <div style="text-align:center;font-weight:700" title="Sr. No.">{{ $loop->iteration }}</div>
        <div class="subject-schedule">
          <div style="font-weight:600"><i class="fas fa-calendar-day" style="color:var(--text-muted);margin-right:4px"></i>{{ $item->exam_date?->format('d M Y, l') ?? '—' }}</div>
          @if($isAdmin)
            <form action="{{ route('admin.examinations.date.update', $item) }}" method="POST" style="display:flex;gap:4px;align-items:center;margin-top:4px;max-width:190px">
              @csrf @method('PATCH')
              <input id="exam-date-{{ $item->id }}" type="date" name="exam_date" aria-label="New exam date" required value="{{ $item->exam_date?->format('Y-m-d') }}" style="max-width:133px;min-width:0;padding:3px;font-size:11px;border:1px solid var(--border);border-radius:5px;background:var(--bg)" />
              <button type="submit" title="Save new exam date" class="btn btn-outline btn-sm" style="padding:4px 6px"><i class="fas fa-check"></i></button>
            </form>
          @endif
          @if($item->time_label)<div style="font-size:11px;color:var(--text-muted);margin-top:1px">{{ $item->time_label }}</div>@endif
        </div>

        <div class="subject-info">
          <div class="subject-name">{{ $item->subject->name ?? 'All Subjects' }}</div>
          <div class="subject-meta">{{ $item->total_marks }} marks &middot; Pass {{ $item->passing_marks }} &middot; Room {{ $item->room ?: '—' }}</div>
        </div>

        <div class="subject-teacher">
          @if($item->teacher)
            <div style="width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;font-size:10.5px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              {{ strtoupper(substr($item->teacher->name,0,2)) }}
            </div>
            <span style="font-size:12.5px">{{ $item->teacher->name }}</span>
          @else
            <span style="color:var(--text-muted);font-size:12px"><i class="fas fa-user-slash"></i> Unassigned</span>
          @endif
        </div>

        <div class="subject-progress">
          <div class="progress" style="margin-bottom:3px"><div class="progress-bar {{ $pct >= 100 ? 'success' : ($pct > 0 ? 'warning' : 'primary') }}" style="width:{{ $pct }}%"></div></div>
          <div style="font-size:11px;color:var(--text-muted)">{{ $entered }}/{{ $expected }} marked</div>
        </div>

        <div>
          <span class="badge {{ $statusMap[$item->status] ?? 'gray' }}">{{ ucfirst($item->status) }}</span>
          @if($item->isLocked())<div style="font-size:10px;color:var(--text-muted);margin-top:3px"><i class="fas fa-lock"></i> Locked</div>@endif
        </div>

        <div class="subject-actions">
          <a href="{{ route($showRoute, $item) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
          <a href="{{ route($resultsRoute, $item) }}" class="btn btn-outline btn-sm" title="Marks"><i class="fas fa-clipboard-list"></i></a>
          @if($isAdmin)
            <button type="button" class="btn btn-outline btn-sm" title="Change exam date" onclick="document.getElementById('exam-date-{{ $item->id }}').showPicker ? document.getElementById('exam-date-{{ $item->id }}').showPicker() : document.getElementById('exam-date-{{ $item->id }}').focus()"><i class="fas fa-calendar-days"></i></button>
            <a href="{{ route($editRoute, $item) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
            @if($item->isLocked())
              <form action="{{ route($unlockRoute, $item) }}" method="POST" style="display:inline">
                @csrf
                <button class="btn btn-outline btn-sm" title="Unlock marks"><i class="fas fa-unlock"></i></button>
              </form>
            @else
              <form action="{{ route($lockRoute, $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Lock this exam? The teacher will no longer be able to edit marks.')">
                @csrf
                <button class="btn btn-outline btn-sm" title="Lock marks"><i class="fas fa-lock"></i></button>
              </form>
            @endif
            <form action="{{ route($destroyRoute, $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this examination?')">
              @csrf @method('DELETE')
              <button class="btn btn-outline btn-sm" style="color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          @endif
        </div>
      </div>
      @endforeach
      @foreach($missingSubjects as $missing)
      <div class="subject-row" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
        <div style="flex:1"><strong>{{ $missing->subject->name }}</strong><div class="text-sm text-muted">{{ $missing->teacher->name ?? 'Teacher unassigned' }}</div></div>
        <span class="badge warning">Not scheduled in this batch</span>
        @if($isAdmin)<a class="btn btn-outline btn-sm" href="{{ route('admin.examinations.schedule') }}">Schedule Exam</a>@endif
      </div>
      @endforeach
    </div>
    @endforeach
  </div>
  @endforeach
</div>
@empty
<div class="card">
  <div class="empty-state">
    <i class="fas fa-file-circle-plus"></i>
    <p>No examinations found.</p>
    @if($isAdmin)
      <a href="{{ route('admin.examinations.schedule') }}" class="btn btn-primary" style="margin-top:14px">
        <i class="fas fa-calendar-plus"></i> Schedule your first examination
      </a>
    @endif
  </div>
</div>
@endforelse

<div style="padding:4px 0">{{ $items->links() }}</div>

@push('scripts')
<script>
const examAcademicStructure = @json($filterStructure ?? []);

(function initExamAcademicCascade() {
    const sessionSelect = document.getElementById('exam-filter-session');
    const programSelect = document.getElementById('exam-filter-program');
    const classSelect   = document.getElementById('exam-filter-class');
    const subjectSelect = document.getElementById('exam-filter-subject');
    const teacherSelect = document.getElementById('exam-filter-teacher');
    if (!sessionSelect || !programSelect || !classSelect || !subjectSelect) return;

    const selectedProgram = String(programSelect.dataset.selected || '');
    const selectedClass   = String(classSelect.dataset.selected || '');
    const selectedSubject = String(subjectSelect.dataset.selected || '');
    const selectedTeacher = teacherSelect ? String(teacherSelect.dataset.selected || '') : '';

    const currentSession = () => examAcademicStructure[String(sessionSelect.value)] || null;
    const currentProgram = () => {
        const session = currentSession();
        return session ? (session.programs || []).find(p => String(p.id) === String(programSelect.value)) : null;
    };
    const currentTerm = () => {
        const program = currentProgram();
        return program ? (program.terms || []).find(t => String(t.id) === String(classSelect.value)) : null;
    };
    const currentSubject = () => {
        const term = currentTerm();
        return term ? (term.subjects || []).find(s => String(s.id) === String(subjectSelect.value)) : null;
    };

    function fillPrograms(keepValue = '') {
        const session = currentSession();
        const programs = session?.programs || [];
        programSelect.innerHTML = !session
            ? '<option value="">Select Session first</option>'
            : (programs.length
                ? '<option value="">Select Program</option>' + programs.map(p => `<option value="${p.id}">${p.name}</option>`).join('')
                : '<option value="">No programs linked to this session</option>');
        programSelect.disabled = !session || programs.length === 0;
        if (keepValue && programs.some(p => String(p.id) === String(keepValue))) programSelect.value = keepValue;
        fillClasses(keepValue ? selectedClass : '');
    }

    function fillClasses(keepValue = '') {
        const program = currentProgram();
        const terms = program?.terms || [];
        classSelect.innerHTML = !program
            ? '<option value="">Select Program first</option>'
            : (terms.length
                ? '<option value="">Select Semester / Part</option>' + terms.map(t => `<option value="${t.id}">${t.name}</option>`).join('')
                : '<option value="">No semesters linked to this program/session</option>');
        classSelect.disabled = !program || terms.length === 0;
        if (keepValue && terms.some(t => String(t.id) === String(keepValue))) classSelect.value = keepValue;
        fillSubjects(keepValue ? selectedSubject : '');
    }

    function fillSubjects(keepValue = '') {
        const term = currentTerm();
        const subjects = term?.subjects || [];
        subjectSelect.innerHTML = !term
            ? '<option value="">Select Semester / Part first</option>'
            : (subjects.length
                ? '<option value="">Select Subject</option>' + subjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('')
                : '<option value="">No subjects assigned to this semester</option>');
        subjectSelect.disabled = !term || subjects.length === 0;
        if (keepValue && subjects.some(s => String(s.id) === String(keepValue))) subjectSelect.value = keepValue;
        fillTeachers(keepValue ? selectedTeacher : '');
    }

    function fillTeachers(keepValue = '') {
        if (!teacherSelect) return;
        const subject = currentSubject();
        const teachers = subject?.teachers || [];
        teacherSelect.innerHTML = !subject
            ? '<option value="">Select Subject first</option>'
            : (teachers.length
                ? '<option value="">All Teachers for this Subject</option>' + teachers.map(t => `<option value="${t.id}">${t.name}</option>`).join('')
                : '<option value="">No teacher assigned in curriculum</option>');
        teacherSelect.disabled = !subject || teachers.length === 0;
        if (keepValue && teachers.some(t => String(t.id) === String(keepValue))) teacherSelect.value = keepValue;
    }

    sessionSelect.addEventListener('change', () => fillPrograms());
    programSelect.addEventListener('change', () => fillClasses());
    classSelect.addEventListener('change', () => fillSubjects());
    subjectSelect.addEventListener('change', () => fillTeachers());

    fillPrograms(selectedProgram);
})();

@if($isAdmin)
function previewExconUpload(input) {
    const preview = document.getElementById('excon-upload-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'inline-block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function switchExconTab(tab) {
    document.getElementById('excon-tab-draw').style.display   = tab === 'draw'   ? 'block' : 'none';
    document.getElementById('excon-tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.querySelectorAll('.excon-tab-btn').forEach(b => {
        const active = b.dataset.tab === tab;
        b.classList.toggle('active', active);
        b.style.background = active ? 'var(--primary)' : '';
        b.style.color      = active ? '#fff' : '';
        b.style.borderColor = active ? 'var(--primary)' : '';
    });
}

// ── Examination controller's signature pad (draw with mouse / touch / stylus) ──
(function () {
    const canvas = document.getElementById('excon-pad');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let drawing = false, hasDrawn = false, ratio = 1;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        ratio = window.devicePixelRatio || 1;
        canvas.width  = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1a0608';
    }
    resize();
    window.addEventListener('resize', resize);

    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }
    function start(e) { drawing = true; hasDrawn = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e)  { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end()     { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    window.clearExconPad = function () {
        ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        hasDrawn = false;
        document.getElementById('exam_controller_signature_data').value = '';
    };

    const form = canvas.closest('form');
    form.addEventListener('submit', function () {
        if (hasDrawn) {
            document.getElementById('exam_controller_signature_data').value = canvas.toDataURL('image/png');
        }
    });
})();
@endif
</script>
@endpush

@endsection
