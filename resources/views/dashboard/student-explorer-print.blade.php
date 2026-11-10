<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Selected Students List</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
  <a href="javascript:window.close()" class="secondary">Close</a>
</div>

<div class="sheet">
<div class="sheet-inner">

  @php
    $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
    $instTagline = \App\Models\Setting::get('institution_tagline');
    $campusName  = \App\Models\Setting::get('campus_name');
    $logoPath    = \App\Models\Setting::get('institution_logo');

    $programs = $students->pluck('program.name')->filter()->unique()->values();
    $sessions = $students->map(fn($s) => $s->academicYear->name ?? $s->session)->filter()->unique()->values();
    $terms    = $students->map(fn($s) => $s->schoolClass->section_label ?? null)->filter()->unique()->values();
    $activeCount = $students->where('status', 'active')->count();
    $totalDues = $students->sum(fn($s) => $s->pendingDues());

    $summaryLabel = static function ($values, $empty = '—') {
        if ($values->isEmpty()) return $empty;
        if ($values->count() <= 3) return $values->implode(', ');
        return $values->take(3)->implode(', ') . ' +' . ($values->count() - 3) . ' more';
    };
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
      <div class="doc-name">Selected Students List</div>
      <div class="doc-sub">Accounts Dashboard Student Explorer</div>
    </div>
    <div class="spacer"></div>
  </div>

  <hr class="rule">

  <div class="meta-grid">
    <div class="row"><span class="lbl">Program</span><span class="val">{{ $summaryLabel($programs, 'Multiple / Not set') }}</span></div>
    <div class="row"><span class="lbl">Session</span><span class="val">{{ $summaryLabel($sessions, 'Not set') }}</span></div>
    <div class="row" style="grid-column:1 / -1"><span class="lbl">Semester / Part</span><span class="val">{{ $summaryLabel($terms, 'Not set') }}</span></div>
  </div>

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $students->count() }}</div><div class="lbl">Selected Students</div></div>
    <div class="stat"><div class="num">{{ $activeCount }}</div><div class="lbl">Active</div></div>
    <div class="stat"><div class="num">{{ $students->count() - $activeCount }}</div><div class="lbl">Other Status</div></div>
    <div class="stat"><div class="num">{{ number_format($totalDues, 0) }}</div><div class="lbl">Pending Dues (Rs.)</div></div>
  </div>

  <table class="rpt">
    <thead>
      <tr>
        <th>SR #</th>
        <th>Student ID</th>
        <th>Student</th>
        <th>Program</th>
        <th>Session</th>
        <th>Semester / Part</th>
        <th>Contact</th>
        <th>Pending Balance</th>
        <th>Student Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $student)
      <tr>
        <td class="ctr">{{ $loop->iteration }}</td>
        <td class="ctr" style="white-space:nowrap;font-weight:700">{{ $student->student_id ?: '—' }}</td>
        <td><strong>{{ $student->name }}</strong></td>
        <td>{{ $student->program->name ?? '—' }}</td>
        <td>{{ $student->academicYear->name ?? ($student->session ?: '—') }}</td>
        <td>{{ $student->schoolClass->section_label ?? '—' }}</td>
        <td class="ctr">{{ $student->phone ?: '—' }}</td>
        <td class="num">Rs.{{ number_format($student->pendingDues(), 0) }}</td>
        <td class="ctr">{{ $student->statusLabel() }}</td>
      </tr>
      @empty
      <tr><td colspan="9" class="ctr" style="padding:20px;color:var(--muted)">No students selected.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="7" class="ctr">Totals</td>
        <td class="num">Rs.{{ number_format($totalDues, 0) }}</td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Accounts Officer</div></div>
  </div>

  <div class="disclaimer">System-generated selected student list for internal administrative use.</div>

</div>
</div>

</body>
</html>
