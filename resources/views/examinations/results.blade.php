@extends('layouts.app')
@section('title', 'Examination Results')
@section('page_title', 'Examination Results')
@section('content')

@php
  $isAdmin = auth()->user()->isAdmin();
  $indexRoute = $isAdmin ? 'admin.examinations.index' : 'teacher.examinations.index';
  $showRoute  = $isAdmin ? 'admin.examinations.show'  : 'teacher.examinations.show';
  $resultsByStudent = $results->keyBy('student_id');
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $examination->name }} — Results</div>
    <div class="page-subtitle">
      {{ $examination->schoolClass->display_name ?? $examination->schoolClass->name ?? '—' }} &middot;
      {{ $examination->subject->name ?? 'All Subjects' }} &middot;
      Total: {{ $examination->total_marks }} &middot; Pass: {{ $examination->passing_marks }}
    </div>
  </div>
  <a href="{{ route($showRoute, $examination) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>@endif

@if($isAdmin)
  <div class="alert alert-info">
    <i class="fas fa-eye"></i>
    You're viewing marks as Admin — read only. Only <strong>{{ $examination->teacher->name ?? 'the assigned teacher' }}</strong> can enter or edit these marks
    @if(!$examination->isLocked())
      , or you can <form action="{{ route('admin.examinations.lock', $examination) }}" method="POST" style="display:inline" onsubmit="return confirm('Lock this exam so marks can no longer be edited?')">@csrf<button type="submit" style="background:none;border:none;padding:0;color:var(--primary);font-weight:700;text-decoration:underline;cursor:pointer">lock the results</button></form> to finalize them.
    @else
      . Results are locked.
    @endif
  </div>
@elseif(!$canEdit)
  <div class="alert alert-warning">
    <i class="fas fa-lock"></i>
    {{ $examination->isLocked() ? 'This examination has been locked by the admin — marks can no longer be edited.' : 'You are not the assigned teacher for this examination, so you can only view these marks.' }}
  </div>
@endif

<div class="card">
  <div class="card-header">
    <div class="card-title">Student Marks</div>
    <span class="tag">{{ $students->count() }} students</span>
  </div>

  <form action="{{ route($canEdit ? 'teacher.examinations.results.store' : $showRoute, $examination) }}" method="POST">
    @csrf
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Roll #</th>
            <th>Student</th>
            <th>Marks Obtained</th>
            <th>Percentage</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody>
          @forelse($students as $student)
          @php $result = $resultsByStudent->get($student->id); @endphp
          <tr>
            <td>{{ $student->roll_number ?? '—' }}</td>
            <td>{{ $student->name }}<br>@include('partials.report-student-status', ['statusStudent' => $student])</td>
            <td>
              @if($canEdit)
                <input type="number" name="marks[{{ $student->id }}]" class="form-control" style="width:100px" min="0" max="{{ $examination->total_marks }}" step="0.5" value="{{ old('marks.'.$student->id, $result->marks_obtained ?? '') }}">
              @else
                <strong>{{ $result->marks_obtained ?? '—' }}</strong> <span style="color:var(--text-muted)">/ {{ $examination->total_marks }}</span>
              @endif
            </td>
            <td>{{ $result ? $result->percentage.'%' : '—' }}</td>
            <td>
              @if($result)
                @php
                  $gradeColor = match($result->grade) { 'A+','A' => 'success', 'B','C' => 'info', 'D' => 'warning', default => 'danger' };
                @endphp
                <span class="badge {{ $gradeColor }}">{{ $result->grade }}</span>
              @else
                <span style="color:var(--text-muted)">—</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="5"><div class="empty-state"><i class="fas fa-user-graduate"></i><p>No active students found in this class.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($canEdit)
      <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)">
          <input type="checkbox" name="mark_completed" value="1" {{ $examination->status === 'completed' ? 'checked' : '' }} style="accent-color:var(--primary)">
          Mark this examination as completed
        </label>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Marks</button>
      </div>
    @endif
  </form>
</div>

@endsection
