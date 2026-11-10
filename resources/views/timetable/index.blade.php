@extends('layouts.app')
@section('title', 'Timetable')
@section('content')

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

  /* subject palette — pastel cards */
  --c-math:    #fde8e8; --ct-math:    #c0192e;
  --c-eng:     #e8f9ef; --ct-eng:     #0d9e6e;
  --c-phys:    #fff4e6; --ct-phys:    #d97706;
  --c-chem:    #fde8ee; --ct-chem:    #9d174d;
  --c-urdu:    #f0e8fd; --ct-urdu:    #6d28d9;
  --c-bio:     #e6f4fd; --ct-bio:     #0369a1;
  --c-hist:    #fef9e6; --ct-hist:    #92400e;
  --c-cs:      #e8eafd; --ct-cs:      #3730a3;
  --c-default: #f3f0ff; --ct-default: #5b21b6;
}

*, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
body { font-family:'Outfit', sans-serif; background:var(--bg); color:var(--text); }

/* ── Page Header ── */
.tt-header {
  display:flex; align-items:flex-end; justify-content:space-between;
  flex-wrap:wrap; gap:16px; margin-bottom:24px;
}
.tt-header-left h1 {
  font-family:'Outfit', sans-serif; font-size:22px; font-weight:800;
  color:#1a0608; letter-spacing:-.01em; line-height:1.2;
}
.tt-header-left p { font-size:13.5px; color:var(--muted); margin-top:5px; font-weight:400; }

.tt-controls { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
.tt-filter-form { display:flex; align-items:flex-end; gap:8px; flex-wrap:wrap; }
.tt-filter-field { display:flex; flex-direction:column; gap:4px; }
.tt-filter-label { font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); padding-left:2px; }

/* academic selector */
.class-select-wrap { position:relative; }
.class-select-wrap select {
  appearance:none; -webkit-appearance:none;
  padding:9px 36px 9px 14px;
  border:1.5px solid var(--border); border-radius:10px;
  background:var(--surface); color:var(--text);
  font-family:'Outfit', sans-serif; font-size:13.5px; font-weight:600;
  cursor:pointer; box-shadow:var(--shadow);
  transition:border-color .15s;
}
.class-select-wrap select:focus { outline:none; border-color:var(--crimson); }
.class-select-wrap select:disabled { cursor:not-allowed; opacity:.55; background:#f7f7f8; }
.class-select-wrap::after {
  content:''; position:absolute; right:12px; top:50%; transform:translateY(-50%);
  width:0; height:0;
  border-left:5px solid transparent; border-right:5px solid transparent;
  border-top:5px solid var(--muted); pointer-events:none;
}

/* Add Period button */
.btn-add {
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 20px; border-radius:10px; border:none;
  background:var(--crimson); color:#fff;
  font-family:'Outfit', sans-serif; font-size:13.5px; font-weight:700;
  cursor:pointer; box-shadow:0 4px 14px rgba(139,12,34,.30);
  transition:background .15s, transform .15s, box-shadow .15s;
  text-decoration:none;
}
.btn-add:hover { background:#a81030; transform:translateY(-1px); box-shadow:0 6px 20px rgba(139,12,34,.35); }

/* Print button */
.btn-print {
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 18px; border-radius:10px;
  border:1.5px solid var(--border); background:var(--surface); color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:13.5px; font-weight:700;
  cursor:pointer; text-decoration:none; box-shadow:var(--shadow);
  transition:border-color .15s, color .15s, transform .15s;
}
.btn-print:hover { border-color:var(--crimson); color:var(--crimson); transform:translateY(-1px); }

/* print options dropdown */
.tt-print-menu { position:relative; }
.tt-print-menu > summary { list-style:none; }
.tt-print-menu > summary::-webkit-details-marker { display:none; }
.tt-print-menu[open] > summary { border-color:var(--crimson); color:var(--crimson); }
.tt-print-chevron { font-size:9px; margin-left:1px; transition:transform .15s; }
.tt-print-menu[open] .tt-print-chevron { transform:rotate(180deg); }
.tt-print-menu-popover {
  position:absolute; right:0; top:calc(100% + 8px); z-index:40; width:310px;
  background:#fff; border:1.5px solid var(--border); border-radius:12px; padding:7px;
  box-shadow:0 12px 32px rgba(30,20,24,.14);
}
.tt-print-menu-popover a {
  display:flex; align-items:flex-start; gap:10px; padding:10px 11px; border-radius:9px;
  text-decoration:none; color:var(--text); transition:background .12s;
}
.tt-print-menu-popover a:hover { background:var(--crimson-soft); }
.tt-print-menu-popover a > i { width:18px; margin-top:2px; color:var(--crimson); text-align:center; }
.tt-print-menu-popover a span { display:flex; flex-direction:column; gap:2px; line-height:1.25; }
.tt-print-menu-popover a strong { font-size:12.5px; }
.tt-print-menu-popover a small { font-size:10.5px; color:var(--muted); font-weight:500; }

/* ── Alert / Conflict Banner ── */
.conflict-alert {
  display:flex; align-items:flex-start; gap:12px;
  background:#fff0f1; border:1.5px solid rgba(139,12,34,.25);
  border-radius:12px; padding:14px 18px; margin-bottom:20px;
  animation:slideIn .3s ease;
}
@keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.conflict-alert i { color:var(--crimson); font-size:16px; padding-top:1px; flex-shrink:0; }
.conflict-alert-body { flex:1; }
.conflict-alert-title { font-weight:700; font-size:13.5px; color:var(--crimson); }
.conflict-alert-msg   { font-size:13px; color:#6b1120; margin-top:2px; }

/* ── Timetable Card ── */
.tt-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden;
  box-shadow:var(--shadow); animation:fadeUp .4s ease;
}
@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* ── Grid Table ── */
.tt-table { width:100%; border-collapse:collapse; }
.tt-table th {
  font-family:'Outfit', sans-serif; font-size:11px; font-weight:700;
  letter-spacing:.10em; text-transform:uppercase; color:var(--muted);
  padding:14px 10px; background:#fdf6f7; border-bottom:1.5px solid var(--border);
  text-align:center;
}
.tt-table th:first-child { text-align:left; padding-left:20px; width:130px; }

.tt-table td {
  border:1px solid var(--border); padding:8px; vertical-align:middle;
  text-align:center; min-width:150px;
}
.tt-table td:first-child {
  text-align:center; padding:10px 20px;
  background:#fdf6f7; font-size:13px; font-weight:600; color:var(--muted);
  white-space:nowrap; border-left:none;
}
.tt-table tr:last-child td { border-bottom:none; }

@media (max-width:768px) {
  .tt-scroll-hint { display:block !important; }
}

/* ── Period Cell ── */
.period-cell {
  border-radius:9px; padding:8px 10px; cursor:pointer;
  transition:transform .15s, box-shadow .15s; position:relative;
  border:1.5px solid transparent;
}
.period-cell:hover { transform:scale(1.02); box-shadow:0 4px 14px rgba(0,0,0,.10); z-index:2; }
.period-cell .pc-subject { font-family:'Outfit', sans-serif; font-size:12.5px; font-weight:700; line-height:1.25; }
.period-cell .pc-teacher { font-size:11px; margin-top:2px; font-weight:500; }
.period-cell .pc-days {
  display:inline-flex; align-items:center; gap:4px;
  font-size:9.5px; font-weight:700; letter-spacing:.02em;
  margin-top:5px; padding:2px 7px; border-radius:20px;
  background:rgba(255,255,255,.55); color:inherit; opacity:.85;
  width:fit-content;
}
.period-cell .pc-days i { font-size:8.5px; }
.period-cell .pc-actions {
  display:none; position:absolute; top:5px; right:5px;
  display:flex; gap:3px; opacity:0; transition:opacity .15s;
}
.period-cell:hover .pc-actions { opacity:1; }
.pc-action-btn {
  width:22px; height:22px; border-radius:6px; border:none;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; cursor:pointer; transition:background .12s;
}
.pc-action-edit { background:rgba(255,255,255,.8); color:#555; }
.pc-action-edit:hover { background:#fff; color:var(--crimson); }
.pc-action-del { background:rgba(255,255,255,.8); color:#555; }
.pc-action-del:hover { background:#fff; color:var(--crimson); }

/* subject colors — legacy palette kept only as vars in case other views
   reference them; the grid itself now colors by teacher (see teacherColor()
   in the view), which scales to any subject naming scheme, not just a
   fixed keyword list. */

/* ── Multiple lectures in the same day/slot ── */
.cell-stack { display:flex; flex-direction:column; gap:6px; }
.add-more-cell {
  height:22px; border-radius:7px; border:1.3px dashed rgba(139,12,34,.18);
  display:flex; align-items:center; justify-content:center; gap:5px;
  font-size:10px; font-weight:600; color:rgba(139,12,34,.35); cursor:pointer;
  transition:background .15s, color .15s, border-color .15s; opacity:0;
}
.cell-stack:hover .add-more-cell { opacity:1; }
.add-more-cell:hover { background:var(--crimson-soft); border-color:rgba(139,12,34,.35); color:var(--crimson); }

/* ── Teacher color legend ── */
.tt-legend {
  display:flex; flex-wrap:wrap; gap:8px 14px;
  padding:14px 20px; border-bottom:1.5px solid var(--border); background:#fdfbfb;
}
.tt-legend-chip {
  display:inline-flex; align-items:center; gap:6px;
  font-size:12px; font-weight:600; color:var(--text);
}
.tt-legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

/* empty cell */
.empty-cell {
  background:#fafafa; border-radius:8px;
  height:56px; border:1.5px dashed rgba(139,12,34,.12);
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:background .15s, border-color .15s;
}
.empty-cell:hover { background:var(--crimson-soft); border-color:rgba(139,12,34,.30); }
.empty-cell i { font-size:14px; color:rgba(139,12,34,.25); transition:color .15s; }
.empty-cell:hover i { color:rgba(139,12,34,.55); }

/* break / special row */
.special-row td { background:#fdf6f7; }
.special-cell {
  display:flex; align-items:center; justify-content:center; gap:10px;
  padding:12px; font-size:13px; font-weight:600; color:var(--muted);
  background:linear-gradient(90deg,#fdf0f2 0%,#f9fafb 100%);
  border-radius:10px;
}
.break-edit-btn {
  width:24px; height:24px; border-radius:7px; border:none;
  background:rgba(139,12,34,.10); color:var(--crimson);
  display:flex; align-items:center; justify-content:center;
  font-size:10.5px; cursor:pointer; transition:background .12s;
  flex-shrink:0;
}
.break-edit-btn:hover { background:var(--crimson); color:#fff; }

/* ── Teacher color picker rows ── */
.color-row {
  display:flex; align-items:center; gap:12px;
  padding:10px 4px; border-bottom:1px solid var(--border);
}
.color-row:last-child { border-bottom:none; }
.color-row-name { flex:1; font-size:13.5px; font-weight:600; color:var(--text); }
.color-row-input {
  width:38px; height:32px; padding:0; border:1.5px solid var(--border);
  border-radius:8px; cursor:pointer; background:none;
}
.color-row-input::-webkit-color-swatch-wrapper { padding:2px; }
.color-row-input::-webkit-color-swatch { border:none; border-radius:5px; }
.color-row-reset {
  width:30px; height:30px; border-radius:8px; border:none;
  background:var(--crimson-soft); color:var(--muted);
  display:flex; align-items:center; justify-content:center;
  font-size:11px; cursor:pointer; transition:background .12s, color .12s;
  flex-shrink:0;
}
.color-row-reset:hover { background:var(--crimson); color:#fff; }
.color-modal-hint { font-size:12.5px; color:var(--muted); margin-bottom:14px; }
.color-modal-list { max-height:360px; overflow-y:auto; }

/* ── Modals ── */
.modal-backdrop {
  display:none; position:fixed; inset:0;
  background:rgba(15,5,7,.45); backdrop-filter:blur(3px);
  z-index:1000; align-items:center; justify-content:center;
}
.modal-backdrop.open { display:flex; }
.modal-box {
  background:var(--surface); border-radius:16px;
  width:100%; max-width:500px; margin:20px;
  box-shadow:0 24px 60px rgba(139,12,34,.20);
  animation:modalIn .25s cubic-bezier(.34,1.56,.64,1);
  overflow:hidden;
}
@keyframes modalIn { from{opacity:0;transform:scale(.94) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
.modal-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:20px 24px 16px;
  border-bottom:1px solid var(--border);
}
.modal-title { font-family:'Outfit', sans-serif; font-size:17px; font-weight:800; }
.modal-close {
  width:32px; height:32px; border-radius:8px; border:none;
  background:var(--crimson-soft); color:var(--muted);
  display:flex; align-items:center; justify-content:center;
  font-size:16px; cursor:pointer; transition:background .12s, color .12s;
}
.modal-close:hover { background:var(--crimson); color:#fff; }
.modal-body { padding:20px 24px; }
.modal-footer {
  padding:16px 24px 20px; border-top:1px solid var(--border);
  display:flex; justify-content:flex-end; gap:10px;
}

/* Form elements */
.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
.form-control {
  width:100%; padding:10px 14px; border-radius:9px;
  border:1.5px solid var(--border);
  background:var(--surface); color:var(--text);
  font-family:'Outfit', sans-serif; font-size:13.5px;
  transition:border-color .15s, box-shadow .15s;
}
.form-control:focus { outline:none; border-color:var(--crimson); box-shadow:0 0 0 3px rgba(139,12,34,.10); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

.btn-outline {
  padding:9px 18px; border-radius:9px; border:1.5px solid var(--border);
  background:transparent; color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:13.5px; font-weight:600;
  cursor:pointer; transition:border-color .15s, color .15s;
}
.btn-outline:hover { border-color:var(--crimson); color:var(--crimson); }
.btn-primary {
  padding:9px 20px; border-radius:9px; border:none;
  background:var(--crimson); color:#fff;
  font-family:'Outfit', sans-serif; font-size:13.5px; font-weight:700;
  cursor:pointer; transition:background .15s;
  display:inline-flex; align-items:center; gap:7px;
}
.btn-primary:hover { background:#a81030; }

/* ── Day picker (multi-select chips) ── */
.day-picker { display:flex; flex-wrap:wrap; gap:8px; }
.day-chip { position:relative; }
.day-chip input { position:absolute; opacity:0; width:0; height:0; }
.day-chip span {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:46px; padding:8px 10px; border-radius:9px;
  border:1.5px solid var(--border); background:var(--surface); color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:12.5px; font-weight:700;
  cursor:pointer; user-select:none; transition:all .15s;
}
.day-chip input:checked + span {
  background:var(--crimson); border-color:var(--crimson); color:#fff;
  box-shadow:0 3px 10px rgba(139,12,34,.25);
}
.day-chip span:hover { border-color:var(--crimson); }
.day-quick-select { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.day-quick-btn {
  padding:5px 12px; border-radius:20px; border:1.5px dashed var(--border);
  background:transparent; color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:11.5px; font-weight:600;
  cursor:pointer; transition:all .15s;
}
.day-quick-btn:hover { border-color:var(--crimson); color:var(--crimson); border-style:solid; }
.day-picker-hint { font-size:11.5px; color:var(--muted); margin-top:8px; }

/* Conflict hint inside modal */
.conflict-hint {
  display:none; align-items:flex-start; gap:8px;
  background:#fff0f1; border:1px solid rgba(139,12,34,.25);
  border-radius:9px; padding:10px 14px; margin-top:10px;
  font-size:12.5px; color:#7a0c1a;
}
.conflict-hint i { color:var(--crimson); margin-top:1px; flex-shrink:0; }
.conflict-hint.visible { display:flex; }

/* Flash success */
.flash-success {
  display:flex; align-items:center; gap:10px;
  background:#e8f9ef; border:1px solid rgba(13,158,110,.25);
  border-radius:10px; padding:12px 18px; margin-bottom:18px;
  font-size:13.5px; font-weight:500; color:#065f46;
}
.flash-success i { color:#0d9e6e; }

/* No entries empty state */
.tt-empty {
  padding:60px 20px; text-align:center;
}
.tt-empty i { font-size:48px; color:rgba(139,12,34,.15); display:block; margin-bottom:14px; }
.tt-empty h3 { font-family:'Outfit', sans-serif; font-size:18px; font-weight:700; color:var(--muted); }
.tt-empty p { font-size:13px; color:var(--muted); margin-top:6px; }
</style>

{{-- Flash Messages --}}
@if(session('success'))
<div class="flash-success">
  <i class="fas fa-check-circle"></i>
  {{ session('success') }}
</div>
@endif

@if($errors->has('conflict'))
<div class="conflict-alert">
  <i class="fas fa-exclamation-triangle"></i>
  <div class="conflict-alert-body">
    <div class="conflict-alert-title">Schedule Conflict Detected</div>
    <div class="conflict-alert-msg">{{ $errors->first('conflict') }}</div>
  </div>
</div>
@endif

{{-- Page Header --}}
<div class="tt-header">
  <div class="tt-header-left">
    <h1>Timetable</h1>
    <p>Class schedules and period management</p>
  </div>
  <div class="tt-controls">
    <form method="GET" id="tt-filter-form" class="tt-filter-form" data-ajax-filter>
      <div class="tt-filter-field">
        <span class="tt-filter-label">Session</span>
        <div class="class-select-wrap">
          <select name="academic_year_id" id="tt-filter-session">
            <option value="">Select Session</option>
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>
                {{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="tt-filter-field">
        <span class="tt-filter-label">Program</span>
        <div class="class-select-wrap">
          <select name="program_id" id="tt-filter-program" data-selected="{{ $selectedProgramId }}">
            <option value="">Select Session first</option>
          </select>
        </div>
      </div>
      <div class="tt-filter-field">
        <span class="tt-filter-label">Semester / Part</span>
        <div class="class-select-wrap">
          <select name="class_id" id="tt-filter-class" data-selected="{{ $selectedClassId }}">
            <option value="">Select Program first</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn-print"><i class="fas fa-filter"></i> Load</button>
      <a class="btn-print" href="{{ route('admin.timetable.index') }}" data-ajax-filter-link><i class="fas fa-rotate-left"></i> Reset</a>
    </form>
    @if($selectedYearId)
      <details class="tt-print-menu">
        <summary class="btn-print"><i class="fas fa-print"></i> Print Options <i class="fas fa-chevron-down tt-print-chevron"></i></summary>
        <div class="tt-print-menu-popover">
          @if($selectedProgramId)
            <a href="{{ route('admin.timetable.print', [
                'mode' => 'selected',
                'academic_year_id' => $selectedYearId,
                'program_id' => $selectedProgramId,
                'class_id' => $selectedClassId,
                'day' => request('day')
            ]) }}" target="_blank">
              <i class="fas fa-file-lines"></i>
              <span><strong>Selected Program / Part</strong><small>Print the timetable currently selected above</small></span>
            </a>
          @endif
          <a href="{{ route('admin.timetable.print', [
              'mode' => 'merged',
              'academic_year_id' => $selectedYearId,
              'day' => request('day')
          ]) }}" target="_blank">
            <i class="fas fa-table-cells-large"></i>
            <span><strong>All Classes — Merged</strong><small>One compact landscape timetable for the whole session</small></span>
          </a>
        </div>
      </details>
    @endif
    <button class="btn-print" onclick="openModal('colorModal')" type="button">
      <i class="fas fa-palette"></i> Teacher Colors
    </button>
    <button class="btn-add" onclick="openAddPeriodModal()" type="button">
      <i class="fas fa-plus"></i> Add Period
    </button>
  </div>
</div>

{{-- ── Timetable Grid ── --}}
@php
  $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
  if ($items->getCollection()->contains('day', 'Saturday')) $days[] = 'Saturday';

  // build matrix: [timeSlot][day] = [item, item, ...] — a slot/day cell can
  // now hold more than one lecture (e.g. two sections sharing a room, or a
  // combined/elective period), so each cell collects an array instead of a
  // single item.
  // $slotTimes[$slot] keeps the *actual* 24-hour, zero-padded start/end
  // (e.g. "08:00") separately from the human-readable $slot label
  // (e.g. "8:00–9:00") — the label isn't safe to feed back into an
  // <input type="time">, which requires strict zero-padded HH:MM.
  $matrix    = [];
  $slotTimes = [];
  foreach($items as $item) {
    $startC = \Carbon\Carbon::parse($item->start_time);
    $endC   = \Carbon\Carbon::parse($item->end_time);
    $slot = $startC->format('g:i') . '–' . $endC->format('g:i');
    $matrix[$slot][$item->day][] = $item;
    $slotTimes[$slot] = ['start' => $startC->format('H:i'), 'end' => $endC->format('H:i')];
  }

  // The query is day-first, so a time that first appears on Wednesday used to
  // be inserted after all Monday-only times. Sort the completed row matrix by
  // its real 24-hour start/end values so 4:30–5:15 can never appear below
  // 5:15–6:00 simply because the 4:30 row was first encountered on another day.
  uksort($matrix, function ($a, $b) use ($slotTimes) {
    $startCmp = strcmp($slotTimes[$a]['start'], $slotTimes[$b]['start']);
    return $startCmp !== 0 ? $startCmp : strcmp($slotTimes[$a]['end'], $slotTimes[$b]['end']);
  });

  // Work out where the editable break belongs in the chronological rows.
  // The setting accepts 24-hour time as well as AM/PM. For an old ambiguous
  // value such as "4:30-5:15", choose the interpretation closest to the
  // timetable's actual period times (normally 4:30 PM rather than 4:30 AM).
  $clockMinutes = function (?string $value): ?int {
    if (!$value || !preg_match('/(\d{1,2})\s*:\s*(\d{2})\s*(am|pm)?/i', trim($value), $m)) return null;
    $h = (int) $m[1]; $min = (int) $m[2]; $ampm = strtolower($m[3] ?? '');
    if ($min > 59 || $h > 23) return null;
    if ($ampm) {
      if ($h < 1 || $h > 12) return null;
      $h = $h % 12 + ($ampm === 'pm' ? 12 : 0);
    }
    return $h * 60 + $min;
  };
  $breakParts = preg_split('/\s*[-–—]\s*/u', (string) $breakTime, 2);
  $breakSortMinute = $clockMinutes($breakParts[0] ?? null);
  $breakHasAmPm = (bool) preg_match('/\b(?:am|pm)\b/i', $breakParts[0] ?? '');
  $scheduleStartMinutes = collect($slotTimes)->map(fn($t) => $clockMinutes($t['start']))->filter(fn($v) => $v !== null)->values();
  if ($breakSortMinute !== null && !$breakHasAmPm && $breakSortMinute < 12 * 60 && $scheduleStartMinutes->isNotEmpty()) {
    $pmCandidate = $breakSortMinute + 12 * 60;
    $distance = fn($candidate) => $scheduleStartMinutes->map(fn($m) => abs($m - $candidate))->min();
    if ($pmCandidate < 24 * 60 && $distance($pmCandidate) < $distance($breakSortMinute)) $breakSortMinute = $pmCandidate;
  }

  // Colour per teacher — admins can manually assign a color per teacher
  // (via the "Teacher Colors" modal, stored on teachers.timetable_color);
  // anyone left unset falls back to a deterministic golden-angle hue so no
  // two teachers who are actually different people end up looking the
  // same by accident.
  function teacherColor(?int $teacherId, ?string $manualColor = null): array {
    if (!$teacherId) {
      return ['bg' => '#f1f1f3', 'text' => '#5b5b63', 'border' => 'rgba(60,60,70,.15)'];
    }

    if ($manualColor && preg_match('/^#[0-9A-Fa-f]{6}$/', $manualColor)) {
      $r = hexdec(substr($manualColor, 1, 2));
      $g = hexdec(substr($manualColor, 3, 2));
      $b = hexdec(substr($manualColor, 5, 2));
      return [
        'bg'     => "rgba({$r},{$g},{$b},.14)",
        'text'   => $manualColor,
        'border' => "rgba({$r},{$g},{$b},.32)",
      ];
    }

    $hue = fmod($teacherId * 137.508, 360);
    return [
      'bg'     => "hsl({$hue}, 68%, 93%)",
      'text'   => "hsl({$hue}, 45%, 28%)",
      'border' => "hsla({$hue}, 55%, 40%, .22)",
    ];
  }

  // Legend: every distinct teacher appearing in the grid right now, so the
  // colour-coding above is actually readable at a glance.
  $legendTeachers = $items->pluck('teacher')->filter()->unique('id')->sortBy('name')->values();

  // ── Group same lecture across the days it repeats on ──
  // A single "Add Period" submission with multiple days ticked creates one
  // DB row per day, so to show "this lecture also runs on Tue/Wed" on each
  // cell we regroup by the combo that makes it the *same* lecture: class +
  // subject + teacher + exact time slot. Two unrelated lectures that just
  // happen to share a time slot (different subject/teacher) are never
  // merged together.
  $dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  $dayShort = ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed','Thursday'=>'Thu','Friday'=>'Fri','Saturday'=>'Sat'];

  $seriesKey = fn($item) => implode('|', [$item->school_class_id, $item->subject_id, $item->teacher_id, $item->start_time, $item->end_time]);

  $seriesDays = [];
  foreach ($items as $item) {
    $seriesDays[$seriesKey($item)][] = $item->day;
  }

  // Collapses a lecture's day list into a compact label, merging any
  // consecutive run of days into a range: ['Monday','Tuesday'] -> "Mon–Tue",
  // ['Wednesday'..'Saturday'] -> "Wed–Sat", non-consecutive days stay comma
  // separated, e.g. ['Monday','Wednesday'] -> "Mon, Wed".
  function formatDayRange(array $days, array $dayOrder, array $dayShort): string {
    $sorted = collect($days)->unique()->sortBy(fn($d) => array_search($d, $dayOrder))->values();
    if ($sorted->count() <= 1) return $dayShort[$sorted->first()] ?? '';

    $runs = [];
    $runStart = $sorted[0];
    $prevIdx  = array_search($sorted[0], $dayOrder);
    for ($i = 1; $i < $sorted->count(); $i++) {
      $idx = array_search($sorted[$i], $dayOrder);
      if ($idx === $prevIdx + 1) { $prevIdx = $idx; continue; }
      $runs[] = [$runStart, $dayOrder[$prevIdx]];
      $runStart = $sorted[$i];
      $prevIdx  = $idx;
    }
    $runs[] = [$runStart, $dayOrder[$prevIdx]];

    return collect($runs)
      ->map(fn($r) => $r[0] === $r[1] ? $dayShort[$r[0]] : $dayShort[$r[0]] . '–' . $dayShort[$r[1]])
      ->implode(', ');
  }
@endphp

<div class="tt-card">
  @if($items->count() > 0)
  @if($legendTeachers->isNotEmpty())
  <div class="tt-legend">
    @foreach($legendTeachers as $t)
      @php $lc = teacherColor($t->id, $t->timetable_color); @endphp
      <span class="tt-legend-chip">
        <span class="tt-legend-dot" style="background:{{ $lc['text'] }}"></span>{{ $t->name }}
      </span>
    @endforeach
  </div>
  @endif
  <div class="tt-scroll-hint" style="display:none;font-size:11.5px;color:var(--text-muted);padding:8px 12px 0;">
    <i class="fas fa-arrows-left-right"></i> Swipe to see more days
  </div>
  <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
    <table class="tt-table">
      <thead>
        <tr>
          <th>PERIOD / DAY</th>
          @foreach($days as $day)
          <th>{{ strtoupper($day) }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @php $breakInserted = false; @endphp
        @foreach($matrix as $slot => $dayMap)
          @php $slotStartMinute = $clockMinutes($slotTimes[$slot]['start']); @endphp

          @if(!$breakInserted && $breakSortMinute !== null && $slotStartMinute !== null && $breakSortMinute <= $slotStartMinute)
            <tr class="special-row">
              <td>{{ $breakTime }}</td>
              <td colspan="{{ count($days) }}">
                <div class="special-cell">
                  <span>{{ $breakLabel }}</span>
                  <button type="button" class="break-edit-btn" onclick="openModal('breakModal')" title="Edit break period">
                    <i class="fas fa-pen"></i>
                  </button>
                </div>
              </td>
            </tr>
            @php $breakInserted = true; @endphp
          @endif

          <tr>
            <td>{{ $slot }}</td>
            @foreach($days as $day)
            @php $dayItems = $dayMap[$day] ?? []; @endphp
            <td>
              @if(count($dayItems) === 0)
                <div class="empty-cell"
                  onclick="prefillAdd('{{ $day }}','{{ $slotTimes[$slot]['start'] }}','{{ $slotTimes[$slot]['end'] }}')">
                  <i class="fas fa-plus"></i>
                </div>
              @else
                <div class="cell-stack">
                  @foreach($dayItems as $item)
                  @php $tc = teacherColor($item->teacher_id, $item->teacher->timetable_color ?? null); @endphp
                  <div class="period-cell" style="background:{{ $tc['bg'] }}; color:{{ $tc['text'] }}; border-color:{{ $tc['border'] }}">
                    <div class="pc-actions">
                      <button class="pc-action-btn pc-action-edit"
                        onclick="openEditModal({{ $item->id }},'{{ $item->teacher_id }}','{{ $item->start_time }}','{{ $item->end_time }}');event.stopPropagation()"
                        title="Edit"><i class="fas fa-pen"></i></button>
                      <form action="{{ route('admin.timetable.destroy',$item) }}" method="POST" style="display:contents"
                        onsubmit="return confirm('Remove this period?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="pc-action-btn pc-action-del" title="Delete" onclick="event.stopPropagation()">
                          <i class="fas fa-trash"></i></button>
                      </form>
                    </div>
                    <div class="pc-subject">{{ $item->subject->name ?? '—' }}</div>
                    <div class="pc-teacher">{{ $item->teacher->name ?? '—' }}</div>
                    @php $itemDays = $seriesDays[$seriesKey($item)] ?? [$item->day]; @endphp
                    @if(count($itemDays) > 1)
                      <div class="pc-days" title="Held on {{ collect($itemDays)->sortBy(fn($d) => array_search($d, $dayOrder))->implode(', ') }}">
                        <i class="fas fa-calendar-day"></i> {{ formatDayRange($itemDays, $dayOrder, $dayShort) }}
                      </div>
                    @endif
                  </div>
                  @endforeach
                  <div class="add-more-cell"
                    onclick="prefillAdd('{{ $day }}','{{ $slotTimes[$slot]['start'] }}','{{ $slotTimes[$slot]['end'] }}')"
                    title="Add another lecture in this slot">
                    <i class="fas fa-plus"></i> Add another
                  </div>
                </div>
              @endif
            </td>
            @endforeach
          </tr>
        @endforeach

        @if(!$breakInserted)
          <tr class="special-row">
            <td>{{ $breakTime }}</td>
            <td colspan="{{ count($days) }}">
              <div class="special-cell">
                <span>{{ $breakLabel }}</span>
                <button type="button" class="break-edit-btn" onclick="openModal('breakModal')" title="Edit break period">
                  <i class="fas fa-pen"></i>
                </button>
              </div>
            </td>
          </tr>
        @endif
      </tbody>
    </table>
  </div>
  @else
  <div class="tt-empty">
    <i class="fas fa-calendar-alt"></i>
    @if(!$selectedClassId)
      <h3>Select a Semester / Part</h3>
      <p>Choose <strong>Session → Program → Semester / Part</strong> above. The timetable loads automatically for that exact academic path.</p>
    @else
      <h3>No Timetable Entries</h3>
      <p>No periods are scheduled for this selected Session / Program / Semester yet. Click "Add Period" to create one.</p>
    @endif
  </div>
  @endif
</div>

{{-- ── Add Modal ── --}}
<div id="addModal" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Add Period</span>
      <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
    </div>
    <form action="{{ route('admin.timetable.store') }}" method="POST" id="addForm">
      @csrf
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Session *</label>
            <select name="academic_year_id" id="add_session" class="form-control" required onchange="ttPopulatePrograms('add')">
              <option value="">Select Session</option>
              @foreach($academicYears as $year)
                <option value="{{ $year->id }}">{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Program *</label>
            <select name="program_id" id="add_program" class="form-control" required onchange="ttPopulateClasses('add')" disabled>
              <option value="">Select Session first</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Semester / Part *</label>
            <select name="school_class_id" id="add_class" class="form-control" required onchange="ttPopulateSubjects('add')" disabled>
              <option value="">Select Program first</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Subject *</label>
            <select name="subject_id" id="add_subject" class="form-control" required onchange="ttPrefillTeacher('add')" disabled>
              <option value="">Select Semester / Part first</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Teacher</label>
          <select name="teacher_id" id="add_teacher" class="form-control" onchange="checkConflict(this)">
            <option value="">Select Teacher</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
            @endforeach
          </select>
          <div style="font-size:11.5px;color:var(--muted);margin-top:5px">The curriculum teacher is preselected when available; you can still change it.</div>
        </div>
        <div class="form-row">
          <div class="form-group" style="grid-column:1 / -1">
            <label class="form-label">Day(s) *</label>
            <div class="day-picker" id="add_day_picker">
              @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                <label class="day-chip">
                  <input type="checkbox" name="day[]" value="{{ $day }}" class="add-day-checkbox" onchange="checkConflict()">
                  <span>{{ substr($day, 0, 3) }}</span>
                </label>
              @endforeach
            </div>
            <div class="day-quick-select">
              <button type="button" class="day-quick-btn" onclick="selectDayRange('Monday','Tuesday')">Mon–Tue</button>
              <button type="button" class="day-quick-btn" onclick="selectDayRange('Monday','Friday')">Mon–Fri</button>
              <button type="button" class="day-quick-btn" onclick="selectDayRange('Wednesday','Saturday')">Wed–Sat</button>
              <button type="button" class="day-quick-btn" onclick="selectDayRange('Monday','Saturday')">Mon–Sat</button>
              <button type="button" class="day-quick-btn" onclick="clearDaySelection()">Clear</button>
            </div>
            <p class="day-picker-hint">Tick every day this same subject/teacher/time slot repeats on — e.g. Mon &amp; Tue for one lecture, Wed–Sat for another.</p>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Time *</label>
            <input type="time" name="start_time" id="add_start" class="form-control" required onchange="checkConflict(this)">
          </div>
          <div class="form-group">
            <label class="form-label">End Time *</label>
            <input type="time" name="end_time" id="add_end" class="form-control" required onchange="checkConflict(this)">
          </div>
        </div>
        <div id="add_conflict_hint" class="conflict-hint">
          <i class="fas fa-exclamation-triangle"></i>
          <span id="add_conflict_msg">This teacher already has a class at this time.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Period</button>
      </div>
    </form>
  </div>
</div>

{{-- ── Edit Modal ── --}}
<div id="editModal" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Edit Period</span>
      <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <form id="editForm" method="POST">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Teacher</label>
          <select name="teacher_id" id="edit_teacher_id" class="form-control" onchange="checkEditConflict(this)">
            <option value="">Select Teacher</option>
            @foreach($teachers as $teacher)
              <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" id="edit_start_time" class="form-control" onchange="checkEditConflict(this)">
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" id="edit_end_time" class="form-control" onchange="checkEditConflict(this)">
          </div>
        </div>
        <div id="edit_conflict_hint" class="conflict-hint">
          <i class="fas fa-exclamation-triangle"></i>
          <span id="edit_conflict_msg">This teacher already has a class at this time.</span>
        </div>
      </div>
      <input type="hidden" id="edit_item_id" name="_item_id">
      <div class="modal-footer">
        <button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

{{-- ── Teacher Colors Modal ── --}}
<div id="colorModal" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Assign Teacher Colors</span>
      <button class="modal-close" onclick="closeModal('colorModal')">&times;</button>
    </div>
    <div class="modal-body">
      <p class="color-modal-hint">Pick a color for each teacher's periods on the grid. Leave a teacher unset and they'll keep their auto-assigned color.</p>
      <div class="color-modal-list">
        @foreach($teachers as $teacher)
          <div class="color-row">
            <span class="color-row-name">{{ $teacher->name }}</span>
            <form action="{{ route('admin.timetable.teacher-color.update', $teacher) }}" method="POST" style="display:contents">
              @csrf @method('PUT')
              <input type="color" name="timetable_color" class="color-row-input"
                     value="{{ $teacher->timetable_color ?? '#8b0c22' }}"
                     onchange="this.form.submit()" title="Pick a color for {{ $teacher->name }}">
            </form>
            @if($teacher->timetable_color)
              <form action="{{ route('admin.timetable.teacher-color.update', $teacher) }}" method="POST" style="display:contents">
                @csrf @method('PUT')
                <input type="hidden" name="timetable_color" value="">
                <button type="submit" class="color-row-reset" title="Reset {{ $teacher->name }} to auto color">
                  <i class="fas fa-rotate-left"></i>
                </button>
              </form>
            @endif
          </div>
        @endforeach
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-outline" onclick="closeModal('colorModal')">Done</button>
    </div>
  </div>
</div>

{{-- ── Edit Break Modal ── --}}
<div id="breakModal" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Edit Break Period</span>
      <button class="modal-close" onclick="closeModal('breakModal')">&times;</button>
    </div>
    <form action="{{ route('admin.timetable.break.update') }}" method="POST">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Label</label>
          <input type="text" name="break_label" class="form-control" value="{{ $breakLabel }}" required
                 placeholder="e.g. 🕌 Juma Prayer (Friday) / Lunch Break">
        </div>
        <div class="form-group">
          <label class="form-label">Break Time</label>
          <input type="text" name="break_time" class="form-control" value="{{ $breakTime }}" required placeholder="e.g. 16:30-17:15 or 4:30 PM-5:15 PM">
          <small style="display:block;margin-top:5px;color:var(--muted);font-size:11px">Use 24-hour time or include AM/PM so the break can be positioned exactly in the timetable sequence.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-outline" onclick="closeModal('breakModal')">Cancel</button>
        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
// ── Conflict data injected from PHP ──
const timetableData = @json($allEntries ?? $items->toArray());

// ── Session -> Program -> Semester/Part -> Subject cascade data ──
const ttAcademicStructure = @json($filterStructure ?? []);

function ttSessionNode(sessionId) {
  return ttAcademicStructure[String(sessionId)] || null;
}
function ttProgramNode(sessionId, programId) {
  const session = ttSessionNode(sessionId);
  return session ? (session.programs || []).find(p => String(p.id) === String(programId)) : null;
}
function ttTermNode(sessionId, programId, classId) {
  const program = ttProgramNode(sessionId, programId);
  return program ? (program.terms || []).find(t => String(t.id) === String(classId)) : null;
}

// Main page filter: downstream lists stay disabled until their parent has a
// valid selection, and previously selected values are restored after reload.
(function initTimetableFilterCascade() {
  const sessionSelect = document.getElementById('tt-filter-session');
  const programSelect = document.getElementById('tt-filter-program');
  const classSelect   = document.getElementById('tt-filter-class');
  if (!sessionSelect || !programSelect || !classSelect) return;

  const initialProgram = String(programSelect.dataset.selected || '');
  const initialClass   = String(classSelect.dataset.selected || '');

  function fillPrograms(keep = '') {
    const programs = ttSessionNode(sessionSelect.value)?.programs || [];
    programSelect.innerHTML = !sessionSelect.value
      ? '<option value="">Select Session first</option>'
      : programs.length
        ? '<option value="">Select Program</option>' + programs.map(p => `<option value="${p.id}">${p.name}</option>`).join('')
        : '<option value="">No programs linked to this session</option>';
    programSelect.disabled = !sessionSelect.value || programs.length === 0;
    if (keep && programs.some(p => String(p.id) === String(keep))) programSelect.value = keep;
    fillClasses(keep ? initialClass : '');
  }

  function fillClasses(keep = '') {
    const terms = ttProgramNode(sessionSelect.value, programSelect.value)?.terms || [];
    classSelect.innerHTML = !programSelect.value
      ? '<option value="">Select Program first</option>'
      : terms.length
        ? '<option value="">Select Semester / Part</option>' + terms.map(t => `<option value="${t.id}">${t.name}</option>`).join('')
        : '<option value="">No semesters linked to this program/session</option>';
    classSelect.disabled = !programSelect.value || terms.length === 0;
    if (keep && terms.some(t => String(t.id) === String(keep))) classSelect.value = keep;
  }

  sessionSelect.addEventListener('change', () => fillPrograms());
  programSelect.addEventListener('change', () => fillClasses());
  fillPrograms(initialProgram);
})();

function ttPopulatePrograms(prefix, keepProgram = '', keepClass = '', keepSubject = '') {
  const sessionSelect = document.getElementById(prefix + '_session');
  const programSelect = document.getElementById(prefix + '_program');
  const programs = ttSessionNode(sessionSelect.value)?.programs || [];

  programSelect.innerHTML = !sessionSelect.value
    ? '<option value="">Select Session first</option>'
    : programs.length
      ? '<option value="">Select Program</option>' + programs.map(p => `<option value="${p.id}">${p.name}</option>`).join('')
      : '<option value="">No programs linked to this session</option>';
  programSelect.disabled = !sessionSelect.value || programs.length === 0;
  if (keepProgram && programs.some(p => String(p.id) === String(keepProgram))) programSelect.value = keepProgram;
  ttPopulateClasses(prefix, keepClass, keepSubject);
}

function ttPopulateClasses(prefix, keepClass = '', keepSubject = '') {
  const sessionId = document.getElementById(prefix + '_session').value;
  const programId = document.getElementById(prefix + '_program').value;
  const classSelect = document.getElementById(prefix + '_class');
  const terms = ttProgramNode(sessionId, programId)?.terms || [];

  classSelect.innerHTML = !programId
    ? '<option value="">Select Program first</option>'
    : terms.length
      ? '<option value="">Select Semester / Part</option>' + terms.map(t => `<option value="${t.id}">${t.name}</option>`).join('')
      : '<option value="">No semesters linked to this program/session</option>';
  classSelect.disabled = !programId || terms.length === 0;
  if (keepClass && terms.some(t => String(t.id) === String(keepClass))) classSelect.value = keepClass;
  ttPopulateSubjects(prefix, keepSubject);
}

function ttPopulateSubjects(prefix, keepSubject = '') {
  const sessionId = document.getElementById(prefix + '_session').value;
  const programId = document.getElementById(prefix + '_program').value;
  const classId = document.getElementById(prefix + '_class').value;
  const subjectSelect = document.getElementById(prefix + '_subject');
  const subjects = ttTermNode(sessionId, programId, classId)?.subjects || [];

  subjectSelect.innerHTML = !classId
    ? '<option value="">Select Semester / Part first</option>'
    : subjects.length
      ? '<option value="">Select Subject</option>' + subjects.map(s => {
          const teacherId = (s.teachers && s.teachers.length) ? s.teachers[0].id : '';
          return `<option value="${s.id}" data-teacher-id="${teacherId}">${s.name}</option>`;
        }).join('')
      : '<option value="">No subjects assigned to this semester</option>';
  subjectSelect.disabled = !classId || subjects.length === 0;
  if (keepSubject && subjects.some(s => String(s.id) === String(keepSubject))) subjectSelect.value = keepSubject;
  if (subjectSelect.value) ttPrefillTeacher(prefix);
}

const ttInitialAdd = {
  session: @json((string) old('academic_year_id', $selectedYearId ?? '')),
  program: @json((string) old('program_id', $selectedProgramId ?? '')),
  classId: @json((string) old('school_class_id', $selectedClassId ?? '')),
  subject: @json((string) old('subject_id', '')),
};

(function initAddPeriodCascade() {
  const sessionSelect = document.getElementById('add_session');
  if (!sessionSelect) return;
  if (ttInitialAdd.session && ttSessionNode(ttInitialAdd.session)) sessionSelect.value = ttInitialAdd.session;
  ttPopulatePrograms('add', ttInitialAdd.program, ttInitialAdd.classId, ttInitialAdd.subject);
})();

function syncAddCascadeFromCurrentFilter() {
  const sessionId = document.getElementById('tt-filter-session')?.value || '';
  const programId = document.getElementById('tt-filter-program')?.value || '';
  const classId   = document.getElementById('tt-filter-class')?.value || '';
  const sessionSelect = document.getElementById('add_session');
  if (sessionSelect && sessionId && ttSessionNode(sessionId)) {
    sessionSelect.value = sessionId;
    ttPopulatePrograms('add', programId, classId, '');
  }
}

function openAddPeriodModal() {
  clearDaySelection();
  syncAddCascadeFromCurrentFilter();
  openModal('addModal');
}

// When a subject is picked, pre-fill the Teacher dropdown with whoever is
// assigned to teach that subject in that class (from Curriculum by Class) —
// still fully changeable, just saves a click for the common case.
function ttPrefillTeacher(prefix) {
  const subjectSelect = document.getElementById(prefix + '_subject');
  const option = subjectSelect.options[subjectSelect.selectedIndex];
  const teacherId = option ? option.getAttribute('data-teacher-id') : '';
  const teacherSelect = document.getElementById(prefix + '_teacher');
  if (teacherId) {
    teacherSelect.value = teacherId;
    checkConflict(teacherSelect);
  }
}

// ── Modal helpers ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-backdrop').forEach(m => {
  m.addEventListener('click', e => { if(e.target === m) closeModal(m.id); });
});

document.addEventListener('keydown', e => {
  if(e.key === 'Escape') document.querySelectorAll('.modal-backdrop.open').forEach(m => closeModal(m.id));
});

// ── Day picker helpers ──
const DAY_ORDER = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

function getDayCheckboxes() {
  return Array.from(document.querySelectorAll('.add-day-checkbox'));
}

function clearDaySelection() {
  getDayCheckboxes().forEach(cb => cb.checked = false);
  checkConflict();
}

// Ticks every day from startDay to endDay inclusive (in Mon–Sat order),
// e.g. selectDayRange('Wednesday','Saturday') checks Wed, Thu, Fri, Sat —
// so the same lecture can be scheduled across a contiguous run of days
// without clicking each one individually.
function selectDayRange(startDay, endDay) {
  const startIdx = DAY_ORDER.indexOf(startDay);
  const endIdx   = DAY_ORDER.indexOf(endDay);
  if (startIdx === -1 || endIdx === -1) return;
  const [lo, hi] = startIdx <= endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
  getDayCheckboxes().forEach(cb => {
    const idx = DAY_ORDER.indexOf(cb.value);
    if (idx >= lo && idx <= hi) cb.checked = true;
  });
  checkConflict();
}

// ── Pre-fill add modal when clicking empty cell ──
function prefillAdd(day, start, end) {
  syncAddCascadeFromCurrentFilter();
  getDayCheckboxes().forEach(cb => cb.checked = (cb.value === day));
  document.getElementById('add_start').value = start;
  document.getElementById('add_end').value   = end;
  openModal('addModal');
}

// ── Open edit modal ──
function openEditModal(id, teacherId, startTime, endTime) {
  const base = "{{ url('admin/timetable') }}";
  document.getElementById('editForm').action      = base + '/' + id;
  document.getElementById('edit_item_id').value   = id;
  document.getElementById('edit_teacher_id').value = teacherId;
  document.getElementById('edit_start_time').value = startTime;
  document.getElementById('edit_end_time').value   = endTime;
  openModal('editModal');
}

// ── Client-side conflict checker ──
function hasConflict(teacherId, day, startTime, endTime, excludeId = null) {
  if (!teacherId || !day || !startTime || !endTime) return null;
  const items = Array.isArray(timetableData) ? timetableData : (timetableData.data || []);
  for (const item of items) {
    if (excludeId && item.id == excludeId) continue;
    if (!item.teacher_id || item.teacher_id != teacherId) continue;
    if (item.day !== day) continue;
    // overlap: not (end <= item.start || start >= item.end)
    const newStart = startTime.replace(':','');
    const newEnd   = endTime.replace(':','');
    const exStart  = (item.start_time||'').replace(':','').substring(0,4);
    const exEnd    = (item.end_time||'').replace(':','').substring(0,4);
    if (!(newEnd <= exStart || newStart >= exEnd)) {
      return item;
    }
  }
  return null;
}

function checkConflict() {
  const teacherId  = document.getElementById('add_teacher').value;
  const selectedDays = getDayCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
  const start      = document.getElementById('add_start').value;
  const end        = document.getElementById('add_end').value;
  const hint       = document.getElementById('add_conflict_hint');
  const msg        = document.getElementById('add_conflict_msg');

  // Check every selected day — a range like Wed–Sat needs to be conflict-free
  // on each individual day, not just the first one.
  let conflict = null, conflictDay = null;
  for (const day of selectedDays) {
    conflict = hasConflict(teacherId, day, start, end);
    if (conflict) { conflictDay = day; break; }
  }

  if (conflict) {
    msg.textContent = `This teacher already has "${conflict.subject?.name ?? 'a class'}" at this time on ${conflictDay}.`;
    hint.classList.add('visible');
  } else {
    hint.classList.remove('visible');
  }
}

function checkEditConflict() {
  const teacherId = document.getElementById('edit_teacher_id').value;
  const start     = document.getElementById('edit_start_time').value;
  const end       = document.getElementById('edit_end_time').value;
  const id        = document.getElementById('edit_item_id').value;
  // We don't have day easily in edit — just check teacher+time
  const hint = document.getElementById('edit_conflict_hint');
  const msg  = document.getElementById('edit_conflict_msg');
  // simplified: just look for same teacher/time in all days
  const items = Array.isArray(timetableData) ? timetableData : (timetableData.data || []);
  let found = null;
  for (const item of items) {
    if (item.id == id) continue;
    if (!item.teacher_id || item.teacher_id != teacherId) continue;
    const newStart = (start||'').replace(':','');
    const newEnd   = (end||'').replace(':','');
    const exStart  = (item.start_time||'').replace(':','').substring(0,4);
    const exEnd    = (item.end_time||'').replace(':','').substring(0,4);
    if (!(newEnd <= exStart || newStart >= exEnd)) { found = item; break; }
  }
  if (found) {
    msg.textContent = `This teacher is already scheduled from ${found.start_time} to ${found.end_time} on ${found.day}.`;
    hint.classList.add('visible');
  } else {
    hint.classList.remove('visible');
  }
}

// Re-open add modal if validation failed
@if($errors->any() && !$errors->has('conflict'))
  openModal('addModal');
@endif
</script>
@endpush

@endsection
