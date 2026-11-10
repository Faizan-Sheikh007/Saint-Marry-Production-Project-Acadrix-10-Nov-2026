@extends('layouts.app')
@section('title', 'Attendance')
@section('content')

@php
  $isAdmin     = auth()->user()->isAdmin();
  $saveRoute   = $isAdmin ? route('admin.attendance.save')  : route('teacher.attendance.save');
  $reportRoute = $isAdmin ? 'admin.attendance.report'       : 'teacher.attendance.report';
  $indexRoute  = $isAdmin ? route('admin.attendance.index') : route('teacher.attendance.index');
  $printRoute  = $isAdmin ? 'admin.attendance.print'        : 'teacher.attendance.print';
  $alreadySaved = !$isAdmin && $existingAttendance->isNotEmpty();
@endphp

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root {
  --crimson:      #8b0c22;
  --crimson-soft: rgba(139,12,34,.08);
  --bg:           #f7f0f1;
  --surface:      #ffffff;
  --border:       rgba(139,12,34,.10);
  --text:         #1a0608;
  --muted:        #8a5860;
  --radius:       14px;
  --shadow:       0 2px 16px rgba(139,12,34,.07);
  --shadow-lg:    0 8px 40px rgba(139,12,34,.13);
  --green:  #16a34a;
  --red:    #dc2626;
  --orange: #d97706;
  --indigo: #6366f1;
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

/* ── Page Header ── */
.att-header {
  display:flex; align-items:flex-end; justify-content:space-between;
  flex-wrap:wrap; gap:12px; margin-bottom:22px;
}
.att-header h1 { font-family:'Outfit', sans-serif; font-size:22px; font-weight:800; color:var(--text); }
.att-header p  { font-size:13px; color:var(--muted); margin-top:3px; }

.att-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ── */
.btn-primary {
  display:inline-flex; align-items:center; gap:7px;
  padding:9px 18px; border-radius:9px; border:none;
  background:var(--crimson); color:#fff;
  font-family:'Outfit', sans-serif; font-size:13px; font-weight:700;
  cursor:pointer; transition:background .15s;
  text-decoration:none;
}
.btn-primary:hover { background:#a81030; }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; }

.btn-outline {
  display:inline-flex; align-items:center; gap:7px;
  padding:8px 16px; border-radius:9px; border:1.5px solid var(--border);
  background:var(--surface); color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:13px; font-weight:600;
  cursor:pointer; transition:border-color .15s, color .15s;
  text-decoration:none;
}
.btn-outline:hover { border-color:var(--crimson); color:var(--crimson); }

/* ── Filter Card ── */
.filter-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); padding:14px 20px; margin-bottom:18px;
  box-shadow:var(--shadow);
}
.filter-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.form-group  { margin-bottom:0; }
.form-label  { display:block; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin-bottom:5px; }
.form-control {
  padding:9px 13px; border-radius:8px; border:1.5px solid var(--border);
  background:var(--surface); color:var(--text);
  font-family:'Outfit', sans-serif; font-size:13px;
  transition:border-color .15s;
}
.form-control:focus { outline:none; border-color:var(--crimson); }

/* ── Stat Mini Cards ── */
.stat-row  { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.stat-mini {
  flex:1; min-width:110px;
  background:var(--surface); border:1px solid var(--border);
  border-radius:12px; padding:14px 16px;
  display:flex; align-items:center; gap:12px;
  box-shadow:var(--shadow);
}
.stat-mini-icon {
  width:38px; height:38px; border-radius:9px;
  display:flex; align-items:center; justify-content:center; font-size:15px;
  flex-shrink:0;
}
.stat-mini-val  { font-family:'Outfit', sans-serif; font-size:20px; font-weight:800; line-height:1; }
.stat-mini-lbl  { font-size:11px; color:var(--muted); margin-top:3px; }

/* ── Table Card ── */
.att-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow);
}
.att-card-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:8px;
}
.att-card-title { font-family:'Outfit', sans-serif; font-size:15px; font-weight:700; }

.att-table { width:100%; border-collapse:collapse; }
.att-table th {
  font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  color:var(--muted); padding:10px 14px;
  background:#fdf6f7; border-bottom:1px solid var(--border); text-align:left;
}
.att-table th.center { text-align:center; }
.att-table td { padding:10px 14px; border-top:1px solid var(--border); font-size:13.5px; }
.att-table tr.clickable { cursor:pointer; transition:background .12s; }
.att-table tr.clickable:hover td { background:#fdf6f7; }

/* student cell */
.stu-cell { display:flex; align-items:center; gap:10px; }
.stu-avatar {
  width:34px; height:34px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-family:'Outfit', sans-serif; font-size:12px; font-weight:700; color:#fff;
}
.stu-name { font-weight:600; font-size:13.5px; }
.stu-id   { font-size:11px; color:var(--muted); }

/* radio attendance */
.radio-wrap { display:flex; justify-content:center; }
.radio-wrap input[type=radio] { width:17px; height:17px; cursor:pointer; }

/* badge */
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-success { background:rgba(22,163,74,.1);  color:var(--green); }
.badge-warning { background:rgba(217,119,6,.1);  color:var(--orange); }

/* already-saved notice */
.notice-saved {
  display:flex; align-items:flex-start; gap:12px;
  background:#f0fdf4; border:1px solid #bbf7d0;
  border-radius:12px; padding:14px 18px; margin-bottom:18px;
  color:#166534; font-size:13.5px;
}
.notice-saved i { font-size:18px; flex-shrink:0; margin-top:1px; color:var(--green); }
.notice-saved strong { font-weight:700; display:block; margin-bottom:2px; }

/* flash */
.flash-success {
  display:flex; align-items:center; gap:10px;
  background:#f0fdf4; border:1px solid #bbf7d0;
  border-radius:10px; padding:12px 18px; margin-bottom:16px;
  font-size:13.5px; color:#166534;
}

/* ── Monthly Map Modal ── */
.map-backdrop {
  display:none; position:fixed; inset:0;
  background:rgba(15,5,7,.50); backdrop-filter:blur(4px);
  z-index:2000; align-items:center; justify-content:center;
}
.map-backdrop.open { display:flex; }
.map-box {
  background:var(--surface); border-radius:20px;
  width:100%; max-width:580px; margin:20px;
  box-shadow:0 24px 60px rgba(139,12,34,.22);
  animation:mapIn .25s cubic-bezier(.34,1.56,.64,1);
  overflow:hidden;
}
@keyframes mapIn { from{opacity:0;transform:scale(.93) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }

.map-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:20px 24px 16px; border-bottom:1px solid var(--border);
}
.map-title { font-family:'Outfit', sans-serif; font-size:16px; font-weight:800; }
.map-close {
  width:32px; height:32px; border-radius:8px; border:none;
  background:var(--crimson-soft); color:var(--muted);
  display:flex; align-items:center; justify-content:center;
  font-size:16px; cursor:pointer; transition:background .12s, color .12s;
}
.map-close:hover { background:var(--crimson); color:#fff; }

.map-body { padding:20px 24px; }

/* legend */
.map-legend {
  display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;
}
.map-leg-item { display:flex; align-items:center; gap:6px; font-size:12.5px; font-weight:500; color:var(--text); }
.map-leg-dot  { width:13px; height:13px; border-radius:4px; }

/* calendar grid */
.map-grid {
  display:grid; grid-template-columns:repeat(7,1fr); gap:6px;
  margin-bottom:20px;
}
.map-day-name {
  font-size:10px; font-weight:700; letter-spacing:.06em;
  text-transform:uppercase; color:var(--muted);
  text-align:center; padding-bottom:4px;
}
.map-cell {
  aspect-ratio:1; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-family:'Outfit', sans-serif; font-size:13px; font-weight:800;
  color:#fff; transition:transform .1s;
  position:relative;
}
.map-cell:hover { transform:scale(1.08); z-index:1; }
.map-cell.present  { background:#16a34a; }
.map-cell.absent   { background:#dc2626; }
.map-cell.late     { background:#d97706; }
.map-cell.leave    { background:#f59e0b; }
.map-cell.holiday  { background:#fde8e8; color:#c0192e; }
.map-cell.future   { background:#f3f3f5; color:#bbb; }
.map-cell.empty    { background:transparent; }

/* summary row */
.map-summary {
  display:flex; gap:24px; flex-wrap:wrap;
  padding-top:18px; border-top:1px solid var(--border);
}
.map-sum-item {}
.map-sum-val { font-family:'Outfit', sans-serif; font-size:22px; font-weight:800; line-height:1; }
.map-sum-lbl { font-size:12px; color:var(--muted); margin-top:3px; }
</style>

{{-- Flash --}}
@if(session('success'))
<div class="flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Header --}}
<div class="att-header">
  <div>
    <h1>Attendance</h1>
    <p><i class="fas fa-calendar-day"></i> {{ $date->format('l, d M Y') }}</p>
  </div>
  <div class="att-actions">
    <a href="{{ route($reportRoute, ['academic_year_id' => $selectedYearId, 'class_id' => $selectedClass?->id, 'date' => $date->format('Y-m-d')]) }}" class="btn-outline">
      <i class="fas fa-chart-bar"></i> View Report
    </a>
    <a href="{{ route($reportRoute, ['academic_year_id' => $selectedYearId, 'class_id' => $selectedClass?->id, 'date' => $date->format('Y-m-d'), 'download' => 1]) }}" class="btn-outline">
      <i class="fas fa-file-excel"></i> Export
    </a>
    <a href="{{ route($printRoute, ['academic_year_id' => $selectedYearId, 'class_id' => $selectedClass?->id, 'date' => $date->format('Y-m-d'), 'subject_id' => $selectedSubjectId]) }}" target="_blank" class="btn-outline">
      <i class="fas fa-print"></i> Print
    </a>
  </div>
</div>

{{-- Already-saved notice --}}
@if($alreadySaved)
<div class="notice-saved">
  <i class="fas fa-check-circle"></i>
  <div>
    <strong>Attendance Already Submitted</strong>
    You submitted attendance for <strong>{{ $date->format('l, d M Y') }}</strong>. Contact admin if changes are needed.
  </div>
</div>
@endif

{{-- Filters --}}
<div class="filter-card">
  <form method="GET" action="{{ $indexRoute }}" id="attendance-filter-form" data-ajax-filter>
    <div class="filter-row">
      <div class="form-group" style="min-width:175px">
        <label class="form-label">Session</label>
        <select name="academic_year_id" id="attendance-session" class="form-control">
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="min-width:230px">
        <label class="form-label">Program</label>
        <select name="program_id" id="attendance-program" class="form-control" data-selected="{{ $selectedProgram?->id }}">
          <option value="">Select Session first</option>
        </select>
      </div>
      <div class="form-group" style="min-width:185px">
        <label class="form-label">Semester / Part</label>
        <select name="class_id" id="attendance-class" class="form-control" data-selected="{{ $selectedClass?->id }}">
          <option value="">Select Program first</option>
        </select>
      </div>
      <div class="form-group" style="min-width:150px">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
      </div>
      <div class="form-group" style="min-width:210px">
        <label class="form-label">Subject</label>
        <select name="subject_id" id="attendance-subject" class="form-control" data-selected="{{ $selectedSubjectId }}">
          <option value="">Select Semester / Part first</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end">
        <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Load Attendance</button>
        <a href="{{ $indexRoute }}" class="btn-outline" data-ajax-filter-link>Reset</a>
      </div>
    </div>
    <div style="font-size:11.5px;color:var(--muted);margin-top:9px">Select Session → Program → Semester / Part. Each next list only contains records linked to the previous choice.</div>
  </form>
</div>


{{-- Stat row --}}
@if($existingAttendance->isNotEmpty())
@php
  $presentCount = $existingAttendance->filter(fn($s) => $s === 'present')->count();
  $absentCount  = $existingAttendance->filter(fn($s) => $s === 'absent')->count();
  $lateCount    = $existingAttendance->filter(fn($s) => $s === 'late')->count();
  $leaveCount   = $existingAttendance->filter(fn($s) => $s === 'leave')->count();
@endphp
<div class="stat-row">
  <div class="stat-mini">
    <div class="stat-mini-icon" style="background:rgba(22,163,74,.1)"><i class="fas fa-user-check" style="color:var(--green)"></i></div>
    <div><div class="stat-mini-val" style="color:var(--green)">{{ $presentCount }}</div><div class="stat-mini-lbl">Present</div></div>
  </div>
  <div class="stat-mini">
    <div class="stat-mini-icon" style="background:rgba(220,38,38,.1)"><i class="fas fa-user-times" style="color:var(--red)"></i></div>
    <div><div class="stat-mini-val" style="color:var(--red)">{{ $absentCount }}</div><div class="stat-mini-lbl">Absent</div></div>
  </div>
  <div class="stat-mini">
    <div class="stat-mini-icon" style="background:rgba(217,119,6,.1)"><i class="fas fa-user-clock" style="color:var(--orange)"></i></div>
    <div><div class="stat-mini-val" style="color:var(--orange)">{{ $lateCount }}</div><div class="stat-mini-lbl">Late</div></div>
  </div>
  <div class="stat-mini">
    <div class="stat-mini-icon" style="background:rgba(99,102,241,.1)"><i class="fas fa-user-slash" style="color:var(--indigo)"></i></div>
    <div><div class="stat-mini-val" style="color:var(--indigo)">{{ $leaveCount }}</div><div class="stat-mini-lbl">Leave</div></div>
  </div>
</div>
@endif

{{-- Main Table --}}
@if($students->isEmpty())
  <div class="att-card" style="padding:60px;text-align:center;color:var(--muted)">
    <i class="fas fa-users" style="font-size:36px;opacity:.25;display:block;margin-bottom:12px"></i>
    @if(!$selectedClass)
      Select a Session, Program and Semester / Part to load attendance.
    @else
      No active students found for this Semester / Part in {{ $selectedYear?->name ?? 'the selected session' }}.
    @endif
  </div>
@else
<form action="{{ $saveRoute }}" method="POST">
  @csrf
  <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
  <input type="hidden" name="school_class_id" value="{{ $selectedClass->id }}">
  <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
  <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">

  <div class="att-card">
    <div class="att-card-header">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="att-card-title">{{ $selectedClass->name }} &mdash; {{ $students->count() }} Students</span>
        @php $subjectLabel = $selectedSubjectId ? ($subjects->firstWhere('id', $selectedSubjectId)->name ?? 'Subject') : null; @endphp
        @if($subjectLabel)
          <span class="badge badge-info">{{ $subjectLabel }}</span>
        @else
          <span class="badge badge-info">General / Whole Class</span>
        @endif
        @if($existingAttendance->isNotEmpty())
          <span class="badge badge-success"><i class="fas fa-check" style="font-size:9px"></i> {{ $isAdmin ? 'Saved' : 'Submitted' }}</span>
        @else
          <span class="badge badge-warning">Not Marked Yet</span>
        @endif
      </div>
      @if(!$alreadySaved)
      <div style="display:flex;gap:8px">
        <button type="button" onclick="markAll('present')" class="btn-outline" style="padding:6px 12px;font-size:12px;color:var(--green);border-color:rgba(22,163,74,.3)">
          <i class="fas fa-check"></i> All Present
        </button>
        <button type="button" onclick="markAll('absent')" class="btn-outline" style="padding:6px 12px;font-size:12px;color:var(--red);border-color:rgba(220,38,38,.3)">
          <i class="fas fa-times"></i> All Absent
        </button>
      </div>
      @endif
    </div>

    <div style="overflow-x:auto">
      <table class="att-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Roll No</th>
            <th class="center" style="color:var(--green)">Present</th>
            <th class="center" style="color:var(--red)">Absent</th>
            <th class="center" style="color:var(--orange)">Late</th>
            <th class="center" style="color:var(--indigo)">Leave</th>
            <th class="center">Map</th>
          </tr>
        </thead>
        <tbody>
          @php $avatarColors = ['#8b0c22','#2563eb','#0d9e6e','#7c3aed','#d97706','#0891b2','#be185d']; @endphp
          @foreach($students as $student)
          @php $status = $existingAttendance[$student->id] ?? 'present'; @endphp
          <tr class="{{ $alreadySaved ? '' : '' }}" style="{{ $alreadySaved ? 'opacity:.8' : '' }}">
            <td style="color:var(--muted);font-weight:600">{{ $loop->iteration }}</td>
            <td>
              <div class="stu-cell">
                @if($student->photo)
                  <img src="{{ storage_asset($student->photo) }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
                @else
                  <div class="stu-avatar" style="background:{{ $avatarColors[$loop->index % count($avatarColors)] }}">
                    {{ strtoupper(substr($student->name,0,2)) }}
                  </div>
                @endif
                <div>
                  <div class="stu-name">{{ $student->name }}</div>
                  <div class="stu-id">{{ $student->student_id }}</div>
                </div>
              </div>
            </td>
            <td>{{ $student->roll_number }}</td>
            @foreach(['present','absent','late','leave'] as $opt)
            <td class="center">
              <input type="radio"
                name="attendance[{{ $student->id }}]"
                value="{{ $opt }}"
                class="attendance-radio status-{{ $opt }}"
                {{ $status === $opt ? 'checked' : '' }}
                {{ $alreadySaved ? 'disabled' : '' }}
                style="width:17px;height:17px;accent-color:{{ match($opt) {
                  'present' => '#16a34a',
                  'absent'  => '#dc2626',
                  'late'    => '#d97706',
                  'leave'   => '#6366f1',
                } }};cursor:{{ $alreadySaved ? 'not-allowed' : 'pointer' }}">
            </td>
            @endforeach
            {{-- Monthly map trigger --}}
            <td class="center">
              <button type="button"
                class="btn-outline"
                style="padding:5px 10px;font-size:11px;border-radius:7px"
                onclick="openMap({{ $student->id }}, '{{ addslashes($student->name) }}')">
                <i class="fas fa-calendar-alt"></i>
              </button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <div style="font-size:12.5px;color:var(--muted)">
        <i class="fas fa-info-circle"></i>
        @if($alreadySaved) Attendance already submitted. Contact admin to make changes.
        @elseif($isAdmin) Saving will overwrite any existing attendance for this date.
        @else Submitting will send attendance to admin for {{ $date->format('d M Y') }}.
        @endif
      </div>
      @if($alreadySaved)
        <button type="button" disabled class="btn-primary" style="opacity:.5;cursor:not-allowed">
          <i class="fas fa-check-circle"></i> Already Submitted
        </button>
      @else
        <button type="submit" class="btn-primary">
          <i class="fas fa-save"></i> {{ $isAdmin ? 'Save Attendance' : 'Submit Attendance' }}
        </button>
      @endif
    </div>
  </div>
</form>
@endif

{{-- ── Monthly Attendance Map Modal ── --}}
<div id="mapBackdrop" class="map-backdrop">
  <div class="map-box">
    <div class="map-header">
      <span class="map-title" id="mapTitle">Monthly Attendance Map</span>
      <button class="map-close" onclick="closeMap()">&times;</button>
    </div>
    <div class="map-body">
      {{-- Legend --}}
      <div class="map-legend">
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#16a34a"></div> Present</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#dc2626"></div> Absent</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#f59e0b"></div> Leave</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#fde8e8;border:1px solid #c0192e"></div> Holiday</div>
      </div>

      {{-- Day names --}}
      <div class="map-grid" id="mapGrid">
        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
          <div class="map-day-name">{{ $d }}</div>
        @endforeach
        {{-- cells injected by JS --}}
      </div>

      {{-- Summary --}}
      <div class="map-summary">
        <div class="map-sum-item">
          <div class="map-sum-val" id="sumPresent" style="color:var(--green)">—</div>
          <div class="map-sum-lbl">Present Days</div>
        </div>
        <div class="map-sum-item">
          <div class="map-sum-val" id="sumAbsent" style="color:var(--red)">—</div>
          <div class="map-sum-lbl">Absent Days</div>
        </div>
        <div class="map-sum-item">
          <div class="map-sum-val" id="sumLeave" style="color:var(--orange)">—</div>
          <div class="map-sum-lbl">Leave Days</div>
        </div>
        <div class="map-sum-item">
          <div class="map-sum-val" id="sumRate" style="color:var(--crimson)">—</div>
          <div class="map-sum-lbl">Rate</div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
const attendanceAcademicStructure = @json($filterStructure);

(function initAttendanceAcademicCascade() {
  const sessionSelect = document.getElementById('attendance-session');
  const programSelect = document.getElementById('attendance-program');
  const classSelect   = document.getElementById('attendance-class');
  const subjectSelect = document.getElementById('attendance-subject');
  if (!sessionSelect || !programSelect || !classSelect || !subjectSelect) return;

  const initialProgram = programSelect.dataset.selected || '';
  const initialClass   = classSelect.dataset.selected || '';
  const initialSubject = subjectSelect.dataset.selected || '';

  function sessionNode() {
    return attendanceAcademicStructure[String(sessionSelect.value)] || null;
  }

  function programNode() {
    const session = sessionNode();
    return session ? (session.programs || []).find(p => String(p.id) === String(programSelect.value)) || null : null;
  }

  function termNode() {
    const program = programNode();
    return program ? (program.terms || []).find(t => String(t.id) === String(classSelect.value)) || null : null;
  }

  function populateSubjects(selected = '') {
    const term = termNode();
    subjectSelect.innerHTML = '';
    if (!term) {
      subjectSelect.innerHTML = `<option value="">${classSelect.value ? 'No subjects assigned' : 'Select Semester / Part first'}</option>`;
      subjectSelect.disabled = true;
      return;
    }
    subjectSelect.disabled = false;
    subjectSelect.innerHTML = '<option value="">General / Whole Class</option>';
    (term.subjects || []).forEach(subject => {
      const option = document.createElement('option');
      option.value = subject.id;
      option.textContent = subject.name;
      option.selected = String(subject.id) === String(selected);
      subjectSelect.appendChild(option);
    });
  }

  function populateClasses(selected = '', selectedSubject = '') {
    const program = programNode();
    classSelect.innerHTML = '';
    if (!program) {
      classSelect.innerHTML = `<option value="">${sessionSelect.value ? 'Select Program first' : 'Select Session first'}</option>`;
      classSelect.disabled = true;
      populateSubjects('');
      return;
    }
    classSelect.disabled = false;
    classSelect.innerHTML = '<option value="">Select Semester / Part</option>';
    (program.terms || []).forEach(term => {
      const option = document.createElement('option');
      option.value = term.id;
      option.textContent = term.name;
      option.selected = String(term.id) === String(selected);
      classSelect.appendChild(option);
    });
    if (!(program.terms || []).length) {
      classSelect.innerHTML = '<option value="">No linked Semester / Part</option>';
      classSelect.disabled = true;
    }
    populateSubjects(selectedSubject);
  }

  function populatePrograms(selected = '', selectedClass = '', selectedSubject = '') {
    const session = sessionNode();
    programSelect.innerHTML = '';
    if (!session) {
      programSelect.innerHTML = '<option value="">Select Session first</option>';
      programSelect.disabled = true;
      populateClasses('', '');
      return;
    }
    programSelect.disabled = false;
    programSelect.innerHTML = '<option value="">Select Program</option>';
    (session.programs || []).forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.selected = String(program.id) === String(selected);
      programSelect.appendChild(option);
    });
    if (!(session.programs || []).length) {
      programSelect.innerHTML = '<option value="">No Programs linked to this Session</option>';
      programSelect.disabled = true;
    }
    populateClasses(selectedClass, selectedSubject);
  }

  sessionSelect.addEventListener('change', () => populatePrograms('', '', ''));
  programSelect.addEventListener('change', () => populateClasses('', ''));
  classSelect.addEventListener('change', () => populateSubjects(''));
  populatePrograms(initialProgram, initialClass, initialSubject);
})();


// ── Attendance data for the current month (injected from PHP) ──
// Shape: { studentId: { "2026-02-01": "present", "2026-02-03": "absent", ... }, ... }
const monthlyData = @json($monthlyMap ?? []);

const today = new Date();

function markAll(status) {
  document.querySelectorAll('.status-' + status).forEach(r => r.checked = true);
}

function openMap(studentId, studentName) {
  document.getElementById('mapTitle').textContent = 'Monthly Attendance Map — ' + studentName;

  const records = monthlyData[studentId] || {};

  // Build calendar for current month
  const year  = today.getFullYear();
  const month = today.getMonth(); // 0-indexed
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const firstDow    = new Date(year, month, 1).getDay(); // 0=Sun

  let html = '';

  // Empty leading cells (day names already rendered as static HTML)
  for (let i = 0; i < firstDow; i++) {
    html += '<div class="map-cell empty"></div>';
  }

  let present = 0, absent = 0, leave = 0, late = 0;

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = year + '-' + String(month + 1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
    const dow     = new Date(year, month, d).getDay();
    const isFuture = new Date(year, month, d) > today;
    const isWeekend = dow === 0 || dow === 6;

    let cls  = 'future';
    let label = d;

    if (isFuture) {
      cls = 'future'; label = d;
    } else if (isWeekend) {
      cls = 'holiday'; label = 'H';
    } else if (records[dateStr]) {
      const s = records[dateStr];
      cls = s; // present|absent|late|leave
      label = s.charAt(0).toUpperCase();
      if (s === 'present') present++;
      else if (s === 'absent') absent++;
      else if (s === 'leave' || s === 'late') { leave++; late++; }
    } else {
      cls = 'future'; label = d;
    }

    html += `<div class="map-cell ${cls}" title="${dateStr}">${label}</div>`;
  }

  // Replace only the cell divs (preserve the 7 day-name divs)
  const grid = document.getElementById('mapGrid');
  // Remove old cells (keep first 7 day-name divs)
  while (grid.children.length > 7) grid.removeChild(grid.lastChild);
  grid.insertAdjacentHTML('beforeend', html);

  const total = present + absent + late;
  const rate  = total > 0 ? Math.round((present + late) / total * 100) : 0;

  document.getElementById('sumPresent').textContent = present;
  document.getElementById('sumAbsent').textContent  = absent;
  document.getElementById('sumLeave').textContent   = leave;
  document.getElementById('sumRate').textContent    = rate + '%';

  document.getElementById('mapBackdrop').classList.add('open');
}

function closeMap() {
  document.getElementById('mapBackdrop').classList.remove('open');
}

document.getElementById('mapBackdrop').addEventListener('click', function(e) {
  if (e.target === this) closeMap();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMap(); });
</script>
@endpush

@endsection
