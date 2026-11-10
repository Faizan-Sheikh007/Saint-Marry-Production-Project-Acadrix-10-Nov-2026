@extends('layouts.app')
@section('title', $item->name)
@section('page_title', 'Computer Lab Inventory')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.computer-lab-inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:500px">
  <div class="card-body">
    <h2 style="font-size:18px;font-weight:800;margin-bottom:12px">{{ $item->name }}</h2>
    <p><strong>Category:</strong> {{ $item->category ?? '—' }}</p>
    <p><strong>Type:</strong> {{ \App\Models\ComputerLabInventory::itemTypes()[$item->item_type] ?? ucfirst($item->item_type) }}</p>
    <p><strong>Quantity:</strong> {{ $item->quantity }} {{ $item->unit }}</p>
    <p><strong>Min. Stock:</strong> {{ $item->min_quantity }}</p>
    <p><strong>Unit Price:</strong> Rs. {{ number_format($item->price ?? 0) }}</p>
    @if($item->specification)
      <p><strong>Specification:</strong><br>{{ $item->specification }}</p>
    @endif
    @if($item->isServer())
    <div class="card" style="background:#f8fafc;margin-top:12px">
      <div class="card-body">
        <div style="font-weight:700;font-size:13px;margin-bottom:8px;color:var(--text-muted)"><i class="fas fa-server"></i> Server Details</div>
        <p><strong>Role:</strong> {{ $item->server_role ?? '—' }}</p>
        <p><strong>Operating System:</strong> {{ $item->operating_system ?? '—' }}</p>
        <p><strong>IP Address:</strong> {{ $item->ip_address ?? '—' }}</p>
      </div>
    </div>
    @endif
    @if($item->quantity <= $item->min_quantity)
    <div class="announcement warning" style="margin-top:12px"><i class="fas fa-exclamation-triangle"></i> <span>Stock is low! Please reorder.</span></div>
    @endif
  </div>
</div>
@endsection
