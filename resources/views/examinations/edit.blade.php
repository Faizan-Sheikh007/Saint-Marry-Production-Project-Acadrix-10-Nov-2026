@extends('layouts.app')
@section('title', 'Edit Examination')
@section('page_title', 'Edit Examination')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">Edit Examination</div>
    <div class="page-subtitle">{{ $examination->schoolClass->display_name ?? $examination->schoolClass->name ?? '—' }} &middot; {{ $examination->subject->name ?? 'All Subjects' }}</div>
  </div>
  <a href="{{ route('admin.examinations.show', $examination) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@if($errors->any())
  <div class="alert alert-danger">
    <i class="fas fa-triangle-exclamation"></i>
    <div>@foreach($errors->all() as $msg)<div>{{ $msg }}</div>@endforeach</div>
  </div>
@endif

@if($examination->isLocked())
  <div class="alert alert-warning"><i class="fas fa-lock"></i> This examination's results are locked. You can still edit its schedule below.</div>
@endif

<div class="card">
  <div class="card-header"><div class="card-title">Schedule Details</div></div>
  <form action="{{ route('admin.examinations.update', $examination) }}" method="POST">
    @csrf @method('PUT')
    <div class="card-body">
      <div class="grid grid-2" style="gap:14px">
        <div class="form-group"><label class="form-label">Academic Session *</label>
          <select name="academic_year_id" class="form-control" required><option value="">Select session</option>
          @foreach($academicYears as $year)<option value="{{ $year->id }}" {{ old('academic_year_id', $examination->academic_year_id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Exam Name *</label>
          <input type="text" name="name" class="form-control" required value="{{ old('name', $examination->name) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Type *</label>
          <select name="type" class="form-control" required>
            @foreach(['midterm' => 'Mid Term', 'final' => 'Final', 'quiz' => 'Quiz', 'unit_test' => 'Unit Test'] as $k => $v)
              <option value="{{ $k }}" {{ old('type', $examination->type) === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Teacher</label>
          <select name="teacher_id" class="form-control">
            <option value="">Unassigned</option>
            @foreach($teachers as $t)
              <option value="{{ $t->id }}" {{ old('teacher_id', $examination->teacher_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status *</label>
          <select name="status" class="form-control" required>
            <option value="scheduled" {{ old('status', $examination->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="completed" {{ old('status', $examination->status) === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ old('status', $examination->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Exam Date *</label>
          <input type="date" name="exam_date" class="form-control" required value="{{ old('exam_date', $examination->exam_date?->format('Y-m-d')) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Room</label>
          <input type="text" name="room" class="form-control" value="{{ old('room', $examination->room) }}" placeholder="e.g. Hall A">
        </div>
        <div class="form-group">
          <label class="form-label">Start Time *</label>
          <input type="time" name="start_time" class="form-control" required value="{{ old('start_time', $examination->start_time ? \Carbon\Carbon::parse($examination->start_time)->format('H:i') : '') }}">
        </div>
        <div class="form-group">
          <label class="form-label">End Time *</label>
          <input type="time" name="end_time" class="form-control" required value="{{ old('end_time', $examination->end_time ? \Carbon\Carbon::parse($examination->end_time)->format('H:i') : '') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Total Marks *</label>
          <input type="number" name="total_marks" class="form-control" required min="1" value="{{ old('total_marks', $examination->total_marks) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Passing Marks *</label>
          <input type="number" name="passing_marks" class="form-control" required min="1" value="{{ old('passing_marks', $examination->passing_marks) }}">
        </div>
      </div>
      <p style="font-size:11.5px;color:var(--text-muted);margin-top:4px"><i class="fas fa-circle-info"></i> Class and subject can't be changed here — delete and reschedule if you need a different class/subject pairing.</p>
    </div>
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
      <a href="{{ route('admin.examinations.show', $examination) }}" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </form>
</div>

@endsection
