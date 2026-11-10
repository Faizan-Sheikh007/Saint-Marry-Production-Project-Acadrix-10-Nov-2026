@extends('layouts.app')
@section('title', $assignment->title)
@section('page_title', $assignment->title)
@section('content')

@php
  $isTeacher = auth()->user()->isTeacher();
  $isAdmin   = auth()->user()->isAdmin();
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $assignment->title }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      <span class="crumb subject"><i class="fas fa-book"></i> {{ $course->title }}</span>
      <span class="badge {{ $assignment->status === 'active' ? 'info' : 'gray' }}">{{ ucfirst($assignment->status) }}</span>
      @if($isAdmin)<span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>@endif
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
    @if($isTeacher)
      <a href="{{ route('lms.assignments.edit', [$course, $assignment]) }}" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
      <form action="{{ route('lms.assignments.destroy', [$course, $assignment]) }}" method="POST" onsubmit="return confirm('Delete this assignment?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i> Delete</button>
      </form>
    @endif
  </div>
</div>

<div class="grid grid-2" style="gap:20px;align-items:start;margin-bottom:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">Details</div></div>
    <div class="card-body" style="font-size:13.5px;line-height:1.6">
      @if($assignment->description)<p style="color:var(--text-muted);margin-bottom:12px">{{ $assignment->description }}</p>@endif
      @if($assignment->instructions)
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:4px">Instructions</div>
        <p>{{ $assignment->instructions }}</p>
      @endif
      @if($assignment->file_attachment)
        <a href="{{ storage_asset($assignment->file_attachment) }}" target="_blank" class="btn btn-outline btn-sm" style="margin-top:10px">
          <i class="fas fa-paperclip"></i> Download attachment
        </a>
      @endif
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Summary</div></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 20px">
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase">Total Marks</div>
          <div style="font-weight:700;margin-top:2px">{{ $assignment->total_marks }}</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase">Due Date</div>
          <div style="font-weight:700;margin-top:2px">{{ $assignment->due_date?->format('d M Y, h:i A') ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

@if($student)
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Your Submission</div></div>
  <div class="card-body">
    @if($submission)
      <div style="margin-bottom:12px">
        <span class="badge {{ $submission->status === 'graded' ? 'success' : ($submission->status === 'late' ? 'warning' : 'info') }}">{{ ucfirst($submission->status) }}</span>
        <span style="font-size:11.5px;color:var(--text-muted);margin-left:6px">Submitted {{ $submission->submitted_at?->diffForHumans() }}</span>
      </div>
      <div style="font-size:13.5px;line-height:1.6;background:var(--bg);border-radius:8px;padding:14px;margin-bottom:14px">{{ $submission->content }}</div>
      @if($submission->file_path)
        <a href="{{ storage_asset($submission->file_path) }}" target="_blank" class="btn btn-outline btn-sm" style="margin-bottom:14px"><i class="fas fa-file"></i> View your uploaded file</a>
      @endif
      @if($submission->status === 'graded')
        <div class="alert alert-success" style="margin-bottom:0">
          <i class="fas fa-star"></i> Graded: {{ $submission->marks_obtained }} / {{ $assignment->total_marks }}
          @if($submission->feedback) &middot; {{ $submission->feedback }} @endif
        </div>
      @endif
    @elseif($assignment->status !== 'active')
      <p>This assignment is closed. New submissions are not accepted.</p>
    @else
      <form method="POST" action="{{ route('lms.assignments.submit', [$course, $assignment]) }}" enctype="multipart/form-data">
        @csrf
        <textarea name="content" class="form-control" rows="5" placeholder="Write or paste your submission here…"></textarea>
        <div style="margin-top:10px">
          <label class="form-label">Attach a file (optional)</label>
          <input type="file" name="file" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-paper-plane"></i> Submit</button>
      </form>
    @endif
  </div>
</div>
@endif

@if($isTeacher || $isAdmin)
<div class="card">
  <div class="card-header">
    <div class="card-title">Submissions</div>
    <span class="tag">{{ $submissions->count() }}</span>
  </div>
  @if($submissions->isEmpty())
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet.</p></div>
  @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Status</th><th>Submitted</th><th>File</th><th>Marks</th>@if($isTeacher)<th>Actions</th>@endif</tr></thead>
        <tbody>
          @foreach($submissions as $sub)
          <tr>
            <td>{{ $sub->student->name ?? '—' }}</td>
            <td><span class="badge {{ $sub->status === 'graded' ? 'success' : ($sub->status === 'late' ? 'warning' : 'info') }}">{{ ucfirst($sub->status) }}</span></td>
            <td style="font-size:12.5px">{{ $sub->submitted_at?->format('d M Y, h:i A') ?? '—' }}</td>
            <td>@if($sub->file_path)<a href="{{ storage_asset($sub->file_path) }}" target="_blank"><i class="fas fa-file"></i></a>@else —@endif</td>
            <td>{{ $sub->marks_obtained !== null ? $sub->marks_obtained . '/' . $assignment->total_marks : '—' }}</td>
            @if($isTeacher)
            <td>
              <button class="btn btn-outline btn-sm" onclick="document.getElementById('grade-{{ $sub->id }}').style.display='flex'"><i class="fas fa-star"></i> Grade</button>
            </td>
            @endif
          </tr>
          @if($isTeacher)
          <tr id="grade-{{ $sub->id }}" style="display:none">
            <td colspan="6" style="background:var(--bg)">
              <form method="POST" action="{{ route('lms.assignments.grade', [$course, $assignment, $sub]) }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 0">
                @csrf
                <div style="max-width:120px">
                  <input type="number" name="marks_obtained" class="form-control" min="0" max="{{ $assignment->total_marks }}" value="{{ $sub->marks_obtained }}" placeholder="Marks" required>
                </div>
                <div style="flex:1;min-width:200px">
                  <input type="text" name="feedback" class="form-control" value="{{ $sub->feedback }}" placeholder="Feedback (optional)">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save</button>
              </form>
            </td>
          </tr>
          @endif
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endif

@endsection
