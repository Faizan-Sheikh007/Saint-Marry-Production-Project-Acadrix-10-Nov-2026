@extends('layouts.app')
@section('title', isset($course) ? 'Edit Course' : 'New Course')
@section('page_title', isset($course) ? 'Edit Course' : 'New Course')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ isset($course) ? 'Edit Course' : 'New Course' }}</div>
    <div class="page-subtitle">{{ isset($course) ? $course->title : 'Set up a course shell — you can add lessons, assignments and quizzes after saving.' }}</div>
  </div>
  <a href="{{ isset($course) ? route('lms.courses.show', $course) : route('lms.courses.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card" style="max-width:720px">
  <div class="card-body">
    <form method="POST" action="{{ isset($course) ? route('lms.courses.update', $course) : route('lms.courses.store') }}">
      @csrf
      @if(isset($course)) @method('PUT') @endif

      <div style="margin-bottom:16px">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title', $course->title ?? '') }}">
        @error('title')<div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $course->description ?? '') }}</textarea>
      </div>

      <div class="grid grid-2" style="margin-bottom:16px">
        <div>
          <label class="form-label">Subject</label>
          <select name="subject_id" class="form-control">
            <option value="">— None —</option>
            @foreach($subjects as $s)
              <option value="{{ $s->id }}" {{ old('subject_id', $course->subject_id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="form-label">Class</label>
          <select name="school_class_id" class="form-control">
            <option value="">— None —</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" {{ old('school_class_id', $course->school_class_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->display_name ?? $c->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div style="margin-bottom:22px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $val => $label)
            <option value="{{ $val }}" {{ old('status', $course->status ?? 'draft') === $val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px">Publishing a course tied to a class will automatically enrol every active student in that class.</div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> {{ isset($course) ? 'Save Changes' : 'Create Course' }}</button>
    </form>
  </div>
</div>

@endsection
