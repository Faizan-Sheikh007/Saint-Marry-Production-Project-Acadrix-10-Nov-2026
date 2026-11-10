@extends('layouts.app')
@section('title', isset($examination) ? 'Edit Examination' : 'Add Examination')
@section('page_title', isset($examination) ? 'Edit Examination' : 'Add Examination')
@section('content')

@php
  $isAdmin     = auth()->user()->isAdmin();
  $indexRoute  = $isAdmin ? route('admin.examinations.index') : route('teacher.examinations.index');
  $storeRoute  = $isAdmin ? route('admin.examinations.store') : route('teacher.examinations.store');
  $updateRoute = isset($examination)
      ? ($isAdmin
          ? route('admin.examinations.update',  $examination)
          : route('teacher.examinations.update', $examination))
      : null;
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ $indexRoute }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:22px;font-weight:800">
    {{ isset($examination) ? 'Edit Examination' : 'Schedule Examination' }}
  </div>
</div>

<div class="card" style="max-width:640px">
  <div class="card-header">
    <div class="card-title">
      {{ isset($examination) ? 'Edit Examination Details' : 'New Examination' }}
    </div>
  </div>
  <div class="card-body">
    <form action="{{ isset($examination) ? $updateRoute : $storeRoute }}" method="POST">
      @csrf
      @if(isset($examination)) @method('PUT') @endif

      <div class="form-group">
        <label class="form-label">Exam Name *</label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $examination->name ?? '') }}"
               placeholder="e.g. Mid Term Exam 2025">
        @error('name')
          <div style="color:red;font-size:12px;margin-top:4px">{{ $message }}</div>
        @enderror
      </div>

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Class *</label>
          <select name="school_class_id" class="form-control" required
                  {{ isset($examination) ? 'disabled' : '' }}>
            <option value="">Select Class</option>
            @foreach($classes as $class)
              <option value="{{ $class->id }}"
                {{ old('school_class_id', $examination->school_class_id ?? '') == $class->id ? 'selected' : '' }}>
                {{ $class->name }}{{ $class->section ? ' - ' . $class->section : '' }}
              </option>
            @endforeach
          </select>
          {{-- Re-submit class_id if editing since field is disabled --}}
          @if(isset($examination))
            <input type="hidden" name="school_class_id" value="{{ $examination->school_class_id }}">
          @endif
        </div>

        <div class="form-group">
          <label class="form-label">Subject</label>
          <select name="subject_id" class="form-control">
            <option value="">All Subjects</option>
            @foreach($subjects as $subject)
              <option value="{{ $subject->id }}"
                {{ old('subject_id', $examination->subject_id ?? '') == $subject->id ? 'selected' : '' }}>
                {{ $subject->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Exam Date *</label>
          <input type="date" name="exam_date" class="form-control" required
                 value="{{ old('exam_date', isset($examination) ? $examination->exam_date?->format('Y-m-d') : '') }}">
          @error('exam_date')
            <div style="color:red;font-size:12px;margin-top:4px">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Type *</label>
          <select name="type" class="form-control" required>
            @foreach(['midterm' => 'Mid Term', 'final' => 'Final', 'quiz' => 'Quiz', 'unit_test' => 'Unit Test'] as $k => $v)
              <option value="{{ $k }}"
                {{ old('type', $examination->type ?? '') == $k ? 'selected' : '' }}>
                {{ $v }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Total Marks *</label>
          <input type="number" name="total_marks" class="form-control" required min="1"
                 value="{{ old('total_marks', $examination->total_marks ?? 100) }}">
          @error('total_marks')
            <div style="color:red;font-size:12px;margin-top:4px">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Passing Marks *</label>
          <input type="number" name="passing_marks" class="form-control" required min="1"
                 value="{{ old('passing_marks', $examination->passing_marks ?? 40) }}">
          @error('passing_marks')
            <div style="color:red;font-size:12px;margin-top:4px">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
        <a href="{{ $indexRoute }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i>
          {{ isset($examination) ? 'Update Examination' : 'Schedule Examination' }}
        </button>
      </div>
    </form>
  </div>
</div>

@endsection
