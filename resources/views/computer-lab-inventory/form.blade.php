@extends('layouts.app')
@section('title', isset($item) ? 'Edit Item' : 'Add Item')
@section('page_title', isset($item) ? 'Edit Computer Lab Inventory' : 'Add Computer Lab Inventory Item')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.computer-lab-inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:540px">
  <div class="card-body">
    <form action="{{ isset($item) ? route('admin.computer-lab-inventory.update', $item) : route('admin.computer-lab-inventory.store') }}" method="POST">
      @csrf @if(isset($item)) @method('PUT') @endif
      <div class="form-group"><label class="form-label">Item Name *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $item->name ?? '') }}"></div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" value="{{ old('category', $item->category ?? '') }}" placeholder="Equipment, Consumables..."></div>
        <div class="form-group"><label class="form-label">Item Type</label>
          <select name="item_type" class="form-control" id="itemTypeSelect" onchange="document.getElementById('serverFields').style.display = this.value === 'server' ? 'block' : 'none'">
            @foreach(\App\Models\ComputerLabInventory::itemTypes() as $val => $label)
              <option value="{{ $val }}" {{ old('item_type', $item->item_type ?? 'workstation') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select></div>
      </div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Quantity *</label>
          <input type="number" name="quantity" class="form-control" required value="{{ old('quantity', $item->quantity ?? 0) }}" min="0"></div>
        <div class="form-group"><label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" value="{{ old('unit', $item->unit ?? '') }}" placeholder="Pcs, Set..."></div>
      </div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Min. Quantity</label>
          <input type="number" name="min_quantity" class="form-control" value="{{ old('min_quantity', $item->min_quantity ?? 5) }}" min="0"></div>
        <div class="form-group"><label class="form-label">Unit Price (Rs.)</label>
          <input type="number" name="price" class="form-control" value="{{ old('price', $item->price ?? '') }}" step="0.01"></div>
      </div>
      <div class="form-group"><label class="form-label">Specification</label>
        <textarea name="specification" class="form-control" rows="3" placeholder="e.g. Core i7-12700, 16GB RAM, 512GB SSD, 24-inch monitor...">{{ old('specification', $item->specification ?? '') }}</textarea></div>

      <div id="serverFields" style="display:{{ old('item_type', $item->item_type ?? 'workstation') === 'server' ? 'block' : 'none' }};border:1px dashed var(--border-color);border-radius:8px;padding:14px;margin-bottom:14px">
        <div style="font-weight:700;font-size:13px;margin-bottom:10px;color:var(--text-muted)">Server Options</div>
        <div class="grid grid-2" style="gap:12px">
          <div class="form-group"><label class="form-label">Server Role</label>
            <input type="text" name="server_role" class="form-control" value="{{ old('server_role', $item->server_role ?? '') }}" placeholder="File Server, Domain Controller..."></div>
          <div class="form-group"><label class="form-label">Operating System</label>
            <input type="text" name="operating_system" class="form-control" value="{{ old('operating_system', $item->operating_system ?? '') }}" placeholder="Windows Server 2022, Ubuntu 24.04..."></div>
        </div>
        <div class="form-group"><label class="form-label">IP Address</label>
          <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $item->ip_address ?? '') }}" placeholder="192.168.1.10"></div>
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('admin.computer-lab-inventory.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
