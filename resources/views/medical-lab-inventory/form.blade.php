@extends('layouts.app')
@section('title', isset($item) ? 'Edit Item' : 'Add Item')
@section('page_title', isset($item) ? 'Edit Medical Lab Inventory' : 'Add Medical Lab Inventory Item')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.medical-lab-inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:540px">
  <div class="card-body">
    <form action="{{ isset($item) ? route('admin.medical-lab-inventory.update', $item) : route('admin.medical-lab-inventory.store') }}" method="POST">
      @csrf @if(isset($item)) @method('PUT') @endif
      <div class="form-group"><label class="form-label">Item Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $item->name ?? '') }}"></div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Lab / Category</label>
          <input type="text" name="category" class="form-control" value="{{ old('category', $item->category ?? '') }}" placeholder="e.g. Anatomy Lab Room #22"></div>
        <div class="form-group"><label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" value="{{ old('unit', $item->unit ?? '') }}" placeholder="Pcs, Kg, Box..."></div>
      </div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Quantity *</label>
          <input type="number" name="quantity" class="form-control" required value="{{ old('quantity', $item->quantity ?? 0) }}" min="0"></div>
        <div class="form-group"><label class="form-label">Min. Quantity</label>
          <input type="number" name="min_quantity" class="form-control" value="{{ old('min_quantity', $item->min_quantity ?? 5) }}" min="0"></div>
      </div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Unit Price (Rs.)</label>
          <input type="number" name="price" class="form-control" value="{{ old('price', $item->price ?? '') }}" step="0.01"></div>
        <div class="form-group"><label class="form-label">Expiry Date</label>
          <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', isset($item->expiry_date) ? $item->expiry_date->format('Y-m-d') : '') }}"></div>
      </div>
      <div class="form-group"><label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="3" placeholder="e.g. storage conditions, condition notes, batch/lot number...">{{ old('remarks', $item->remarks ?? '') }}</textarea></div>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('admin.medical-lab-inventory.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
