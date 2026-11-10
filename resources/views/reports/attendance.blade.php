@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page_title', 'Attendance Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $attendanceRoute = $accountsReport ? 'accounts.reports.attendance' : 'admin.reports.attendance';
  $printRoute = $accountsReport ? 'accounts.reports.attendance.print' : 'admin.reports.attendance.print';
  $pdfRoute = $accountsReport ? 'accounts.reports.attendance.pdf' : 'admin.reports.attendance.pdf';
  $studentRoute = $accountsReport ? 'accounts.reports.attendance.student' : 'admin.reports.attendance.student';
  $studentPdfRoute = $accountsReport ? 'accounts.reports.attendance.student.pdf' : 'admin.reports.attendance.student.pdf';
  $exportRoute = $accountsReport ? 'reports.export' : 'admin.reports.export';
  $mode = request('view','summary');
@endphp
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;gap:12px;flex-wrap:wrap">
  <div style="display:flex;gap:12px;align-items:center"><a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a><div><div style="font-size:22px;font-weight:850">Attendance Report</div><div style="font-size:12px;color:var(--text-muted)">Student-wise attendance rates plus raw daily records.</div></div></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="{{ route($exportRoute, array_merge(['type'=>'attendance'],request()->query())) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> CSV</a>
    <a href="{{ route($printRoute, request()->query()) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Print</a>
    <a href="{{ route($pdfRoute, request()->query()) }}" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:12px;margin-bottom:20px">
@foreach([
 ['Records',$summary['total'],'var(--primary)'],['Present',$summary['present'],'var(--success)'],['Absent',$summary['absent'],'var(--danger)'],['Late',$summary['late'],'var(--warning)'],['Leave',$summary['leave'],'#6366f1'],['Attendance Rate',$summary['percentage'].'%', $summary['percentage']>=75?'var(--success)':'var(--danger)']
] as [$label,$value,$color])<div class="stat-card"><div><div style="font-size:22px;font-weight:850;color:{{ $color }}">{{ $value }}</div><div style="font-size:11px;color:var(--text-muted)">{{ $label }}</div></div></div>@endforeach
</div>

<div class="card" style="margin-bottom:18px"><div class="card-body" style="padding:15px 18px">
<form method="GET" style="display:grid;grid-template-columns:1.5fr repeat(7,minmax(125px,1fr)) auto auto;gap:9px;align-items:end" data-ajax-filter>
  <input type="hidden" name="view" value="{{ $mode }}">
  <div><label class="form-label">Search Student</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name, ID or roll no"></div>
  <div><label class="form-label">From</label><input type="date" class="form-control" name="from" value="{{ $from->format('Y-m-d') }}"></div>
  <div><label class="form-label">To</label><input type="date" class="form-control" name="to" value="{{ $to->format('Y-m-d') }}"></div>
  <div><label class="form-label">Program</label><select class="form-control" name="program_id"><option value="">All</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('program_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
  <div><label class="form-label">Semester/Part</label><select class="form-control" name="class_id"><option value="">All</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>{{ $c->display_name }}</option>@endforeach</select></div>
  <div><label class="form-label">Academic Year</label><select class="form-control" name="academic_year_id"><option value="">All Years</option>@foreach($academicYears as $y)<option value="{{ $y->id }}" {{ request('academic_year_id')==$y->id?'selected':'' }}>{{ $y->name }}</option>@endforeach</select></div>
  <div><label class="form-label">Subject</label><select class="form-control" name="subject_id"><option value="">All</option>@foreach($subjects as $s)<option value="{{ $s->id }}" {{ request('subject_id')==$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></div>
  <div><label class="form-label">Status</label><select class="form-control" name="status"><option value="">All</option>@foreach(['present','absent','late','leave'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select></div>
  <button class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button><a href="{{ route($attendanceRoute) }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
</form></div></div>
<style>@media(max-width:1350px){form[data-ajax-filter]{grid-template-columns:repeat(4,minmax(140px,1fr))!important}}@media(max-width:760px){form[data-ajax-filter]{grid-template-columns:1fr!important}}</style>

<div style="display:flex;gap:8px;margin-bottom:12px">
  <a href="{{ route($attendanceRoute, array_merge(request()->except(['view','summary_page','records_page']),['view'=>'summary'])) }}" class="btn {{ $mode==='summary'?'btn-primary':'btn-outline' }} btn-sm"><i class="fas fa-chart-pie"></i> Student Summary</a>
  <a href="{{ route($attendanceRoute, array_merge(request()->except(['view','summary_page','records_page']),['view'=>'records'])) }}" class="btn {{ $mode==='records'?'btn-primary':'btn-outline' }} btn-sm"><i class="fas fa-list"></i> Daily Records</a>
</div>

@if($mode==='records')
<div class="card"><div style="padding:13px 18px;border-bottom:1px solid var(--border);font-weight:700">{{ $records->total() }} attendance record(s)</div><div class="table-wrap"><table>
<thead><tr><th>#</th><th>Student</th><th>Program / Semester</th><th>Academic Year</th><th>Subject</th><th>Date</th><th>Status</th><th>Remarks</th><th>Student Report</th></tr></thead><tbody>
@forelse($records as $r)<tr><td>{{ ($records->currentPage()-1)*$records->perPage()+$loop->iteration }}</td><td><strong>{{ $r->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r->student])</strong><div style="font-size:10px;color:var(--text-muted)">{{ $r->student->student_id ?? '' }}</div></td><td>{{ $r->student->program->name ?? '—' }}<div style="font-size:10px;color:var(--text-muted)">{{ $r->student->schoolClass->section_label ?? '—' }}</div></td><td>{{ $r->student->academicYear->name ?? '—' }}</td><td>{{ $r->subject->name ?? 'General' }}</td><td>{{ $r->date?->format('d M Y') }}</td><td><span class="badge {{ $r->status==='present'?'success':($r->status==='absent'?'danger':'warning') }}">{{ ucfirst($r->status) }}</span></td><td>{{ $r->remarks ?: '—' }}</td><td>@if($r->student)<a class="btn btn-outline btn-sm" href="{{ route($studentRoute, array_merge(['student'=>$r->student],request()->only(['from','to','subject_id']))) }}"><i class="fas fa-eye"></i></a>@endif</td></tr>@empty<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">No records found.</td></tr>@endforelse
</tbody></table></div><div style="padding:15px 18px">{{ $records->links() }}</div></div>
@else
<div class="card"><div style="padding:13px 18px;border-bottom:1px solid var(--border);font-weight:700">{{ $perStudent->total() }} student(s) covered • {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div><div class="table-wrap"><table>
<thead><tr><th>#</th><th>Student</th><th>Program / Semester</th><th>Academic Year</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Leave</th><th>Attendance %</th><th>Report</th></tr></thead><tbody>
@forelse($perStudent as $r) @php $student=$r['student']; $c=$r['percentage']>=90?'var(--success)':($r['percentage']>=75?'var(--warning)':'var(--danger)'); @endphp<tr><td>{{ ($perStudent->currentPage()-1)*$perStudent->perPage()+$loop->iteration }}</td><td><strong>{{ $student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $student])</strong><div style="font-size:10px;color:var(--text-muted)">{{ $student->student_id ?? '' }}</div></td><td>{{ $student->program->name ?? '—' }}<div style="font-size:10px;color:var(--text-muted)">{{ $student->schoolClass->section_label ?? '—' }}</div></td><td>{{ $student->academicYear->name ?? '—' }}</td><td>{{ $r['total'] }}</td><td style="color:var(--success);font-weight:700">{{ $r['present'] }}</td><td style="color:var(--danger);font-weight:700">{{ $r['absent'] }}</td><td>{{ $r['late'] }}</td><td>{{ $r['leave'] }}</td><td><strong style="color:{{ $c }}">{{ $r['percentage'] }}%</strong></td><td style="white-space:nowrap"><a href="{{ route($studentRoute,array_merge(['student'=>$student],request()->only(['from','to','subject_id','status']))) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a> <a href="{{ route($studentPdfRoute,array_merge(['student'=>$student],request()->only(['from','to','subject_id','status']))) }}" class="btn btn-outline btn-sm" title="PDF"><i class="fas fa-file-pdf"></i></a></td></tr>@empty<tr><td colspan="11" style="text-align:center;padding:30px;color:var(--text-muted)">No attendance found for the selected filters.</td></tr>@endforelse
</tbody></table></div><div style="padding:15px 18px">{{ $perStudent->links() }}</div></div>
@endif
@endsection
