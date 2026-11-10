@extends('layouts.app')
@section('title', isset($feeHead) ? 'Edit Fee Head' : 'Add Fee Head')
@section('page_title', isset($feeHead) ? 'Edit Fee Head' : 'Add Fee Head')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('fee-heads.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:600px">
  <div class="card-header"><div class="card-title">{{ isset($feeHead) ? 'Edit Fee Head' : 'New Fee Head' }}</div></div>
  <div class="card-body">
    <form action="{{ isset($feeHead) ? route('fee-heads.update', $feeHead) : route('fee-heads.store') }}" method="POST">
      @csrf
      @if(isset($feeHead)) @method('PUT') @endif
      <div class="form-group"><label class="form-label">Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $feeHead->name ?? '') }}" placeholder="e.g. Tuition Fee">
        @error('name')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror</div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Category *</label>
          <select name="category" class="form-control" required>
            @foreach(\App\Models\FeeHead::categories() as $key => $label)
            <option value="{{ $key }}" {{ old('category', $feeHead->category ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Type *</label>
          <select name="type" class="form-control" required>
            <option value="recurring" {{ old('type', $feeHead->type ?? 'recurring') == 'recurring' ? 'selected' : '' }}>Recurring (applies each cycle and splits across selected installments, e.g. Tuition/Transport)</option>
            <option value="per_cycle" {{ old('type', $feeHead->type ?? '') == 'per_cycle' ? 'selected' : '' }}>Per Semester/Year (applies each cycle and splits across that cycle's installments, e.g. Examination Fee)</option>
            <option value="one_time" {{ old('type', $feeHead->type ?? '') == 'one_time' ? 'selected' : '' }}>One-time (charged once for the whole program and split across the first cycle's installments, e.g. Admission Fee)</option>
          </select></div>
      </div>
      <div class="form-group"><label class="form-label">Default Amount (Rs.) *</label>
        <input type="number" step="1" min="0" name="default_amount" class="form-control" required value="{{ old('default_amount', $feeHead->default_amount ?? '') }}"></div>
      <div class="form-group"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $feeHead->description ?? '') }}</textarea></div>
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:20px;cursor:pointer">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $feeHead->is_active ?? true) ? 'checked' : '' }}> Active
      </label>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('fee-heads.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
