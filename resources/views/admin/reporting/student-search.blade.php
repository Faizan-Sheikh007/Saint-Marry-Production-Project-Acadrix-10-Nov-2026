@extends('layouts.app')
@section('title', 'Student Information Search')
@section('page_title', 'Student Information Search')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $reportsHome = $accountsReport ? 'reports.index' : 'admin.reports.index';
  $searchRoute = $accountsReport ? 'accounts.reporting.student-search' : 'admin.reporting.student-search';
  $studentProfileRoute = $accountsReport ? 'accounts.students.show' : 'students.show';
  $studentPrintRoute = $accountsReport ? 'accounts.reporting.student-profile.print' : 'admin.reporting.student-profile.print';
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="{{ route($reportsHome) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Reporting Center</a>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Search Students</div></div>
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="{{ route($searchRoute) }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Cell #</label>
        <input type="text" name="phone" class="form-control" placeholder="e.g. 0300-1234567" value="{{ request('phone') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Student Name / ID</label>
        <input type="text" name="name" class="form-control" placeholder="Name or Student ID" value="{{ request('name') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Father's Name</label>
        <input type="text" name="father_name" class="form-control" placeholder="Father's Name" value="{{ request('father_name') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" placeholder="Area / City / Street" value="{{ request('address') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-control">
          <option value="all">Any Program</option>
          @foreach($programs as $p)
          <option value="{{ $p->id }}" {{ (string) request('program_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <a href="{{ route($searchRoute) }}" class="btn btn-outline" data-ajax-filter-link>Clear</a>
    </form>
  </div>
</div>

@if(request()->hasAny(['phone','name','father_name','address','program_id']))
<div class="card">
  <div class="card-header">
    <div class="card-title">Results ({{ $students->total() }} found)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student ID</th><th>Name</th><th>Father's Name</th><th>Cell #</th><th>Address</th><th>Program</th><th>Semester/Part</th><th>Academic Year</th><th></th></tr></thead>
      <tbody>
        @forelse($students as $s)
        <tr>
          <td>{{ $s->student_id ?: '—' }}</td>
          <td>{{ $s->name }}<br>@include('partials.report-student-status', ['statusStudent' => $s]) @include('partials.student-status-badge', ['statusStudent' => $s])</td>
          <td>{{ $s->father_name ?: '—' }}</td>
          <td>{{ $s->phone ?: '—' }}</td>
          <td>{{ \Illuminate\Support\Str::limit($s->address ?: '—', 40) }}</td>
          <td>{{ $s->program->name ?? '—' }}</td>
          <td>{{ $s->schoolClass->name ?? '—' }}</td>
          <td>{{ $s->academicYear->name ?? '—' }}</td>
          <td style="white-space:nowrap">
            <a href="{{ route($studentProfileRoute, $s->id) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
            <a href="{{ route($studentPrintRoute, $s->id) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print Sheet</a>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">No students matched your search.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($students->hasPages())
  <div style="padding:14px 20px">{{ $students->links() }}</div>
  @endif
</div>
@else
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted)">
  <i class="fas fa-magnifying-glass" style="font-size:26px;margin-bottom:10px;display:block"></i>
  Enter at least one search field above to find a student and print their information sheet.
</div></div>
@endif
@endsection
