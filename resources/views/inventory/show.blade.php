@extends('layouts.app')
@section('title', $inventory->name)
@section('page_title', 'Inventory Item')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:500px">
  <div class="card-body">
    <h2 style="font-size:18px;font-weight:800;margin-bottom:12px">{{ $inventory->name }}</h2>
    <p><strong>Category:</strong> {{ $inventory->category ?? '—' }}</p>
    <p><strong>Quantity:</strong> {{ $inventory->quantity }} {{ $inventory->unit }}</p>
    <p><strong>Min. Stock:</strong> {{ $inventory->min_quantity }}</p>
    <p><strong>Unit Price:</strong> Rs. {{ number_format($inventory->price ?? 0) }}</p>
    @if($inventory->quantity <= $inventory->min_quantity)
    <div class="announcement warning" style="margin-top:12px"><i class="fas fa-exclamation-triangle"></i> <span>Stock is low! Please reorder.</span></div>
    @endif
  </div>
</div>
@endsection
