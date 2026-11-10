@extends('layouts.app')
@section('title', 'Edit Voucher')
@section('page_title', 'Edit Voucher')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card" style="max-width:600px">
  <div class="card-header"><div class="card-title">Edit Voucher {{ $voucher->voucher_no }}</div></div>
  <div class="card-body">
    <form action="{{ route('vouchers.update', $voucher) }}" method="POST">
      @csrf @method('PUT')
      <div class="form-group"><label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title', $voucher->title) }}"></div>
      <div class="form-group"><label class="form-label">Amount *</label>
        <input type="number" step="1" min="0" name="amount" class="form-control" required value="{{ old('amount', $voucher->amount) }}"></div>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Discount</label>
          <input type="number" step="1" min="0" name="discount_amount" class="form-control" value="{{ old('discount_amount', $voucher->discount_amount) }}"></div>
        <div class="form-group"><label class="form-label">Due Date *</label>
          <input type="date" name="due_date" class="form-control" required value="{{ old('due_date', $voucher->due_date->format('Y-m-d')) }}"></div>
      </div>
      <div class="form-group"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $voucher->notes) }}</textarea></div>
      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection
