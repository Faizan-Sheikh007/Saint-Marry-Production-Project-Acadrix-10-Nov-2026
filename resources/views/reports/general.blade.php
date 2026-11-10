@extends('layouts.app')
@section('title', 'General Student Report')
@section('page_title', 'General Student Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $prefix = $accountsReport ? 'accounts' : 'admin';
  $homeRoute = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $reportRoute = $prefix . '.reports.general';
  $exportRoute = $prefix . '.reports.general.export';
@endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="{{ route($homeRoute) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div>
      <div style="font-size:22px;font-weight:800">General Student Report</div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Filter student records by program, semester and academic session; choose the fields to include in your report.</div>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <button type="button" class="btn btn-outline" onclick="downloadGeneralReport('pdf')"><i class="fas fa-file-pdf"></i> Print / PDF</button>
    <button type="button" class="btn btn-outline" onclick="downloadGeneralReport('csv')"><i class="fas fa-file-csv"></i> CSV</button>
    <button type="button" class="btn btn-outline" onclick="downloadGeneralReport('xlsx')"><i class="fas fa-file-excel"></i> Excel</button>
  </div>
</div>

<form method="GET" action="{{ route($reportRoute) }}" id="generalReportForm">
  <div class="card" style="margin-bottom:18px">
    <div class="card-body" style="padding:16px 20px">
      <div class="general-report-filters" style="display:grid;grid-template-columns:repeat(4,minmax(150px,1fr)) auto auto;gap:10px;align-items:end">
        <div class="form-group" style="margin:0"><label class="form-label" for="general-program">Program</label><select id="general-program" class="form-control" name="program_id"><option value="">All Programs</option>@foreach($programs as $p)<option value="{{ $p->id }}" @selected(request('program_id') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <div class="form-group" style="margin:0"><label class="form-label" for="general-semester">Semester / Part</label><select id="general-semester" class="form-control" name="semester_id"><option value="">All Semesters</option>@foreach($semesters as $s)<option value="{{ $s->id }}" @selected(request('semester_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="form-group" style="margin:0"><label class="form-label" for="general-session">Academic Session</label><select id="general-session" class="form-control" name="academic_year_id"><option value="">All Sessions</option>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(request('academic_year_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="form-group" style="margin:0"><label class="form-label" for="general-status">Student Status</label><select id="general-status" class="form-control" name="student_status"><option value="">All Student Statuses</option>@foreach(\App\Support\StudentStatus::VALUES as $statusValue)<option value="{{ $statusValue }}" @selected(request('student_status') === $statusValue)>{{ \App\Support\StudentStatus::label($statusValue) }}</option>@endforeach</select></div>
        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route($reportRoute) }}" class="btn btn-outline"><i class="fas fa-times"></i> Reset</a>
      </div>
      <div style="border-top:1px solid var(--border);margin-top:16px;padding-top:14px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px">
          <div><div style="font-size:13px;font-weight:700"><i class="fas fa-table-columns" style="color:var(--primary)"></i> Report Columns</div><div style="font-size:11px;color:var(--text-muted);margin-top:3px">Select the columns to show in the preview and downloaded files. Student Status is always included.</div></div>
          <div style="display:flex;gap:8px"><button type="button" class="btn btn-outline btn-sm" onclick="setGeneralColumns(true)">Select All</button><button type="button" class="btn btn-outline btn-sm" onclick="setGeneralColumns(false)">Default Columns</button></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px 16px">
          @foreach($columns as $key => $label)
            <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $selected)) @disabled($key === 'status')> {{ $label }}@if($key === 'status')<input type="hidden" name="columns[]" value="status">@endif</label>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</form>
<style>
@media(max-width:1050px){.general-report-filters{grid-template-columns:repeat(2,minmax(160px,1fr))!important}}
@media(max-width:600px){.general-report-filters{grid-template-columns:1fr!important}}
</style>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:18px">
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:11px;background:rgba(139,12,34,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-users"></i></div><div><div style="font-size:22px;font-weight:800">{{ number_format($students->total()) }}</div><div style="font-size:11px;color:var(--text-muted)">Matching Students</div></div></div>
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:11px;background:rgba(139,12,34,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-table-columns"></i></div><div><div style="font-size:22px;font-weight:800">{{ count($selected) }}</div><div style="font-size:11px;color:var(--text-muted)">Selected Columns</div></div></div>
</div>

<div class="card">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px"><strong>{{ number_format($students->total()) }} student(s) found</strong><span style="font-size:11px;color:var(--text-muted)">Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}</span></div>
  <div class="table-wrap"><table><thead><tr><th>Sr #</th>@foreach($selected as $key)<th>{{ $columns[$key] }}</th>@endforeach</tr></thead><tbody>
    @forelse($students as $student)<tr><td>{{ $students->firstItem() + $loop->index }}</td>@foreach($selected as $key)<td>@switch($key)
      @case('status') {{ $student->statusLabel() }} @break
      @case('father_name') {{ $student->father_name ?: ($student->guardian_name ?: '—') }} @break
      @case('program') {{ $student->program?->name ?? '—' }} @break
      @case('semester') {{ $student->schoolClass?->name ?? '—' }} @break
      @case('session') {{ $student->academicYear?->name ?? $student->session ?? '—' }} @break
      @default {{ $student->{$key} ?? '—' }}
    @endswitch</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($selected) + 1 }}" style="text-align:center;padding:30px;color:var(--text-muted)">No students match the selected filters.</td></tr>@endforelse
  </tbody></table></div>
  <div style="padding:15px 18px">{{ $students->links() }}</div>
</div>
<script>
function setGeneralColumns(selectAll) {
  const defaults = ['name','father_name','address','phone','status'];
  document.querySelectorAll('#generalReportForm input[name="columns[]"]').forEach(box => {
    if (box.type === 'checkbox') box.checked = selectAll || defaults.includes(box.value);
  });
}
function downloadGeneralReport(format) {
  const form = document.getElementById('generalReportForm');
  const params = new URLSearchParams(new FormData(form));
  params.set('format', format);
  const url = @json(route($exportRoute)) + '?' + params.toString();
  if (format === 'pdf') { window.open(url, '_blank', 'noopener'); }
  else { window.location.href = url; }
}
</script>
@endsection
