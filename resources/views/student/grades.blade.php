@extends('layouts.app')
@section('title', 'My Grades')
@section('page_title', 'My Grades')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">My Grades</div>
    <div class="page-subtitle">Assignment marks, quiz results, and your overall progress in every course.</div>
  </div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Overall Marks by Course</div></div>
  @if($enrollments->isEmpty())
    <div class="empty-state"><i class="fas fa-laptop-code"></i><p>You're not enrolled in any courses yet.</p></div>
  @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>Course</th><th>Overall %</th></tr></thead>
        <tbody>
          @foreach($enrollments as $enrollment)
          @php $pct = $overall[$enrollment->course_id] ?? null; @endphp
          <tr>
            <td>{{ $enrollment->course->title ?? '—' }}</td>
            <td>
              @if($pct === null)
                <span style="color:var(--text-muted)">No graded work yet</span>
              @else
                <div style="display:flex;align-items:center;gap:10px;max-width:260px">
                  <div class="progress" style="flex:1"><div class="progress-bar {{ $pct >= 50 ? 'success' : 'danger' }}" style="width:{{ $pct }}%"></div></div>
                  <span style="font-weight:700;font-size:12.5px">{{ $pct }}%</span>
                </div>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="grid grid-2" style="gap:20px;align-items:start">
  <div class="card" style="margin-bottom:0">
    <div class="card-header"><div class="card-title">Assignment Grades</div></div>
    @if($assignmentGrades->isEmpty())
      <div class="empty-state"><i class="fas fa-file-lines"></i><p>No graded assignments yet.</p></div>
    @else
      @foreach($assignmentGrades as $sub)
      <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;gap:10px">
          <div>
            <div style="font-weight:700;font-size:13px">{{ $sub->assignment->title ?? '—' }}</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">{{ $sub->assignment->course->title ?? '—' }}</div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:800;font-size:14px;color:var(--primary)">{{ $sub->marks_obtained }}/{{ $sub->assignment->total_marks ?? '—' }}</div>
          </div>
        </div>
        @if($sub->feedback)<div style="font-size:12px;color:var(--text-muted);margin-top:6px"><i class="fas fa-comment-dots"></i> {{ $sub->feedback }}</div>@endif
      </div>
      @endforeach
    @endif
  </div>

  <div class="card" style="margin-bottom:0">
    <div class="card-header"><div class="card-title">Quiz Results</div></div>
    @if($quizResults->isEmpty())
      <div class="empty-state"><i class="fas fa-circle-question"></i><p>No quiz results yet.</p></div>
    @else
      @foreach($quizResults as $attempt)
      <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;gap:10px">
          <div>
            <div style="font-weight:700;font-size:13px">{{ $attempt->quiz->title ?? '—' }}</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">{{ $attempt->quiz->course->title ?? '—' }}</div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:800;font-size:14px;color:var(--primary)">{{ $attempt->score }}/{{ $attempt->total_marks }}</div>
            <div style="font-size:11px;color:var(--text-muted)">{{ $attempt->percentage }}%</div>
          </div>
        </div>
      </div>
      @endforeach
    @endif
  </div>
</div>

@endsection
