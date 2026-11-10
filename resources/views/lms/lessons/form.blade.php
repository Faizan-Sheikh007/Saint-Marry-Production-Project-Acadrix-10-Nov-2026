@extends('layouts.app')
@php $isVideoForm = !isset($lesson) && request('type') === 'video'; @endphp
@section('title', isset($lesson) ? 'Edit Lesson' : ($isVideoForm ? 'New Video Lecture' : 'New Lesson'))
@section('page_title', isset($lesson) ? 'Edit Lesson' : ($isVideoForm ? 'New Video Lecture' : 'New Lesson'))
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ isset($lesson) ? 'Edit Lesson' : ($isVideoForm ? 'New Video Lecture' : 'New Lesson') }}</div>
    <div class="page-subtitle">{{ $course->title }}</div>
  </div>
  <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
</div>

<div class="card" style="max-width:720px">
  <div class="card-body">
    <form method="POST" action="{{ isset($lesson) ? route('lms.lessons.update', [$course, $lesson]) : route('lms.lessons.store', $course) }}" enctype="multipart/form-data">
      @csrf
      @if(isset($lesson)) @method('PUT') @endif

      <div style="margin-bottom:16px">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title', $lesson->title ?? '') }}">
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Short Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $lesson->description ?? '') }}">
      </div>

      <div class="grid grid-2" style="margin-bottom:16px">
        <div>
          <label class="form-label">Type</label>
          <select name="type" class="form-control" id="lessonType" onchange="toggleLessonFields()">
            @foreach(['text' => 'Text', 'video' => 'Video', 'file' => 'File', 'mixed' => 'Mixed'] as $val => $label)
              <option value="{{ $val }}" {{ old('type', $lesson->type ?? request('type', 'text')) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="form-label">Duration (minutes)</label>
          <input type="number" name="duration_minutes" class="form-control" min="0" value="{{ old('duration_minutes', $lesson->duration_minutes ?? '') }}">
        </div>
      </div>

      <div style="margin-bottom:16px" id="videoUrlField">
        <label class="form-label">Video URL</label>
        <input type="url" name="video_url" class="form-control" placeholder="https://…" value="{{ old('video_url', $lesson->video_url ?? '') }}">
      </div>

      <div style="margin-bottom:16px" id="attachmentField">
        <label class="form-label">Lecture Notes / File (PDF, Doc, Presentation, etc.)</label>
        @if(isset($lesson) && $lesson->file_attachment)
          <div style="margin-bottom:8px;font-size:12.5px">
            <a href="{{ storage_asset($lesson->file_attachment) }}" target="_blank"><i class="fas fa-paperclip"></i> Current file</a> — uploading a new one will replace it.
          </div>
        @endif
        <input type="file" name="attachment" class="form-control">
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Content</label>
        <textarea name="content" class="form-control" rows="8">{{ old('content', $lesson->content ?? '') }}</textarea>
      </div>

      <div class="grid grid-2" style="margin-bottom:22px">
        <div>
          <label class="form-label">Order</label>
          <input type="number" name="order" class="form-control" min="1" value="{{ old('order', $lesson->order ?? '') }}">
        </div>
        <div style="display:flex;align-items:flex-end">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px">
            <input type="checkbox" name="is_free" value="1" {{ old('is_free', $lesson->is_free ?? false) ? 'checked' : '' }}> Free preview (visible before enrolment)
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> {{ isset($lesson) ? 'Save Changes' : 'Add Lesson' }}</button>
    </form>
  </div>
</div>

<script>
function toggleLessonFields() {
  const type = document.getElementById('lessonType').value;
  document.getElementById('videoUrlField').style.display = (type === 'video' || type === 'mixed') ? '' : 'none';
  document.getElementById('attachmentField').style.display = (type === 'file' || type === 'mixed') ? '' : 'none';
}
toggleLessonFields();
</script>

@endsection
