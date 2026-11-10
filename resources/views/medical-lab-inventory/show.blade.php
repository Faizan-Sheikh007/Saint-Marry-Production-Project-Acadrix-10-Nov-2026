@extends('layouts.app')
@section('title', $item->name)
@section('page_title', 'Medical Lab Inventory')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.medical-lab-inventory.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:500px">
  <div class="card-body">
    <h2 style="font-size:18px;font-weight:800;margin-bottom:12px">{{ $item->name }}</h2>
    <p><strong>Category:</strong> {{ $item->category ?? '—' }}</p>
    <p><strong>Quantity:</strong> {{ $item->quantity }} {{ $item->unit }}</p>
    <p><strong>Min. Stock:</strong> {{ $item->min_quantity }}</p>
    <p><strong>Unit Price:</strong> Rs. {{ number_format($item->price ?? 0) }}</p>
    <p><strong>Expiry Date:</strong> {{ $item->expiry_date ? $item->expiry_date->format('d M Y') : '—' }}</p>
    @if($item->remarks)
      <p><strong>Remarks:</strong> {{ $item->remarks }}</p>
    @endif
    @if($item->isExpired())
    <div class="announcement warning" style="margin-top:12px;background:#fee2e2;color:#991b1b"><i class="fas fa-times-circle"></i> <span>This item has expired.</span></div>
    @elseif($item->isExpiringSoon())
    <div class="announcement warning" style="margin-top:12px"><i class="fas fa-clock"></i> <span>This item is expiring within 30 days.</span></div>
    @endif
    @if($item->quantity <= $item->min_quantity)
    <div class="announcement warning" style="margin-top:12px"><i class="fas fa-exclamation-triangle"></i> <span>Stock is low! Please reorder.</span></div>
    @endif
  </div>
</div>
@endsection
