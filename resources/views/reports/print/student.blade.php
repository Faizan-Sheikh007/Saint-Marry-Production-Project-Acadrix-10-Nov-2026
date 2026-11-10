<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Report - {{ $student->name }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
<style>
  .photo-box{width:78px;height:92px;border:1px solid #777;display:flex;align-items:center;justify-content:center;font-size:9px;text-align:center;color:#666;overflow:hidden;flex-shrink:0}.photo-box img{width:100%;height:100%;object-fit:cover}
  .remark-box{border:1px solid #bbb;padding:8px 10px;margin:7px 0;font-size:11px;line-height:1.45}.remark-meta{font-weight:700;margin-bottom:3px}
</style>
</head>
<body>
<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Save / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>
<div class="sheet"><div class="sheet-inner">
@php
  $instName = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $campusName = \App\Models\Setting::get('campus_name');
  $logoPath = \App\Models\Setting::get('institution_logo');
@endphp
<div class="letterhead">
  <div class="logo-wrap">@if($logoPath)<img src="{{ storage_asset($logoPath) }}" alt="Logo">@else<div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/','',$instName),0,2)) ?: 'IN' }}</div>@endif</div>
  <div class="center"><div class="inst-name">{{ $instName }}</div>@if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif @if($campusName)<div class="inst-address">{{ $campusName }}</div>@endif<div class="doc-name">Comprehensive Student Report</div><div class="doc-sub">Academic Performance • Attendance • Fees • Teacher Remarks</div></div>
  <div class="photo-box">@if($student->photo)<img src="{{ storage_asset($student->photo) }}">@else Student Photo@endif</div>
</div>
<hr class="rule">
<div class="meta-grid" style="grid-template-columns:1fr 1fr 1fr">
  <div class="row"><span class="lbl">Student ID</span><span class="val">{{ $student->student_id ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Name</span><span class="val">{{ $student->name }}</span></div>
  <div class="row"><span class="lbl">Roll No</span><span class="val">{{ $student->roll_number ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Program</span><span class="val">{{ $student->program->name ?? '—' }}</span></div>
  <div class="row"><span class="lbl">Semester/Part</span><span class="val">{{ $student->schoolClass->section_label ?? '—' }}</span></div>
  <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $student->academicYear->name ?? '—' }}</span></div>
  <div class="row"><span class="lbl">Father</span><span class="val">{{ $student->father_name ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Guardian</span><span class="val">{{ $student->guardian_name ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Phone</span><span class="val">{{ $student->phone ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Admission</span><span class="val">{{ $student->admission_date?->format('d M Y') ?: '—' }}</span></div>
  <div class="row"><span class="lbl">Student Status</span><span class="val">{{ $student->statusLabel() }}</span></div>
  <div class="row"><span class="lbl">Report Period</span><span class="val">{{ $from?->format('d M Y') ?? 'All records' }}{{ $to ? ' – '.$to->format('d M Y') : '' }}</span></div>
</div>

<div class="stat-strip">
  <div class="stat"><div class="num">{{ $attendanceSummary['percentage'] }}%</div><div class="lbl">Attendance</div></div>
  <div class="stat"><div class="num">{{ $examSummary['average'] }}%</div><div class="lbl">Exam Average</div></div>
  <div class="stat"><div class="num">{{ $examSummary['count'] }}</div><div class="lbl">Exam Results</div></div>
  <div class="stat"><div class="num">{{ number_format($feeSummary['paid'],0) }}</div><div class="lbl">Paid Fee (Rs.)</div></div>
  <div class="stat"><div class="num">{{ number_format($feeSummary['pending'],0) }}</div><div class="lbl">Pending Fee (Rs.)</div></div>
</div>

<div class="section-title">Attendance Summary</div>
<table class="rpt"><thead><tr><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th><th>Attendance %</th></tr></thead><tbody><tr><td class="ctr">{{ $attendanceSummary['total'] }}</td><td class="ctr">{{ $attendanceSummary['present'] }}</td><td class="ctr">{{ $attendanceSummary['absent'] }}</td><td class="ctr">{{ $attendanceSummary['late'] }}</td><td class="ctr">{{ $attendanceSummary['leave'] }}</td><td class="ctr"><strong>{{ $attendanceSummary['percentage'] }}%</strong></td></tr></tbody></table>

<div class="section-title">Multiple Exam Results</div>
<table class="rpt"><thead><tr><th>#</th><th>Date</th><th>Exam</th><th>Subject</th><th>Type</th><th>Marks</th><th>%</th><th>Grade</th><th>Remarks</th></tr></thead><tbody>
@forelse($examResults as $i=>$result)<tr><td class="ctr">{{ $i+1 }}</td><td class="ctr">{{ $result->examination?->exam_date?->format('d M Y') ?? '—' }}</td><td>{{ $result->examination?->name ?? '—' }}</td><td>{{ $result->examination?->subject?->name ?? '—' }}</td><td class="ctr">{{ $result->examination?->type_label ?? '—' }}</td><td class="ctr">{{ number_format((float)$result->marks_obtained,1) }}/{{ $result->examination?->total_marks ?? 0 }}</td><td class="ctr">{{ number_format((float)$result->percentage,1) }}%</td><td class="ctr">{{ $result->grade ?: '—' }}</td><td>{{ $result->remarks ?: '—' }}</td></tr>@empty<tr><td colspan="9" class="ctr">No exam results found.</td></tr>@endforelse
</tbody></table>

<div class="section-title">Fee Summary</div>
<div class="meta-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="row"><span class="lbl">Fee Status</span><span class="val">{{ $feeSummary['status'] }}</span></div>
  <div class="row"><span class="lbl">Total Charged</span><span class="val">Rs. {{ number_format($feeSummary['charged'],0) }}</span></div>
  <div class="row"><span class="lbl">Paid</span><span class="val">Rs. {{ number_format($feeSummary['paid'],0) }}</span></div>
  <div class="row"><span class="lbl">Pending</span><span class="val">Rs. {{ number_format($feeSummary['pending'],0) }}</span></div>
  <div class="row"><span class="lbl">Overdue</span><span class="val">Rs. {{ number_format($feeSummary['overdue'],0) }}</span></div>
  <div class="row"><span class="lbl">Scholarship / Aid</span><span class="val">Rs. {{ number_format($reliefSummary['total'],0) }}</span></div>
</div>
@if($pendingVouchers->count())
<table class="rpt"><thead><tr><th>Voucher</th><th>Title</th><th>Due Date</th><th>Payable</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
@foreach($pendingVouchers as $v)<tr><td class="ctr">{{ $v->voucher_no }}</td><td>{{ $v->title }}</td><td class="ctr">{{ $v->due_date?->format('d M Y') ?? '—' }}</td><td class="num">{{ number_format($v->total(),0) }}</td><td class="num">{{ number_format($v->paid_amount,0) }}</td><td class="num">{{ number_format($v->balance(),0) }}</td><td class="ctr">{{ $v->autoStatusLabel() }}</td></tr>@endforeach
</tbody></table>
@endif

<div class="section-title">Teacher / Academic Remarks</div>
@forelse($remarks as $remark)
  <div class="remark-box"><div class="remark-meta">{{ $remark->teacher_name ?: 'Teacher / Academic Office' }}{{ $remark->term ? ' — '.$remark->term : '' }} • {{ $remark->remark_date?->format('d M Y') ?? $remark->created_at?->format('d M Y') }}</div>{{ $remark->remarks }}</div>
@empty<div style="font-size:11px;color:#666;padding:7px 0">No manual teacher remarks recorded.</div>@endforelse

<div class="section-title">Recent Attendance Detail</div>
<table class="rpt"><thead><tr><th>Date</th><th>Subject</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
@forelse($attendanceRecords->take(25) as $r)<tr><td class="ctr">{{ $r->date?->format('d M Y') }}</td><td>{{ $r->subject?->name ?? 'General' }}</td><td class="ctr">{{ ucfirst($r->status) }}</td><td>{{ $r->remarks ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="ctr">No attendance records found.</td></tr>@endforelse
</tbody></table>

<div class="foot-row"><div class="sign-block"><div class="sign-line">Class Teacher / Prepared By</div></div><div class="gen-date">Generated {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
<div class="disclaimer">System-generated comprehensive student report. Exam results and fee records remain subject to the institution's official source records.</div>
</div></div>
</body></html>
