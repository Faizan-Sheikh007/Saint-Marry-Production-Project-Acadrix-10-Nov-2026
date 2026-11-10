@extends('layouts.app')
@section('title', isset($inventory) ? 'Edit Item' : 'Add Item')
@section('page_title', isset($inventory) ? 'Edit Inventory' : 'Add Inventory Item')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:540px">
  <div class="card-body">
    <form action="{{ isset($inventory) ? route('admin.inventory.update', $inventory) : route('admin.inventory.store') }}" method="POST">
      @csrf @if(isset($inventory)) @method('PUT') @endif
      <div class="form-group"><label class="form-label">Item Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $inventory->name ?? '') }}"></div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" value="{{ old('category', $inventory->category ?? '') }}" placeholder="Furniture, Electronics..."></div>
        <div class="form-group"><label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" value="{{ old('unit', $inventory->unit ?? '') }}" placeholder="Pcs, Kg, Ltr..."></div>
      </div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Quantity *</label>
          <input type="number" name="quantity" class="form-control" required value="{{ old('quantity', $inventory->quantity ?? 0) }}" min="0"></div>
        <div class="form-group"><label class="form-label">Min. Quantity</label>
          <input type="number" name="min_quantity" class="form-control" value="{{ old('min_quantity', $inventory->min_quantity ?? 5) }}" min="0"></div>
      </div>
      <div class="form-group"><label class="form-label">Unit Price (Rs.)</label>
        <input type="number" name="price" class="form-control" value="{{ old('price', $inventory->price ?? '') }}" step="0.01"></div>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
