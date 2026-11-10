@extends('layouts.app')
@section('title', isset($quiz) ? 'Edit Quiz' : 'New Quiz')
@section('page_title', isset($quiz) ? 'Edit Quiz' : 'New Quiz')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title">{{ isset($quiz) ? 'Edit Quiz' : 'New Quiz' }}</div>
    <div class="page-subtitle">{{ $course->title }}</div>
  </div>
  <a href="{{ route('lms.courses.show', $course) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Course</a>
</div>

@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ isset($quiz) ? route('lms.quizzes.update', [$course, $quiz]) : route('lms.quizzes.store', $course) }}" id="quizForm">
@csrf
@if(isset($quiz)) @method('PUT') @endif

<div class="card" style="max-width:760px;margin-bottom:20px">
  <div class="card-header"><div class="card-title">Quiz Details</div></div>
  <div class="card-body">
    <div style="margin-bottom:16px">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" required value="{{ old('title', $quiz->title ?? '') }}">
    </div>
    <div style="margin-bottom:16px">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="2">{{ old('description', $quiz->description ?? '') }}</textarea>
    </div>
    <div class="grid grid-2" style="margin-bottom:16px">
      <div>
        <label class="form-label">Time Limit (minutes)</label>
        <input type="number" name="time_limit" class="form-control" min="1" required value="{{ old('time_limit', $quiz->time_limit ?? 20) }}">
      </div>
      <div>
        <label class="form-label">Attempts Allowed</label>
        <input type="number" name="attempts_allowed" class="form-control" min="1" required value="{{ old('attempts_allowed', $quiz->attempts_allowed ?? 1) }}">
      </div>
    </div>
    <div class="grid grid-2" style="margin-bottom:16px">
      <div>
        <label class="form-label">Total Marks</label>
        <input type="number" name="total_marks" class="form-control" min="1" required id="totalMarksField" readonly value="{{ old('total_marks', $quiz->total_marks ?? 1) }}">
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Calculated from question marks. The server verifies this total.</div>
      </div>
      <div>
        <label class="form-label">Passing Marks</label>
        <input type="number" name="passing_marks" class="form-control" min="1" required value="{{ old('passing_marks', $quiz->passing_marks ?? 1) }}">
      </div>
    </div>
    <div class="grid grid-2">
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="draft" {{ old('status', $quiz->status ?? 'active') === 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="active" {{ old('status', $quiz->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="closed" {{ old('status', $quiz->status ?? 'active') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
      </div>
      <div>
        <label class="form-label">Due Date</label>
        <input type="datetime-local" name="due_date" class="form-control" value="{{ old('due_date', isset($quiz) && $quiz->due_date ? $quiz->due_date->format('Y-m-d\TH:i') : '') }}">
      </div>
    </div>
  </div>
</div>

<div class="card" style="max-width:760px;margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">Questions</div>
    <button type="button" class="btn btn-primary btn-sm" onclick="addQuestion()"><i class="fas fa-plus"></i> Add Question</button>
  </div>
  <div class="card-body" id="questionsWrap"></div>
</div>

@include('lms.partials.task-audience', ['task' => $quiz ?? null])
<button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> {{ isset($quiz) ? 'Save Quiz' : 'Create Quiz' }}</button>
</form>

<template id="questionTpl">
  <div class="quiz-question q-block">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <div class="quiz-qnum">Question <span class="q-index"></span></div>
      <button type="button" class="btn btn-outline btn-sm" style="color:var(--danger)" onclick="this.closest('.q-block').remove(); renumber();"><i class="fas fa-trash"></i></button>
    </div>
    <div style="margin-bottom:10px">
      <input type="text" class="form-control q-question" placeholder="Question text" required>
    </div>
    <div class="grid grid-2" style="margin-bottom:10px">
      <select class="form-control q-type" onchange="toggleOptions(this)">
        <option value="mcq">Multiple Choice</option>
        <option value="true_false">True / False</option>
        <option value="short">Short Answer</option>
      </select>
      <input type="number" class="form-control q-marks" placeholder="Marks" min="1" value="1" required>
    </div>
    <div class="q-options" style="margin-bottom:10px">
      <input type="text" class="form-control" placeholder="Option A" style="margin-bottom:6px">
      <input type="text" class="form-control" placeholder="Option B" style="margin-bottom:6px">
      <input type="text" class="form-control" placeholder="Option C (optional)" style="margin-bottom:6px">
      <input type="text" class="form-control" placeholder="Option D (optional)">
    </div>
    <div>
      <input type="text" class="form-control q-answer" placeholder="Correct answer (must match an option exactly, or true/false)" required>
    </div>
  </div>
</template>

<script>
let qCount = 0;

function addQuestion(data = null) {
  const tpl = document.getElementById('questionTpl').content.cloneNode(true);
  document.getElementById('questionsWrap').appendChild(tpl);
  if (data) {
    const block = document.querySelector('#questionsWrap .q-block:last-child');
    block.querySelector('.q-question').value = data.question || '';
    block.querySelector('.q-type').value = data.type || 'mcq';
    block.querySelector('.q-marks').value = data.marks || 1;
    block.querySelector('.q-answer').value = data.correct_answer || '';
    block.querySelectorAll('.q-options input').forEach((input, i) => input.value = (data.options || [])[i] || '');
    toggleOptions(block.querySelector('.q-type'));
  }
  renumber();
}

function renumber() {
  const blocks = document.querySelectorAll('.q-block');
  blocks.forEach((b, i) => b.querySelector('.q-index').textContent = i + 1);
  qCount = blocks.length;
  recalcTotal();
}

function toggleOptions(sel) {
  const block = sel.closest('.q-block');
  block.querySelector('.q-options').style.display = sel.value === 'mcq' ? '' : 'none';
}

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('.q-marks').forEach(i => total += parseInt(i.value || 0));
  document.getElementById('totalMarksField').value = total;
}

document.getElementById('questionsWrap').addEventListener('input', e => {
  if (e.target.classList.contains('q-marks')) recalcTotal();
});

document.getElementById('quizForm').addEventListener('submit', function (e) {
  this.querySelectorAll('[data-quiz-generated]').forEach(el => el.remove());
  const blocks = document.querySelectorAll('.q-block');
  if (blocks.length === 0) {
    e.preventDefault();
    alert('Add at least one question before saving.');
    return;
  }
  blocks.forEach((b, i) => {
    const type = b.querySelector('.q-type').value;
    const opts = Array.from(b.querySelectorAll('.q-options input')).map(i => i.value).filter(v => v.trim() !== '');
    const fields = {
      question: b.querySelector('.q-question').value,
      type: type,
      marks: b.querySelector('.q-marks').value,
      correct_answer: b.querySelector('.q-answer').value,
    };
    Object.entries(fields).forEach(([key, val]) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.dataset.quizGenerated = '1';
      input.name = `questions[${i}][${key}]`;
      input.value = val;
      this.appendChild(input);
    });
    if (type === 'mcq') {
      opts.forEach((val, oi) => {
        const input = document.createElement('input');
        input.type = 'hidden';
      input.dataset.quizGenerated = '1';
        input.name = `questions[${i}][options][${oi}]`;
        input.value = val;
        this.appendChild(input);
      });
    }
  });
});

@php
$initialQuizQuestions = old('questions', isset($quiz) ? $quiz->questions->map(fn ($question) => $question->only(['question', 'type', 'marks', 'correct_answer', 'options']))->values()->all() : []);
@endphp
const initialQuestions = @json($initialQuizQuestions);
if (initialQuestions.length) initialQuestions.forEach(q => addQuestion(q));
else addQuestion();
</script>

@endsection
