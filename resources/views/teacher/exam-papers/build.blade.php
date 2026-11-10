@extends('layouts.app')
@section('title', 'Build Exam Paper')
@section('page_title', 'Build Exam Paper')

@push('styles')
<style>
  /* Question rows: side-by-side on desktop, stacked on phones/small tablets
     so the textarea isn't squeezed next to a marks box and a delete button. */
  @media (max-width: 640px) {
    .q-row { flex-wrap: wrap; }
    .q-row textarea { flex-basis: 100%; order: 1; }
    .q-row .qnum-label { order: 0; }
    .q-row input[type="number"] { flex: 1; max-width: none !important; order: 2; }
    .q-row button { order: 3; flex: 0 0 auto; }
    .card-header .btn-sm { width: 100%; justify-content: center; }
  }
  @media (max-width: 480px) {
    #paperForm .btn { width: 100%; justify-content: center; }
    #paperForm .paper-actions { flex-direction: column; }
  }
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <a href="{{ route('teacher.examinations.papers.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">
    {{ $examPaper->examination->name ?? 'Exam' }} — {{ $examPaper->examination->subject->name ?? '' }}
    <span style="font-weight:500;color:var(--text-muted);font-size:14px">({{ $examPaper->examination->schoolClass->name ?? '' }})</span>
  </div>
</div>

@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first() }}</div>
@endif

<form action="{{ route('teacher.examinations.papers.save', $examPaper) }}" method="POST" id="paperForm" enctype="multipart/form-data">
  @csrf

  <div class="card" style="margin-bottom:20px">
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Paper Instructions (optional)</label>
        <textarea name="instructions" class="form-control" rows="2" placeholder="e.g. Attempt all questions. Each part carries the marks indicated.">{{ old('instructions', $examPaper->instructions) }}</textarea>
      </div>
      <p style="font-size:13px;color:var(--text-muted);margin:0">
        Total marks for this exam: <strong>{{ $examPaper->examination->total_marks ?? '—' }}</strong>
      </p>
    </div>
  </div>

  {{-- ── Short Questions ── --}}
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-align-left"></i> Short Questions</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addRow('short')"><i class="fas fa-plus"></i> Add Short Question</button>
    </div>
    <div class="card-body" id="short-wrap">
      @forelse($shortQuestions as $i => $q)
        <div class="q-row" data-type="short" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px">
          <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">{{ $i + 1 }}.</span>
          <div style="flex:1">
            <textarea name="short_questions[]" class="form-control" rows="2" placeholder="Question text">{{ $q->question }}</textarea>
            @if($q->image_path)
              <div style="margin-top:6px"><img src="{{ storage_asset($q->image_path) }}" style="max-width:120px;border-radius:6px;border:1px solid var(--border-color)"></div>
            @endif
            <input type="file" name="short_images[{{ $i }}]" accept="image/*" class="form-control" style="margin-top:6px;font-size:12px">
          </div>
          <input type="number" name="short_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0" value="{{ $q->marks }}">
          <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
        </div>
      @empty
      @endforelse
    </div>
  </div>

  {{-- ── Long Questions ── --}}
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-align-justify"></i> Long Questions</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addRow('long')"><i class="fas fa-plus"></i> Add Long Question</button>
    </div>
    <div class="card-body" id="long-wrap">
      @forelse($longQuestions as $i => $q)
        <div class="q-row" data-type="long" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px">
          <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">{{ $i + 1 }}.</span>
          <div style="flex:1">
            <textarea name="long_questions[]" class="form-control" rows="3" placeholder="Question text">{{ $q->question }}</textarea>
            @if($q->image_path)
              <div style="margin-top:6px"><img src="{{ storage_asset($q->image_path) }}" style="max-width:120px;border-radius:6px;border:1px solid var(--border-color)"></div>
            @endif
            <input type="file" name="long_images[{{ $i }}]" accept="image/*" class="form-control" style="margin-top:6px;font-size:12px">
          </div>
          <input type="number" name="long_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0" value="{{ $q->marks }}">
          <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
        </div>
      @empty
      @endforelse
    </div>
  </div>

  {{-- ── Multiple Choice (MCQ) ── --}}
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list-check"></i> Multiple Choice Questions</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addMcqRow()"><i class="fas fa-plus"></i> Add MCQ</button>
    </div>
    <div class="card-body" id="mcq-wrap">
      @forelse($mcqQuestions as $i => $q)
        <div class="q-row mcq-row" style="border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px">
          <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
            <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">{{ $i + 1 }}.</span>
            <textarea name="mcq_questions[]" class="form-control" rows="2" placeholder="Question text">{{ $q->question }}</textarea>
            <input type="number" name="mcq_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0" value="{{ $q->marks }}">
            <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;margin-left:32px">
            @foreach(['a','b','c','d'] as $opt)
              <div style="display:flex;align-items:center;gap:6px">
                <input type="radio" name="mcq_correct[{{ $i }}]" value="{{ $opt }}" {{ $q->correct_answer === $opt ? 'checked' : '' }} title="Mark as correct">
                <input type="text" name="mcq_option_{{ $opt }}[]" class="form-control" placeholder="Option {{ strtoupper($opt) }}" value="{{ $q->options[$opt] ?? '' }}">
              </div>
            @endforeach
          </div>
          <div style="margin-left:32px;margin-top:8px">
            @if($q->image_path)
              <div style="margin-bottom:6px"><img src="{{ storage_asset($q->image_path) }}" style="max-width:120px;border-radius:6px;border:1px solid var(--border-color)"></div>
            @endif
            <input type="file" name="mcq_images[{{ $i }}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
          </div>
        </div>
      @empty
      @endforelse
    </div>
  </div>

  {{-- ── Fill in the Blanks ── --}}
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-pen"></i> Fill in the Blanks</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addFillBlankRow()"><i class="fas fa-plus"></i> Add Fill-in-the-Blank</button>
    </div>
    <div class="card-body" id="fillblank-wrap">
      @forelse($fillBlankQuestions as $i => $q)
        <div class="q-row" style="border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px">
          <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
            <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">{{ $i + 1 }}.</span>
            <textarea name="fillblank_questions[]" class="form-control" rows="2" placeholder="Question text — use ______ to mark the blank">{{ $q->question }}</textarea>
            <input type="number" name="fillblank_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0" value="{{ $q->marks }}">
            <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
          </div>
          <div style="margin-left:32px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <input type="text" name="fillblank_answers[]" class="form-control" style="max-width:260px" placeholder="Correct answer" value="{{ $q->correct_answer }}">
            @if($q->image_path)
              <img src="{{ storage_asset($q->image_path) }}" style="max-width:120px;border-radius:6px;border:1px solid var(--border-color)">
            @endif
            <input type="file" name="fillblank_images[{{ $i }}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
          </div>
        </div>
      @empty
      @endforelse
    </div>
  </div>

  {{-- ── True / False ── --}}
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-check-double"></i> True / False</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addTrueFalseRow()"><i class="fas fa-plus"></i> Add True/False</button>
    </div>
    <div class="card-body" id="truefalse-wrap">
      @forelse($trueFalseQuestions as $i => $q)
        <div class="q-row" style="border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px">
          <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
            <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">{{ $i + 1 }}.</span>
            <textarea name="truefalse_questions[]" class="form-control" rows="2" placeholder="Question text">{{ $q->question }}</textarea>
            <input type="number" name="truefalse_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0" value="{{ $q->marks }}">
            <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
          </div>
          <div style="margin-left:32px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:6px"><input type="radio" name="truefalse_correct[{{ $i }}]" value="true" {{ $q->correct_answer === 'true' ? 'checked' : '' }}> True</label>
            <label style="display:flex;align-items:center;gap:6px"><input type="radio" name="truefalse_correct[{{ $i }}]" value="false" {{ $q->correct_answer === 'false' ? 'checked' : '' }}> False</label>
            @if($q->image_path)
              <img src="{{ storage_asset($q->image_path) }}" style="max-width:120px;border-radius:6px;border:1px solid var(--border-color)">
            @endif
            <input type="file" name="truefalse_images[{{ $i }}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
          </div>
        </div>
      @empty
      @endforelse
    </div>
  </div>

  <div class="paper-actions" style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:40px">
    <button type="submit" name="action_btn" class="btn btn-outline" onclick="document.getElementById('action').value='draft'">
      <i class="fas fa-floppy-disk"></i> Save Draft
    </button>
    <button type="submit" name="action_btn" class="btn btn-primary" onclick="document.getElementById('action').value='submit'">
      <i class="fas fa-paper-plane"></i> Submit Paper
    </button>
  </div>
  <input type="hidden" name="action" id="action" value="draft">
</form>

<template id="row-template">
  <div class="q-row" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px">
    <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">•</span>
    <div style="flex:1">
      <textarea class="form-control" rows="2" placeholder="Question text"></textarea>
      <input type="file" accept="image/*" class="form-control" style="margin-top:6px;font-size:12px">
    </div>
    <input type="number" class="form-control" style="max-width:90px" placeholder="Marks" min="0">
    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
  </div>
</template>

<script>
let mcqCount = {{ $mcqQuestions->count() }};
let fillBlankCount = {{ $fillBlankQuestions->count() }};
let trueFalseCount = {{ $trueFalseQuestions->count() }};

function addRow(type) {
  const wrap = document.getElementById(type + '-wrap');
  const tpl  = document.getElementById('row-template').content.cloneNode(true);
  const row  = tpl.querySelector('.q-row');
  row.dataset.type = type;
  const textarea = row.querySelector('textarea');
  const numInput  = row.querySelector('input[type=number]');
  const fileInput = row.querySelector('input[type=file]');
  textarea.name = type + '_questions[]';
  textarea.rows = type === 'long' ? 3 : 2;
  numInput.name = type + '_marks[]';
  fileInput.name = type + '_images[' + document.querySelectorAll('#' + type + '-wrap .q-row').length + ']';
  wrap.appendChild(row);
  renumber();
}

function addMcqRow() {
  const i = mcqCount++;
  const wrap = document.getElementById('mcq-wrap');
  const div = document.createElement('div');
  div.className = 'q-row mcq-row';
  div.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px';
  div.innerHTML = `
    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
      <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">•</span>
      <textarea name="mcq_questions[]" class="form-control" rows="2" placeholder="Question text"></textarea>
      <input type="number" name="mcq_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0">
      <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;margin-left:32px">
      ${['a','b','c','d'].map(opt => `
        <div style="display:flex;align-items:center;gap:6px">
          <input type="radio" name="mcq_correct[${i}]" value="${opt}" title="Mark as correct">
          <input type="text" name="mcq_option_${opt}[]" class="form-control" placeholder="Option ${opt.toUpperCase()}">
        </div>`).join('')}
    </div>
    <div style="margin-left:32px;margin-top:8px">
      <input type="file" name="mcq_images[${i}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
    </div>`;
  wrap.appendChild(div);
  renumber();
}

function addFillBlankRow() {
  const i = fillBlankCount++;
  const wrap = document.getElementById('fillblank-wrap');
  const div = document.createElement('div');
  div.className = 'q-row';
  div.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px';
  div.innerHTML = `
    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
      <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">•</span>
      <textarea name="fillblank_questions[]" class="form-control" rows="2" placeholder="Question text — use ______ to mark the blank"></textarea>
      <input type="number" name="fillblank_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0">
      <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
    </div>
    <div style="margin-left:32px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="text" name="fillblank_answers[]" class="form-control" style="max-width:260px" placeholder="Correct answer">
      <input type="file" name="fillblank_images[${i}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
    </div>`;
  wrap.appendChild(div);
  renumber();
}

function addTrueFalseRow() {
  const i = trueFalseCount++;
  const wrap = document.getElementById('truefalse-wrap');
  const div = document.createElement('div');
  div.className = 'q-row';
  div.style.cssText = 'border:1px solid var(--border-color);border-radius:8px;padding:12px;margin-bottom:12px';
  div.innerHTML = `
    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:8px">
      <span class="qnum-label" style="padding-top:10px;font-weight:700;color:var(--text-muted);min-width:22px">•</span>
      <textarea name="truefalse_questions[]" class="form-control" rows="2" placeholder="Question text"></textarea>
      <input type="number" name="truefalse_marks[]" class="form-control" style="max-width:90px" placeholder="Marks" min="0">
      <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)" title="Remove"><i class="fas fa-trash"></i></button>
    </div>
    <div style="margin-left:32px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:6px"><input type="radio" name="truefalse_correct[${i}]" value="true"> True</label>
      <label style="display:flex;align-items:center;gap:6px"><input type="radio" name="truefalse_correct[${i}]" value="false"> False</label>
      <input type="file" name="truefalse_images[${i}]" accept="image/*" class="form-control" style="font-size:12px;max-width:280px">
    </div>`;
  wrap.appendChild(div);
  renumber();
}

function removeRow(btn) {
  btn.closest('.q-row').remove();
  renumber();
}
function renumber() {
  ['short', 'long'].forEach(type => {
    const rows = document.querySelectorAll('#' + type + '-wrap .q-row');
    rows.forEach((row, idx) => {
      row.querySelector('.qnum-label').textContent = (idx + 1) + '.';
      const fileInput = row.querySelector('input[type=file]');
      if (fileInput) fileInput.name = type + '_images[' + idx + ']';
    });
  });
  ['mcq', 'fillblank', 'truefalse'].forEach(type => {
    const rows = document.querySelectorAll('#' + type + '-wrap .q-row');
    rows.forEach((row, idx) => {
      row.querySelector('.qnum-label').textContent = (idx + 1) + '.';
      const fileInput = row.querySelector('input[type=file]');
      if (fileInput) fileInput.name = type + '_images[' + idx + ']';
      const radios = row.querySelectorAll('input[type=radio]');
      radios.forEach(r => {
        r.name = r.name.replace(/\[\d+\]/, '[' + idx + ']');
      });
    });
  });
}
document.getElementById('paperForm').addEventListener('submit', function (e) {
  const wraps = ['short-wrap', 'long-wrap', 'mcq-wrap', 'fillblank-wrap', 'truefalse-wrap'];
  const total = wraps.reduce((sum, id) => {
    const textareas = document.querySelectorAll('#' + id + ' .q-row textarea');
    return sum + Array.from(textareas).filter(t => t.value.trim() !== '').length;
  }, 0);
  if (document.getElementById('action').value === 'submit' && total === 0) {
    e.preventDefault();
    alert('Add at least one question before submitting.');
  }
});
</script>
@endsection
