@extends('layouts.app')
@section('title', 'Attendance Record')
@section('content')

@php
  $isAdmin    = auth()->user()->isAdmin();
  $backRoute  = $isAdmin ? route('admin.attendance.index') : route('teacher.attendance.index');
  $status     = $attendance->status ?? 'present';
  $statusConfig = [
    'present' => ['label' => 'Present', 'color' => '#16a34a', 'bg' => 'rgba(22,163,74,.1)',  'icon' => 'fas fa-user-check'],
    'absent'  => ['label' => 'Absent',  'color' => '#dc2626', 'bg' => 'rgba(220,38,38,.1)',  'icon' => 'fas fa-user-times'],
    'late'    => ['label' => 'Late',    'color' => '#d97706', 'bg' => 'rgba(217,119,6,.1)',  'icon' => 'fas fa-user-clock'],
    'leave'   => ['label' => 'Leave',   'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.1)', 'icon' => 'fas fa-user-slash'],
  ];
  $cfg = $statusConfig[$status] ?? $statusConfig['present'];
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
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

.show-wrap { max-width: 640px; }

.btn-outline {
  display:inline-flex; align-items:center; gap:7px;
  padding:8px 16px; border-radius:9px; border:1.5px solid var(--border);
  background:var(--surface); color:var(--muted);
  font-family:'Outfit', sans-serif; font-size:13px; font-weight:600;
  cursor:pointer; transition:border-color .15s,color .15s; text-decoration:none;
}
.btn-outline:hover { border-color:var(--crimson); color:var(--crimson); }

/* ── Hero status card ── */
.show-hero {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow);
  margin-bottom:16px;
}
.show-hero-top {
  padding:24px 24px 20px;
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; gap:18px;
}
.show-avatar {
  width:56px; height:56px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-family:'Outfit', sans-serif; font-size:20px; font-weight:800; color:#fff;
  background:var(--crimson);
}
.show-hero-name { font-family:'Outfit', sans-serif; font-size:18px; font-weight:800; color:var(--text); }
.show-hero-meta { font-size:13px; color:var(--muted); margin-top:3px; }

.show-status-badge {
  margin-left:auto; display:flex; flex-direction:column; align-items:center; gap:6px;
  padding:14px 20px; border-radius:12px; flex-shrink:0;
}
.show-status-badge i  { font-size:22px; }
.show-status-badge span { font-family:'Outfit', sans-serif; font-size:13px; font-weight:800; }

/* ── Detail grid ── */
.show-details {
  display:grid; grid-template-columns:1fr 1fr; gap:0;
}
.detail-item {
  padding:16px 24px; border-right:1px solid var(--border); border-bottom:1px solid var(--border);
}
.detail-item:nth-child(2n) { border-right:none; }
.detail-item:nth-last-child(-n+2) { border-bottom:none; }
.detail-label { font-size:11px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--muted); margin-bottom:5px; }
.detail-value { font-size:14px; font-weight:600; color:var(--text); }

/* ── Monthly Map Card ── */
.map-card {
  background:var(--surface); border:1px solid var(--border);
  border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow);
}
.map-card-header {
  padding:16px 22px; border-bottom:1px solid var(--border);
  display:flex; align-items:center; justify-content:space-between;
}
.map-card-title { font-family:'Outfit', sans-serif; font-size:15px; font-weight:700; }
.map-card-body  { padding:20px 22px; }

.map-legend { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:16px; }
.map-leg-item { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:500; color:var(--text); }
.map-leg-dot  { width:12px; height:12px; border-radius:4px; }

.map-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; margin-bottom:20px; }
.map-day-name { font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); text-align:center; padding-bottom:5px; }
.map-cell {
  aspect-ratio:1; border-radius:9px;
  display:flex; align-items:center; justify-content:center;
  font-family:'Outfit', sans-serif; font-size:12px; font-weight:800; color:#fff;
  transition:transform .12s; cursor:default;
}
.map-cell:hover { transform:scale(1.1); z-index:1; }
.map-cell.present { background:#16a34a; }
.map-cell.absent  { background:#dc2626; }
.map-cell.late    { background:#d97706; }
.map-cell.leave   { background:#f59e0b; }
.map-cell.holiday { background:#fde8e8; color:#c0192e; }
.map-cell.future  { background:#f3f3f5; color:#ccc; }
.map-cell.empty   { background:transparent; }

.map-summary {
  display:flex; gap:24px; flex-wrap:wrap;
  padding-top:16px; border-top:1px solid var(--border);
}
.map-sum-val { font-family:'Outfit', sans-serif; font-size:22px; font-weight:800; line-height:1; }
.map-sum-lbl { font-size:12px; color:var(--muted); margin-top:3px; }
</style>

<div class="show-wrap">
  {{-- Back --}}
  <div style="margin-bottom:18px">
    <a href="{{ $backRoute }}" class="btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  </div>

  {{-- Hero Card --}}
  <div class="show-hero">
    <div class="show-hero-top">
      {{-- Avatar --}}
      @if($attendance->student?->photo)
        <img src="{{ storage_asset($attendance->student->photo) }}"
             style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
      @else
        <div class="show-avatar">
          {{ strtoupper(substr($attendance->student->name ?? 'ST', 0, 2)) }}
        </div>
      @endif

      <div>
        <div class="show-hero-name">{{ $attendance->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $attendance->student])</div>
        <div class="show-hero-meta">
          {{ $attendance->student->student_id ?? '' }}
          @if($attendance->schoolClass) &nbsp;·&nbsp; {{ $attendance->schoolClass->name }} @endif
        </div>
      </div>

      {{-- Status badge --}}
      <div class="show-status-badge" style="background:{{ $cfg['bg'] }}">
        <i class="{{ $cfg['icon'] }}" style="color:{{ $cfg['color'] }}"></i>
        <span style="color:{{ $cfg['color'] }}">{{ $cfg['label'] }}</span>
      </div>
    </div>

    {{-- Detail grid --}}
    <div class="show-details">
      <div class="detail-item">
        <div class="detail-label">Date</div>
        <div class="detail-value">{{ $attendance->date?->format('l, d M Y') ?? '—' }}</div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Class</div>
        <div class="detail-value">{{ $attendance->schoolClass->name ?? '—' }}</div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Roll Number</div>
        <div class="detail-value">{{ $attendance->student->roll_number ?? '—' }}</div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value" style="color:{{ $cfg['color'] }}">
          <i class="{{ $cfg['icon'] }}"></i> {{ $cfg['label'] }}
        </div>
      </div>
      <div class="detail-item" style="grid-column:span 2;border-right:none">
        <div class="detail-label">Remarks</div>
        <div class="detail-value" style="font-weight:400;color:var(--muted)">
          {{ $attendance->remarks ?? 'No remarks recorded.' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Monthly Attendance Map ── built from $monthlyRecord (passed from controller) ── --}}
  @if(!empty($monthlyRecord))
  <div class="map-card">
    <div class="map-card-header">
      <span class="map-card-title">
        Monthly Attendance Map &mdash; {{ $attendance->date?->format('F Y') }}
      </span>
    </div>
    <div class="map-card-body">
      <div class="map-legend">
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#16a34a"></div> Present</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#dc2626"></div> Absent</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#f59e0b"></div> Leave</div>
        <div class="map-leg-item"><div class="map-leg-dot" style="background:#fde8e8;border:1px solid #c0192e"></div> Holiday</div>
      </div>

      @php
        $mapDate    = $attendance->date ?? now();
        $year       = $mapDate->year;
        $month      = $mapDate->month;
        $daysInMonth = $mapDate->daysInMonth;
        $firstDow   = \Carbon\Carbon::create($year, $month, 1)->dayOfWeek; // 0=Sun
        $today      = now();
        $dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

        $present = 0; $absent = 0; $leave = 0; $late = 0;
      @endphp

      <div class="map-grid">
        @foreach($dayNames as $dn)
          <div class="map-day-name">{{ $dn }}</div>
        @endforeach

        @for($i = 0; $i < $firstDow; $i++)
          <div class="map-cell empty"></div>
        @endfor

        @for($d = 1; $d <= $daysInMonth; $d++)
          @php
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow     = \Carbon\Carbon::create($year, $month, $d)->dayOfWeek;
            $isFuture  = \Carbon\Carbon::create($year,$month,$d)->greaterThan($today);
            $isWeekend = $dow === 0 || $dow === 6;
            $rec = $monthlyRecord[$dateStr] ?? null;

            if ($isFuture)       { $cls = 'future';  $lbl = $d; }
            elseif ($isWeekend)  { $cls = 'holiday'; $lbl = 'H'; }
            elseif ($rec)        {
              $cls = $rec;
              $lbl = strtoupper(substr($rec,0,1));
              if ($rec === 'present')            $present++;
              elseif ($rec === 'absent')         $absent++;
              elseif ($rec === 'leave')          $leave++;
              elseif ($rec === 'late')           { $late++; $leave++; }
            } else               { $cls = 'future';  $lbl = $d; }
          @endphp
          <div class="map-cell {{ $cls }}" title="{{ $dateStr }}">{{ $lbl }}</div>
        @endfor
      </div>

      @php
        $total = $present + $absent + $late;
        $rate  = $total > 0 ? round(($present + $late) / $total * 100) : 0;
      @endphp
      <div class="map-summary">
        <div><div class="map-sum-val" style="color:#16a34a">{{ $present }}</div><div class="map-sum-lbl">Present Days</div></div>
        <div><div class="map-sum-val" style="color:#dc2626">{{ $absent }}</div><div class="map-sum-lbl">Absent Days</div></div>
        <div><div class="map-sum-val" style="color:#f59e0b">{{ $leave }}</div><div class="map-sum-lbl">Leave Days</div></div>
        <div><div class="map-sum-val" style="color:var(--crimson)">{{ $rate }}%</div><div class="map-sum-lbl">Rate</div></div>
      </div>
    </div>
  </div>
  @endif
</div>

@endsection
