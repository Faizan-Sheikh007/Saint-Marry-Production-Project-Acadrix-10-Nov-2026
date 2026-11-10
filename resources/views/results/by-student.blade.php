@extends('layouts.app')
@section('title', 'Find Result by Student')
@section('page_title', 'Results & Marksheets')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-user-magnifying-glass" style="color:var(--primary)"></i> Find Result by Student</div>
    <div class="page-subtitle">Search a student to see every exam's marksheet for their class.</div>
  </div>
  <a href="{{ route('admin.results.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div style="flex:1;min-width:240px">
        <label class="form-label">Student name or ID</label>
        <input type="text" name="search" class="form-control" placeholder="e.g. Ayesha Khan or STU-2201" value="{{ request('search') }}" autofocus>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
    </form>
  </div>
</div>

@if($students->count() > 1 && !$student)
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title" style="font-size:14px">Multiple matches — pick one</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Student ID</th><th>Class</th><th></th></tr></thead>
      <tbody>
        @foreach($students as $s)
        <tr>
          <td>{{ $s->name }}<br>@include('partials.report-student-status', ['statusStudent' => $s])</td>
          <td>{{ $s->student_id }}</td>
          <td>{{ $s->schoolClass->display_name ?? $s->schoolClass->name ?? '—' }}</td>
          <td><a href="{{ route('admin.results.by-student', ['student_id' => $s->id]) }}" class="btn btn-outline btn-sm">Select</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@if($student)
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div style="display:flex;align-items:center;gap:12px">
      @if($student->photo)
        <img src="{{ storage_asset($student->photo) }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover">
      @else
        <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($student->name,0,2)) }}</div>
      @endif
      <div>
        <div class="card-title">{{ $student->name }}<br>@include('partials.report-student-status', ['statusStudent' => $student])</div>
        <div style="font-size:12px;color:var(--text-muted)">{{ $student->student_id }} &middot; {{ $student->schoolClass->display_name ?? $student->schoolClass->name ?? '—' }}</div>
      </div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Exam</th><th>Obtained / Total</th><th>%</th><th>Grade</th><th>Result</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse($batches as $b)
        @php
          $sheet = $b['sheet'];
          $statusColor = match($sheet['overall_status']) { 'Pass' => 'success', 'Fail' => 'danger', default => 'warning' };
        @endphp
        <tr>
          <td style="font-weight:600">{{ $b['exam_name'] }}</td>
          <td>{{ rtrim(rtrim(number_format($sheet['total_obtained'],2),'0'),'.') }} / {{ rtrim(rtrim(number_format($sheet['total_max'],2),'0'),'.') }}</td>
          <td>{{ $sheet['percentage'] }}%</td>
          <td>{{ $sheet['overall_grade'] }}</td>
          <td><span class="badge {{ $statusColor }}">{{ $sheet['overall_status'] }}</span></td>
          <td>
            @if($b['published']) <span class="badge success">Published</span>
            @else <span class="badge gray">Not Published</span>
            @endif
          </td>
          <td>
            <a href="{{ route('admin.results.student', ['batchId' => $b['batch_id'], 'schoolClass' => $student->school_class_id, 'student' => $student->id]) }}" target="_blank" class="btn btn-outline btn-sm" title="View / Download">
              <i class="fas fa-file-arrow-down"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-award"></i><p>No exam results found for this student's class yet.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@elseif(request('search') && $students->isEmpty())
<div class="card"><div class="empty-state"><i class="fas fa-user-graduate"></i><p>No student found matching "{{ request('search') }}".</p></div></div>
@endif

@endsection
