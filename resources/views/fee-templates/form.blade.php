@extends('layouts.app')
@section('title', isset($feeTemplate) ? 'Edit Fee Template' : 'Add Fee Template')
@section('page_title', isset($feeTemplate) ? 'Edit Fee Template' : 'Add Fee Template')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('fee-templates.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif
<div class="card" style="max-width:750px">
  <div class="card-header"><div class="card-title">{{ isset($feeTemplate) ? 'Edit Fee Template' : 'New Fee Template' }}</div></div>
  <div class="card-body">
    <form action="{{ isset($feeTemplate) ? route('fee-templates.update', $feeTemplate) : route('fee-templates.store') }}" method="POST">
      @csrf
      @if(isset($feeTemplate)) @method('PUT') @endif

      <div class="form-group"><label class="form-label">Template Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $feeTemplate->name ?? '') }}" placeholder="e.g. Grade 10 Monthly Fee Plan"></div>

      @php
        $initialProgramId = old('program_id', $feeTemplate->schoolClass->program_id ?? '');
      @endphp
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Program</label>
          <select name="program_id" id="program_id" class="form-control">
            <option value="">Applies to all programs / classes</option>
            @foreach($programs as $program)
            <option value="{{ $program->id }}" data-system-type="{{ $program->system_type }}" {{ $initialProgramId == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
            @endforeach
          </select>
          <span id="program-term-count" class="badge primary" style="display:none;margin-top:6px"></span>
        </div>
        <div class="form-group"><label class="form-label">Part / Semester</label>
          <select name="school_class_id" id="school_class_id" class="form-control">
            <option value="">Applies to all parts/semesters in this program</option>
            @if($initialProgramId)
              @php
                $initialClasses = $classes->where('program_id', $initialProgramId)
                  ->sortBy(fn ($c) => (int) (preg_match('/(\d+)/', $c->name, $m) ? $m[1] : 0));
              @endphp
              @foreach($initialClasses as $class)
              <option value="{{ $class->id }}" {{ old('school_class_id', $feeTemplate->school_class_id ?? '') == $class->id ? 'selected' : '' }}>{{ $class->name }}{{ $class->section ? ' ('.$class->section.')' : '' }}</option>
              @endforeach
            @endif
          </select>
        </div>
      </div>

      <div class="form-group"><label class="form-label">Billing Cycle *</label>
        <select name="billing_cycle" id="billing-cycle-select" class="form-control" required>
          @foreach(['one_time'=>'One-time','monthly'=>'Monthly','semester'=>'Semester','annual'=>'Annual'] as $key => $label)
          <option value="{{ $key }}" {{ old('billing_cycle', $feeTemplate->billing_cycle ?? 'monthly') == $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select></div>

      <div class="grid grid-2" style="gap:12px" id="cycle-config-row">
        <div class="form-group">
          <label class="form-label">Parts per Semester/Year</label>
          <input type="number" min="1" max="12" name="parts_per_cycle" class="form-control" value="{{ old('parts_per_cycle', $feeTemplate->parts_per_cycle ?? 1) }}">
          <small style="color:var(--text-muted)">e.g. 2 = fee is split into "Part 1" &amp; "Part 2" within each semester/year, due separately.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Total Semesters/Years in Program</label>
          <input type="number" min="1" max="20" name="total_cycles" id="total_cycles" class="form-control" value="{{ old('total_cycles', $feeTemplate->total_cycles ?? '') }}" placeholder="e.g. 8">
          <small style="color:var(--text-muted)">Lets you generate the whole program's plan (all semesters/years) for a student in one click.</small>
        </div>
      </div>

      <div class="form-group"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $feeTemplate->description ?? '') }}</textarea></div>

      <label style="display:flex;align-items:center;gap:8px;margin-bottom:20px;cursor:pointer">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $feeTemplate->is_active ?? true) ? 'checked' : '' }}> Active
      </label>

      <div style="border-top:1px solid var(--border);padding-top:16px;margin-bottom:16px">
        <label class="form-label" style="margin-bottom:2px;display:block">Fee Heads &amp; Amounts</label>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">Add all the fee heads for this template now — you can leave the amount blank and fill it in later. Only heads with an amount are charged / printed on the slip.</div>
        <div id="template-items">
          @php $existing = isset($feeTemplate) ? $feeTemplate->items : collect([null]); @endphp
          @foreach($existing as $ti)
          <div class="grid grid-2" style="gap:10px;margin-bottom:8px" data-row>
            <select name="fee_head_id[]" class="form-control" required>
              <option value="">Select Fee Head</option>
              @foreach($feeHeads as $fh)
              <option value="{{ $fh->id }}" data-amount="{{ $fh->default_amount }}" {{ old('fee_head_id.'.$loop->parent->index, $ti->fee_head_id ?? '') == $fh->id ? 'selected' : '' }}>{{ $fh->name }}</option>
              @endforeach
            </select>
            <div style="display:flex;gap:8px">
              <input type="number" step="1" min="0" name="amount[]" class="form-control" placeholder="Amount (optional)" value="{{ old('amount.'.$loop->index, $ti->amount ?? '') }}">
              <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('[data-row]').remove()"><i class="fas fa-trash"></i></button>
            </div>
          </div>
          @endforeach
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="add-row"><i class="fas fa-plus"></i> Add Fee Head Row</button>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('fee-templates.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Template</button>
      </div>
    </form>
  </div>
</div>

<template id="row-template">
  <div class="grid grid-2" style="gap:10px;margin-bottom:8px" data-row>
    <select name="fee_head_id[]" class="form-control" required>
      <option value="">Select Fee Head</option>
      @foreach($feeHeads as $fh)
      <option value="{{ $fh->id }}" data-amount="{{ $fh->default_amount }}">{{ $fh->name }}</option>
      @endforeach
    </select>
    <div style="display:flex;gap:8px">
      <input type="number" step="1" min="0" name="amount[]" class="form-control" placeholder="Amount (optional)">
      <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('[data-row]').remove()"><i class="fas fa-trash"></i></button>
    </div>
  </div>
</template>

@push('scripts')
<script>
function toggleCycleConfig() {
  const cycle = document.getElementById('billing-cycle-select').value;
  document.getElementById('cycle-config-row').style.display = (cycle === 'semester' || cycle === 'annual') ? 'grid' : 'none';
}
document.getElementById('billing-cycle-select').addEventListener('change', toggleCycleConfig);
toggleCycleConfig();

document.getElementById('add-row').addEventListener('click', function () {
  const tpl = document.getElementById('row-template').content.cloneNode(true);
  document.getElementById('template-items').appendChild(tpl);
});
document.getElementById('template-items').addEventListener('change', function (e) {
  if (e.target.matches('select[name="fee_head_id[]"]')) {
    const amountInput = e.target.closest('[data-row]').querySelector('input[name="amount[]"]');
    const opt = e.target.selectedOptions[0];
    if (opt && opt.dataset.amount && !amountInput.value) amountInput.value = opt.dataset.amount;
  }
});

(function () {
  const programSelect  = document.getElementById('program_id');
  const sectionSelect  = document.getElementById('school_class_id');
  const termCountLabel = document.getElementById('program-term-count');
  const totalCyclesInput = document.getElementById('total_cycles');
  const preselectedSection = "{{ old('school_class_id', $feeTemplate->school_class_id ?? '') }}";
  const sectionsUrlTemplate = "{{ route('accounts.programs.sections', ['program' => '__PROGRAM_ID__']) }}";

  // Only auto-fill Total Semesters/Years while the user hasn't typed their own value.
  totalCyclesInput.addEventListener('input', function () {
    totalCyclesInput.dataset.userEdited = '1';
  });

  function loadSections(programId, selectedId) {
    if (!programId) {
      sectionSelect.innerHTML = '<option value="">Applies to all parts/semesters in this program</option>';
      termCountLabel.style.display = 'none';
      return;
    }
    sectionSelect.innerHTML = '<option value="">Loading…</option>';
    termCountLabel.style.display = 'none';

    fetch(sectionsUrlTemplate.replace('__PROGRAM_ID__', programId))
      .then(res => res.json())
      .then(sections => {
        sectionSelect.innerHTML = '<option value="">Applies to all parts/semesters in this program</option>';
        sections.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id;
          opt.textContent = s.label;
          if (String(s.id) === String(selectedId)) opt.selected = true;
          sectionSelect.appendChild(opt);
        });

        const count = sections.length;
        const systemType = programSelect.selectedOptions[0]?.dataset.systemType;
        const unit = systemType === 'annual' ? 'Year' : 'Semester';

        if (count) {
          termCountLabel.style.display = 'inline-flex';
          termCountLabel.textContent = `${count} ${unit}${count === 1 ? '' : 's'}`;
        } else {
          termCountLabel.style.display = 'none';
        }

        if (count && totalCyclesInput.dataset.userEdited !== '1') {
          totalCyclesInput.value = count;
        }
      })
      .catch(() => {
        sectionSelect.innerHTML = '<option value="">Could not load parts/semesters</option>';
      });
  }

  programSelect.addEventListener('change', function () {
    loadSections(this.value, null);
  });

  // Populate on first load so editing an existing template shows its program's parts/semesters too.
  if (programSelect.value) {
    loadSections(programSelect.value, preselectedSection);
  }
})();
</script>
@endpush
@endsection