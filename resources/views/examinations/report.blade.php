@extends('layouts.app')
@section('title', 'Examination Reports')
@section('page_title', 'Examination Reports')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-chart-column" style="color:var(--primary)"></i> Examination Reports</div>
    <div class="page-subtitle">Filter results by program, class, subject, teacher, or specific exam.</div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="{{ route('admin.examinations.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="{{ route('admin.examinations.report.export', request()->query()) }}" class="btn btn-primary"><i class="fas fa-file-csv"></i> Export CSV</a>
  </div>
</div>

<div class="grid grid-4" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-list"></i></div>
    <div><div class="stat-value">{{ $summary['entries'] }}</div><div class="stat-label">Result Entries</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-percent"></i></div>
    <div><div class="stat-value">{{ $summary['avg'] }}%</div><div class="stat-label">Average Score</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div><div class="stat-value">{{ $summary['passed'] }}</div><div class="stat-label">Passed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-circle-xmark"></i></div>
    <div><div class="stat-value">{{ max($summary['entries'] - $summary['passed'], 0) }}</div><div class="stat-label">Failed</div></div>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div style="min-width:150px">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-control">
          <option value="">All Programs</option>
          @foreach($programs as $p)<option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div style="min-width:150px">
        <label class="form-label">Class</label>
        <select name="school_class_id" class="form-control">
          <option value="">All Classes</option>
          @foreach($classes as $c)<option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name ?? $c->name }}</option>@endforeach
        </select>
      </div>
      <div style="min-width:150px">
        <label class="form-label">Subject</label>
        <select name="subject_id" class="form-control">
          <option value="">All Subjects</option>
          @foreach($subjects as $s)<option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
        </select>
      </div>
      <div style="min-width:150px">
        <label class="form-label">Teacher</label>
        <select name="teacher_id" class="form-control">
          <option value="">All Teachers</option>
          @foreach($teachers as $t)<option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach
        </select>
      </div>
      <div style="min-width:180px">
        <label class="form-label">Examination</label>
        <select name="examination_id" class="form-control">
          <option value="">All Exams</option>
          @foreach($examinations as $e)<option value="{{ $e->id }}" {{ request('examination_id') == $e->id ? 'selected' : '' }}>{{ $e->name }} ({{ $e->exam_date?->format('d M Y') }})</option>@endforeach
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.examinations.report') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th><th>Class</th><th>Program</th><th>Subject</th><th>Examination</th><th>Teacher</th><th>Marks</th><th>%</th><th>Grade</th>
        </tr>
      </thead>
      <tbody>
        @forelse($results as $r)
        <tr>
          <td>{{ $r->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r->student])</td>
          <td>{{ $r->student->schoolClass->display_name ?? $r->student->schoolClass->name ?? '—' }}</td>
          <td>{{ $r->examination->program->name ?? '—' }}</td>
          <td>{{ $r->examination->subject->name ?? '—' }}</td>
          <td>{{ $r->examination->name ?? '—' }}</td>
          <td>{{ $r->examination->teacher->name ?? '—' }}</td>
          <td>{{ $r->marks_obtained }} / {{ $r->examination->total_marks ?? '—' }}</td>
          <td>{{ $r->percentage }}%</td>
          <td>
            @php $gradeColor = match($r->grade) { 'A+','A' => 'success', 'B','C' => 'info', 'D' => 'warning', default => 'danger' }; @endphp
            <span class="badge {{ $gradeColor }}">{{ $r->grade }}</span>
          </td>
        </tr>
        @empty
        <tr><td colspan="9"><div class="empty-state"><i class="fas fa-chart-column"></i><p>No results match these filters yet.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="padding:4px 0">{{ $results->links() }}</div>

@endsection
