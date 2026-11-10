@extends('layouts.app')
@section('title', 'Attendance Report')
@section('content')

@php
  $isAdmin     = auth()->user()->isAdmin();
  $reportRoute = $isAdmin ? 'admin.attendance.report' : 'teacher.attendance.report';
  $indexRoute  = $isAdmin ? 'admin.attendance.index'  : 'teacher.attendance.index';
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
  --green:  #16a34a; --green-bg:  rgba(22,163,74,.09);
  --red:    #dc2626; --red-bg:    rgba(220,38,38,.09);
  --orange: #d97706; --orange-bg: rgba(217,119,6,.09);
  --indigo: #6366f1; --indigo-bg: rgba(99,102,241,.09);
  --blue:   #2563eb; --blue-bg:   rgba(37,99,235,.09);
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

/* ── Header ── */
.rpt-header {
  display:flex; align-items:flex-end; justify-content:space-between;
  flex-wrap:wrap; gap:14px; margin-bottom:22px;
}
.rpt-header-left h1 { font-family:'Outfit', sans-serif; font-size:22px; font-weight:800; color:var(--text); }
.rpt-header-left p  { font-size:13px; color:var(--muted); margin-top:3px; }
.rpt-actions { display:flex; gap:8px; flex-wrap:wrap; }

.btn-outline {
  display:inline-flex; align-items:center; gap:7px;
  padding:8px 16px; border-radius:9px; border:1.5px solid var(--border);
  background:var(--surface); color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:13px; font-weight:600;
  cursor:pointer; transition:border-color .15s,color .15s; text-decoration:none;
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
  transition:border-color .15s; min-width:150px;
}
.form-control:focus { outline:none; border-color:var(--crimson); }

/* ── Stat Row ── */
.stat-row  { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.stat-mini {
  flex:1; min-width:110px;
  background:var(--surface); border:1px solid var(--border);
  border-radius:12px; padding:14px 16px;
  display:flex; align-items:center; gap:12px;
  box-shadow:var(--shadow); transition:transform .15s;
}
.stat-mini:hover { transform:translateY(-2px); }
.stat-mini-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.stat-mini-val  { font-family:'Outfit', sans-serif; font-size:20px; font-weight:800; line-height:1; }
.stat-mini-lbl  { font-size:11px; color:var(--muted); margin-top:3px; }

/* ── Report Card ── */
.rpt-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow);
}
.rpt-card-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:8px;
}
.rpt-card-title { font-family:'Outfit', sans-serif; font-size:15px; font-weight:700; }
.rpt-info { font-size:12.5px; color:var(--muted); display:flex; align-items:center; gap:5px; }

/* ── Table ── */
.rpt-table { width:100%; border-collapse:collapse; }
.rpt-table th {
  font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  color:var(--muted); padding:10px 14px; background:#fdf6f7;
  border-bottom:1px solid var(--border); text-align:left;
}
.rpt-table th.center { text-align:center; }
.rpt-table td { padding:10px 14px; border-top:1px solid var(--border); font-size:13.5px; vertical-align:middle; }
.rpt-table tr:hover td { background:#fdf6f7; }

/* student cell */
.stu-cell { display:flex; align-items:center; gap:10px; }
.stu-avatar {
  width:34px; height:34px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-family:'Outfit', sans-serif; font-size:12px; font-weight:700; color:#fff;
}

/* progress bar */
.pct-wrap { display:flex; align-items:center; justify-content:center; gap:8px; }
.pct-bar  { width:70px; height:5px; border-radius:99px; background:#e9e3e4; overflow:hidden; }
.pct-fill { height:100%; border-radius:99px; }
.pct-val  { font-weight:700; min-width:40px; font-size:13px; }

/* ── Card Footer ── */
.rpt-footer {
  padding:12px 20px; border-top:1px solid var(--border);
  display:flex; gap:20px; flex-wrap:wrap; font-size:12.5px; color:var(--muted); align-items:center;
}
.rpt-footer strong { font-weight:700; }

/* flash */
.flash-ok {
  display:flex; align-items:center; gap:10px;
  background:#f0fdf4; border:1px solid #bbf7d0;
  border-radius:10px; padding:12px 18px; margin-bottom:16px;
  font-size:13.5px; color:#166534;
}

/* empty */
.rpt-empty { padding:60px 20px; text-align:center; color:var(--muted); }
.rpt-empty i { font-size:40px; opacity:.2; display:block; margin-bottom:12px; }
.rpt-empty p { font-size:14px; }
</style>

@if(session('success'))
<div class="flash-ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Header --}}
<div class="rpt-header">
  <div class="rpt-header-left">
    <h1>Attendance Report</h1>
    <p>
      <i class="fas fa-calendar-day"></i>
      {{ $selectedClass?->name }} &mdash;
      @if($filterType === 'date')
        {{ $date->format('l, d M Y') }}
      @else
        {{ $startDate->format('d M') }} – {{ $endDate->format('d M Y') }}
      @endif
    </p>
  </div>
  <div class="rpt-actions">
    <a href="{{ route($indexRoute, ['class_id' => $selectedClass?->id]) }}" class="btn-outline">
      <i class="fas fa-arrow-left"></i> Back
    </a>
    <a href="{{ route($reportRoute, [
        'class_id'      => $selectedClass?->id,
        'filter_type'   => $filterType,
        'specific_date' => $filterType === 'date'  ? $date->format('Y-m-d') : null,
        'month_date'    => $filterType === 'month' ? $date->format('Y-m')   : null,
        'download'      => 1,
      ]) }}" class="btn-outline">
      <i class="fas fa-file-excel"></i> Download CSV
    </a>
    <button onclick="window.print()" class="btn-outline">
      <i class="fas fa-print"></i> Print
    </button>
  </div>
</div>

{{-- Filters --}}
<div class="filter-card">
  <form method="GET" action="{{ route($reportRoute) }}" data-ajax-filter>
    <div class="filter-row">
      <div class="form-group">
        <label class="form-label">Class</label>
        <select name="class_id" class="form-control" onchange="this.form.requestSubmit()">
          @foreach($classes as $class)
            <option value="{{ $class->id }}" {{ $selectedClass?->id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Filter By</label>
        <select name="filter_type" class="form-control" onchange="toggleDateInput(this.value); this.form.requestSubmit()">
          <option value="month" {{ $filterType === 'month' ? 'selected' : '' }}>Month</option>
          <option value="date"  {{ $filterType === 'date'  ? 'selected' : '' }}>Specific Date</option>
        </select>
      </div>
      <div class="form-group" id="input-date" style="{{ $filterType !== 'date' ? 'display:none' : '' }}">
        <label class="form-label">Date</label>
        <input type="date" name="specific_date" class="form-control"
               value="{{ $filterType === 'date' ? $date->format('Y-m-d') : '' }}"
               onchange="this.form.requestSubmit()">
      </div>
      <div class="form-group" id="input-month" style="{{ $filterType !== 'month' ? 'display:none' : '' }}">
        <label class="form-label">Month</label>
        <input type="month" name="month_date" class="form-control"
               value="{{ $filterType === 'month' ? $date->format('Y-m') : '' }}"
               onchange="this.form.requestSubmit()">
      </div>
      <input type="hidden" name="filter_type" id="hidden_filter_type" value="{{ $filterType }}">
    </div>
  </form>
</div>

{{-- Stats --}}
@php
  $totalPresent = array_sum(array_column($summary, 'present'));
  $totalAbsent  = array_sum(array_column($summary, 'absent'));
  $totalLate    = array_sum(array_column($summary, 'late'));
  $totalLeave   = array_sum(array_column($summary, 'leave'));
@endphp
<div class="stat-row">
  @foreach([
    ['Working Days', $workingDays,  '--blue',   '--blue-bg',   'fas fa-calendar-check'],
    ['Present',      $totalPresent, '--green',  '--green-bg',  'fas fa-user-check'],
    ['Absent',       $totalAbsent,  '--red',    '--red-bg',    'fas fa-user-times'],
    ['Late',         $totalLate,    '--orange', '--orange-bg', 'fas fa-user-clock'],
    ['Leave',        $totalLeave,   '--indigo', '--indigo-bg', 'fas fa-user-slash'],
  ] as [$lbl, $val, $color, $bg, $icon])
  <div class="stat-mini">
    <div class="stat-mini-icon" style="background:var({{ $bg }})">
      <i class="{{ $icon }}" style="color:var({{ $color }})"></i>
    </div>
    <div>
      <div class="stat-mini-val" style="color:var({{ $color }})">{{ $val }}</div>
      <div class="stat-mini-lbl">{{ $lbl }}</div>
    </div>
  </div>
  @endforeach
</div>

{{-- Table --}}
@if($students->isEmpty())
  <div class="rpt-card">
    <div class="rpt-empty">
      <i class="fas fa-chart-bar"></i>
      <p>No students found for this class.</p>
    </div>
  </div>
@else
  @php $avatarColors = ['#8b0c22','#2563eb','#0d9e6e','#7c3aed','#d97706','#0891b2','#be185d']; @endphp
  <div class="rpt-card">
    <div class="rpt-card-header">
      <span class="rpt-card-title">{{ $selectedClass->name }} &mdash; {{ $students->count() }} Students</span>
      @if($filterType === 'month')
      <span class="rpt-info"><i class="fas fa-info-circle"></i> Attendance % = Present + Late</span>
      @endif
    </div>

    <div style="overflow-x:auto">
      <table class="rpt-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Roll No</th>
            <th class="center" style="color:var(--green)">Present</th>
            <th class="center" style="color:var(--red)">Absent</th>
            <th class="center" style="color:var(--orange)">Late</th>
            <th class="center" style="color:var(--indigo)">Leave</th>
            @if($filterType === 'month')
            <th class="center">Attendance %</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse($students as $student)
          @php
            $s   = $summary[$student->id];
            $pct = $workingDays > 0 ? round(($s['present'] + $s['late']) / $workingDays * 100, 1) : 0;
            $pctColor = $pct >= 75 ? 'var(--green)' : ($pct >= 50 ? 'var(--orange)' : 'var(--red)');
          @endphp
          <tr>
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
                  <div style="font-weight:600;font-size:13.5px">{{ $student->name }}<br>@include('partials.report-student-status', ['statusStudent' => $student])</div>
                  <div style="font-size:11px;color:var(--muted)">{{ $student->student_id }}</div>
                </div>
              </div>
            </td>
            <td style="color:var(--muted)">{{ $student->roll_number }}</td>
            <td style="text-align:center"><span style="font-weight:700;color:var(--green)">{{ $s['present'] }}</span></td>
            <td style="text-align:center"><span style="font-weight:700;color:var(--red)">{{ $s['absent'] }}</span></td>
            <td style="text-align:center"><span style="font-weight:700;color:var(--orange)">{{ $s['late'] }}</span></td>
            <td style="text-align:center"><span style="font-weight:700;color:var(--indigo)">{{ $s['leave'] }}</span></td>
            @if($filterType === 'month')
            <td>
              <div class="pct-wrap">
                <div class="pct-bar">
                  <div class="pct-fill" style="width:{{ $pct }}%;background:{{ $pctColor }}"></div>
                </div>
                <span class="pct-val" style="color:{{ $pctColor }}">{{ $pct }}%</span>
              </div>
            </td>
            @endif
          </tr>
          @empty
          <tr><td colspan="{{ $filterType === 'month' ? 8 : 7 }}" style="text-align:center;padding:40px;color:var(--muted)">No attendance records found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="rpt-footer">
      <span><i class="fas fa-user-check" style="color:var(--green)"></i> Present: <strong style="color:var(--green)">{{ $totalPresent }}</strong></span>
      <span><i class="fas fa-user-times" style="color:var(--red)"></i> Absent: <strong style="color:var(--red)">{{ $totalAbsent }}</strong></span>
      <span><i class="fas fa-user-clock" style="color:var(--orange)"></i> Late: <strong style="color:var(--orange)">{{ $totalLate }}</strong></span>
      <span><i class="fas fa-user-slash" style="color:var(--indigo)"></i> Leave: <strong style="color:var(--indigo)">{{ $totalLeave }}</strong></span>
      @if($filterType === 'month')
      <span style="margin-left:auto"><i class="fas fa-calendar-check" style="color:var(--blue)"></i> Working Days: <strong style="color:var(--blue)">{{ $workingDays }}</strong></span>
      @endif
    </div>
  </div>
@endif

@push('scripts')
<script>
function toggleDateInput(type) {
  document.getElementById('input-date').style.display  = type === 'date'  ? '' : 'none';
  document.getElementById('input-month').style.display = type === 'month' ? '' : 'none';
  document.getElementById('hidden_filter_type').value  = type;
}
</script>
@endpush

@endsection
