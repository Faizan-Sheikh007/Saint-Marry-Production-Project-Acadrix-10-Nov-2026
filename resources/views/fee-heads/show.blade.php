@extends('layouts.app')
@section('title', $feeHead->name)
@section('page_title', 'Fee Head Details')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('fee-heads.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('fee-heads.edit', $feeHead) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
</div>
<div class="card" style="max-width:500px">
  <div class="card-header"><div class="card-title">{{ $feeHead->name }}</div></div>
  <div class="card-body">
    <p><strong>Category:</strong> {{ \App\Models\FeeHead::categories()[$feeHead->category] ?? $feeHead->category }}</p>
    <p><strong>Type:</strong> {{ ucfirst($feeHead->type) }}</p>
    <p><strong>Default Amount:</strong> Rs. {{ number_format($feeHead->default_amount, 0) }}</p>
    <p><strong>Vouchers issued:</strong> {{ $feeHead->vouchers->count() }}</p>
    <p><strong>Status:</strong> <span class="badge {{ $feeHead->is_active ? 'success' : 'gray' }}">{{ $feeHead->is_active ? 'Active' : 'Inactive' }}</span></p>
    @if($feeHead->description)<p><strong>Notes:</strong> {{ $feeHead->description }}</p>@endif
  </div>
</div>
@endsection
