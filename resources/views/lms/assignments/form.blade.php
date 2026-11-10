@extends('layouts.app')
@section('title', isset($assignment) ? 'Edit Assignment' : 'New Assignment')
@section('page_title', isset($assignment) ? 'Edit Assignment' : 'New Assignment')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ isset($assignment) ? 'Edit Assignment' : 'New Assignment' }}</div>
    <div class="page-subtitle">{{ $course->title }}</div>
  </div>
  <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
</div>

@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="card" style="max-width:720px">
  <div class="card-body">
    <form method="POST" action="{{ isset($assignment) ? route('lms.assignments.update', [$course, $assignment]) : route('lms.assignments.store', $course) }}" enctype="multipart/form-data">
      @csrf
      @if(isset($assignment)) @method('PUT') @endif

      <div style="margin-bottom:16px">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title', $assignment->title ?? '') }}">
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $assignment->description ?? '') }}</textarea>
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Instructions</label>
        <textarea name="instructions" class="form-control" rows="4" placeholder="What should students submit?">{{ old('instructions', $assignment->instructions ?? '') }}</textarea>
      </div>

      <div class="grid grid-2" style="margin-bottom:16px">
        <div>
          <label class="form-label">Total Marks</label>
          <input type="number" name="total_marks" class="form-control" min="1" required value="{{ old('total_marks', $assignment->total_marks ?? 100) }}">
        </div>
        <div>
          <label class="form-label">Due Date</label>
          <input type="datetime-local" name="due_date" class="form-control" value="{{ old('due_date', isset($assignment) && $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '') }}">
        </div>
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Attachment (optional)</label>
        @if(isset($assignment) && $assignment->file_attachment)
          <div style="margin-bottom:8px;font-size:12.5px">
            <a href="{{ storage_asset($assignment->file_attachment) }}" target="_blank"><i class="fas fa-paperclip"></i> Current file</a> — uploading a new one will replace it.
          </div>
        @endif
        <input type="file" name="attachment" class="form-control">
      </div>

      <div style="margin-bottom:22px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="active" {{ old('status', $assignment->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="closed" {{ old('status', $assignment->status ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
      </div>

      @include('lms.partials.task-audience', ['task' => $assignment ?? null])
      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> {{ isset($assignment) ? 'Save Changes' : 'Add Assignment' }}</button>
    </form>
  </div>
</div>

@endsection
