@extends('layouts.app')
@section('title', $examName . ' — ' . ($schoolClass->display_name ?? $schoolClass->name))
@section('page_title', 'Results & Marksheets')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-award" style="color:var(--primary)"></i> {{ $examName }}</div>
    <div class="page-subtitle">{{ $schoolClass->display_name ?? $schoolClass->name }} &middot; {{ $examinations->count() }} subject{{ $examinations->count() === 1 ? '' : 's' }}</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('admin.results.class.download', ['batchId' => $batchId, 'schoolClass' => $schoolClass->id]) }}" target="_blank" class="btn btn-outline">
      <i class="fas fa-file-arrow-down"></i> Download All (Bulk)
    </a>
    <a href="{{ route('admin.results.index') }}" class="btn btn-outline">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>
</div>

{{-- Completion + Publish --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:14px">
      <div style="flex:1;min-width:220px">
        <div class="progress" style="margin-bottom:5px">
          <div class="progress-bar {{ $completion['percent'] >= 100 ? 'success' : ($completion['percent'] > 0 ? 'warning' : 'primary') }}" style="width:{{ $completion['percent'] }}%"></div>
        </div>
        <div style="font-size:12px;color:var(--text-muted)">
          {{ $completion['filled_slots'] }} / {{ $completion['expected_slots'] }} marks entered across all subjects ({{ $completion['percent'] }}%)
        </div>
      </div>
      <div>
        @if($publication && $publication->isPublished())
          <span class="badge success" style="font-size:12.5px;padding:6px 14px"><i class="fas fa-circle-check"></i> Published {{ $publication->published_at->format('d M Y, g:i A') }}</span>
        @else
          <span class="badge gray" style="font-size:12.5px;padding:6px 14px">Not Published</span>
        @endif
      </div>
    </div>

    @if(!$completion['ready'])
      <div style="font-size:12.5px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:10px 14px;margin-bottom:14px">
        <i class="fas fa-triangle-exclamation"></i> Not every student has marks in every subject yet. You can still publish, but students with missing marks will show as "Incomplete" on their marksheet.
      </div>
    @endif

    <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
      <form action="{{ route('admin.results.publish', ['batchId' => $batchId, 'schoolClass' => $schoolClass->id]) }}" method="POST" style="flex:1;min-width:280px" onsubmit="return confirm('Publish this result? Students and parents will immediately be able to view and download their marksheet.')">
        @csrf
        <label class="form-label">Note for the marksheet (optional) — e.g. grading policy, promotion status</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Students scoring below 50% overall are required to reappear...">{{ old('notes', $publication->notes ?? '') }}</textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:10px">
          <i class="fas fa-bullhorn"></i> {{ $publication && $publication->isPublished() ? 'Republish (update note)' : 'Publish Result' }}
        </button>
      </form>

      @if($publication && $publication->isPublished())
      <form action="{{ route('admin.results.unpublish', ['batchId' => $batchId, 'schoolClass' => $schoolClass->id]) }}" method="POST" onsubmit="return confirm('Unpublish this result? It will be hidden from students and parents until republished.')">
        @csrf
        <button type="submit" class="btn btn-outline" style="color:var(--danger)">
          <i class="fas fa-eye-slash"></i> Unpublish
        </button>
      </form>
      @endif
    </div>
  </div>
</div>

{{-- Subject overview --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title" style="font-size:14px">Subjects in this exam</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Subject</th><th>Teacher</th><th>Total Marks</th><th>Pass Marks</th><th>Marked</th></tr></thead>
      <tbody>
        @foreach($examinations as $exam)
        <tr>
          <td>{{ $exam->subject->name ?? $exam->name }}</td>
          <td>{{ $exam->teacher->name ?? '—' }}</td>
          <td>{{ $exam->total_marks }}</td>
          <td>{{ $exam->passing_marks }}</td>
          <td>{{ $exam->results_count ?? $exam->results()->count() }} / {{ $completion['students_total'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- Student roster --}}
<p style="font-size:12px;color:var(--text-muted)">Student Status is current enrollment status. Existing results for students who are no longer active remain visible; class positions apply to the active roster only.</p>
<div class="card">
  <div class="card-header"><div class="card-title" style="font-size:14px">Student Results</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Obtained / Total</th><th>%</th><th>Grade</th><th>Result</th><th>Position</th><th></th></tr></thead>
      <tbody>
        @forelse($sheets as $sheet)
        @php
          $statusColor = match($sheet['overall_status']) { 'Pass' => 'success', 'Fail' => 'danger', default => 'warning' };
        @endphp
        <tr>
          <td>
            <div style="font-weight:600">{{ $sheet['student']->name }}<br>@include('partials.report-student-status', ['statusStudent' => $sheet['student']])</div>
            <div style="font-size:11px;color:var(--text-muted)">{{ $sheet['student']->student_id }}</div>
          </td>
          <td>{{ $schoolClass->display_name ?? $schoolClass->name }}</td>
          <td>{{ rtrim(rtrim(number_format($sheet['total_obtained'],2),'0'),'.') }} / {{ rtrim(rtrim(number_format($sheet['total_max'],2),'0'),'.') }}</td>
          <td>{{ $sheet['percentage'] }}%</td>
          <td>{{ $sheet['overall_grade'] }}</td>
          <td><span class="badge {{ $statusColor }}">{{ $sheet['overall_status'] }}</span></td>
          <td>{{ $sheet['position'] ? '#' . $sheet['position'] : '—' }}</td>
          <td style="white-space:nowrap">
            <a href="{{ route('admin.results.student', ['batchId' => $batchId, 'schoolClass' => $schoolClass->id, 'student' => $sheet['student']->id]) }}" target="_blank" class="btn btn-outline btn-sm" title="View / Download Marksheet">
              <i class="fas fa-file-arrow-down"></i>
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-user-graduate"></i><p>No active students or historical results found in this class.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
