<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>General Student Report</title>
@include('admin.reporting.partials.report-print-style')
<style>
@media print { @page { size: A4 landscape; margin: 8mm; } }
.sheet { max-width:1400px; }
.general-report-table { table-layout:fixed; word-break:break-word; }
.general-report-table th,.general-report-table td { overflow-wrap:anywhere; }
.general-report-table th:first-child,.general-report-table td:first-child { width:37px; text-align:center; }
@media print { .sheet-inner { padding:14px 17px 20px; } table.rpt { font-size:9px; } table.rpt th,table.rpt td { padding:4px 5px; } }
</style>
</head>
<body>
<div class="toolbar">
<button type="button" onclick="window.print()">Download / Print PDF</button>
<a href="javascript:history.back()" class="secondary">Back</a>
</div>
<div class="sheet"><div class="sheet-inner">
@include('admin.reporting.partials.report-letterhead', [
 'reportTitle' => 'General Student Report',
 'reportSubtitle' => ($program->name ?? 'All Programs').' — '.($semester->name ?? 'All Semesters').' — '.($session->name ?? 'All Sessions'),
 'metaLines' => [
   ['Program', $program->name ?? 'Overall (All Programs)'],
   ['Semester / Part', $semester->name ?? 'All Semesters'],
   ['Academic Session', $session->name ?? 'All Sessions'],
   ['Rows', $students->count()],
   ['Selected Columns', count($selected)],
   ['Generated', now()->format('d M Y, h:i A')],
 ],
])
<div class="stat-strip">
 <div class="stat"><div class="num">{{ number_format($students->count()) }}</div><div class="lbl">Total Students</div></div>
 <div class="stat"><div class="num">{{ count($selected) }}</div><div class="lbl">Report Columns</div></div>
 <div class="stat"><div class="num">{{ $program->name ?? 'All' }}</div><div class="lbl">Program</div></div>
 <div class="stat"><div class="num">{{ $session->name ?? 'All' }}</div><div class="lbl">Academic Session</div></div>
</div>
<div class="section-title">Student Details</div>
<table class="rpt general-report-table">
<thead><tr><th>#</th>@foreach($selected as $key)<th>{{ $columns[$key] }}</th>@endforeach</tr></thead>
<tbody>
@forelse($students as $student)
<tr><td>{{ $loop->iteration }}</td>
@foreach($selected as $key)
<td>@switch($key)
@case('status') {{ $student->statusLabel() }} @break
      @case('father_name') {{ $student->father_name ?: ($student->guardian_name ?: '—') }} @break
@case('program') {{ $student->program?->name ?? '—' }} @break
@case('semester') {{ $student->schoolClass?->name ?? '—' }} @break
@case('session') {{ $student->academicYear?->name ?? $student->session ?? '—' }} @break
@default {{ $student->{$key} ?? '—' }}
@endswitch</td>
@endforeach
</tr>
@empty
<tr><td colspan="{{ count($selected) + 1 }}" style="text-align:center">No students match the selected filters.</td></tr>
@endforelse
</tbody></table>
<div class="foot-row">
 <div class="sign-block"><div class="sign-line">Prepared By (Administration)</div></div>
 <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
 <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
</div>
<div class="disclaimer">This is a system-generated report from the Reporting Center — for internal administrative use only.</div>
</div></div>
</body></html>
