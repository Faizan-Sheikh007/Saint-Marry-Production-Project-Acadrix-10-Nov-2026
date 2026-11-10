<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Sheet — {{ $selectedClass->name ?? 'Class' }} — {{ $date->format('d M Y') }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
<style>
  .mark-box { display:inline-block; width:14px; height:14px; border:1.3px solid var(--line); border-radius:3px; vertical-align:middle; }
  .mark-box.checked { background:var(--primary); border-color:var(--primary); position:relative; }
  .mark-box.checked::after { content:'\2713'; position:absolute; inset:0; color:#fff; font-size:10px; line-height:13px; text-align:center; }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="sheet">
<div class="sheet-inner">

  @php
    $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
    $instTagline = \App\Models\Setting::get('institution_tagline');
    $campusName  = \App\Models\Setting::get('campus_name');
    $logoPath    = \App\Models\Setting::get('institution_logo');

    $presentCount = $existingAttendance->filter(fn($s) => $s === 'present')->count();
    $absentCount  = $existingAttendance->filter(fn($s) => $s === 'absent')->count();
    $lateCount    = $existingAttendance->filter(fn($s) => $s === 'late')->count();
    $leaveCount   = $existingAttendance->filter(fn($s) => $s === 'leave')->count();
  @endphp

  <div class="letterhead">
    <div class="logo-wrap">
      @if($logoPath)
        <img src="{{ storage_asset($logoPath) }}" alt="Logo">
      @else
        <div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $instName), 0, 2)) ?: 'IN' }}</div>
      @endif
    </div>
    <div class="center">
      <div class="inst-name">{{ $instName }}</div>
      @if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif
      @if($campusName)<div class="inst-address">{{ $campusName }}</div>@endif
      <div class="doc-name">Daily Attendance Sheet</div>
      <div class="doc-sub">{{ $selectedClass->name ?? 'No Class Selected' }}{{ $selectedSubject ? ' · '.$selectedSubject->name : '' }} &middot; {{ $date->format('l, d M Y') }}</div>
    </div>
    <div class="spacer"></div>
  </div>

  <hr class="rule">

  <div class="meta-grid" style="grid-template-columns:1fr 1fr">
    <div class="row"><span class="lbl">Class</span><span class="val">{{ $selectedClass->name ?? '—' }}</span></div>
    <div class="row"><span class="lbl">Subject</span><span class="val">{{ $selectedSubject->name ?? 'General / Whole Class' }}</span></div>
    <div class="row"><span class="lbl">Date</span><span class="val">{{ $date->format('d M Y') }}</span></div>
  </div>

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $students->count() }}</div><div class="lbl">Total Students</div></div>
    <div class="stat"><div class="num">{{ $presentCount }}</div><div class="lbl">Present</div></div>
    <div class="stat"><div class="num">{{ $absentCount }}</div><div class="lbl">Absent</div></div>
    <div class="stat"><div class="num">{{ $lateCount }}</div><div class="lbl">Late</div></div>
    <div class="stat"><div class="num">{{ $leaveCount }}</div><div class="lbl">Leave</div></div>
  </div>

  @if($existingAttendance->isEmpty())
  <div class="section-title" style="color:var(--muted);border-bottom:none">Not marked yet for this date — sheet reflects a blank roster.</div>
  @endif

  <table class="rpt">
    <thead>
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Roll No</th>
        <th>Present</th>
        <th>Absent</th>
        <th>Late</th>
        <th>Leave</th>
        <th>Signature</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $student)
      @php $status = $existingAttendance[$student->id] ?? null; @endphp
      <tr>
        <td class="ctr">{{ $loop->iteration }}</td>
        <td>
          <div style="font-weight:700">{{ $student->name }}<br>@include('partials.report-student-status', ['statusStudent' => $student])</div>
          <div style="font-size:9.5px;color:var(--muted)">{{ $student->student_id }}</div>
        </td>
        <td class="ctr">{{ $student->roll_number ?: '—' }}</td>
        @foreach(['present','absent','late','leave'] as $opt)
        <td class="ctr"><span class="mark-box {{ $status === $opt ? 'checked' : '' }}"></span></td>
        @endforeach
        <td></td>
      </tr>
      @empty
      <tr><td colspan="8" class="ctr" style="padding:20px;color:var(--muted)">No active students found for this class.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Class Teacher</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>

  <div class="disclaimer">This is a system-generated report — for internal administrative use only.</div>

</div>
</div>

</body>
</html>
