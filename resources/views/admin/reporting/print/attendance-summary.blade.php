<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

@php
  $periodLabel = match($period) {
    'monthly' => 'Monthly — '.$from->format('F Y'),
    'yearly'  => 'Yearly — '.$from->format('Y'),
    'range'   => 'Custom Range — '.$from->format('d M Y').' to '.$to->format('d M Y'),
    default   => 'All Time',
  };
@endphp

<div class="sheet">
<div class="sheet-inner">

  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Attendance Report',
    'reportSubtitle' => ($program->name ?? 'All Programs').' — '.$periodLabel,
    'metaLines' => [
      ['Program', $program->name ?? 'Overall (All Programs)'],
      ['Period', $periodLabel],
      ['Students Covered', $perStudent->count()],
      ['Overall Attendance', $overall['pct'].'%'],
    ],
  ])

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $overall['total'] }}</div><div class="lbl">Total Records</div></div>
    <div class="stat"><div class="num">{{ $overall['present'] }}</div><div class="lbl">Present</div></div>
    <div class="stat"><div class="num">{{ $perStudent->count() }}</div><div class="lbl">Students</div></div>
    <div class="stat"><div class="num">{{ $overall['pct'] }}%</div><div class="lbl">Attendance Rate</div></div>
  </div>

  <div class="section-title">Student-wise Attendance</div>
  <table class="rpt">
    <thead>
      <tr>
        <th>#</th><th>Student ID</th><th>Name</th><th>Program</th><th>Semester/Part</th><th>Academic Year</th>
        <th>Total Days</th><th>Present</th><th>Absent</th><th>Leave</th><th>%</th>
      </tr>
    </thead>
    <tbody>
      @forelse($perStudent as $i => $r)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td class="ctr">{{ $r['student']->student_id ?? '—' }}</td>
        <td>{{ $r['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r['student']])</td>
        <td>{{ $r['student']->program->name ?? '—' }}</td>
        <td>{{ $r['student']->schoolClass->name ?? '—' }}</td>
        <td>{{ $r['student']->academicYear->name ?? '—' }}</td>
        <td class="ctr">{{ $r['total'] }}</td>
        <td class="ctr">{{ $r['present'] }}</td>
        <td class="ctr">{{ $r['absent'] }}</td>
        <td class="ctr">{{ $r['leave'] }}</td>
        <td class="ctr">{{ $r['pct'] }}%</td>
      </tr>
      @empty
      <tr><td colspan="11" class="ctr">No attendance records found for the selected filters.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>

  <div class="disclaimer">This is a system-generated report from the Reporting Center — for internal administrative use only.</div>

</div>
</div>

</body>
</html>
