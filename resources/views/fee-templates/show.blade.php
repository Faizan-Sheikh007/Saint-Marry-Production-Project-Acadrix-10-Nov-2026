@extends('layouts.app')
@section('title', $feeTemplate->name)
@section('page_title', 'Fee Template Details')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('fee-templates.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('fee-templates.edit', $feeTemplate) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
  <a href="{{ route('vouchers.index') }}#generate" class="btn btn-outline btn-sm"><i class="fas fa-bolt"></i> Generate Vouchers</a>
</div>
<div class="grid grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">{{ $feeTemplate->name }}</div></div>
    <div class="card-body">
      <p><strong>Class/Program:</strong> {{ $feeTemplate->schoolClass->name ?? 'All Classes' }}</p>
      <p><strong>Billing Cycle:</strong> {{ ucfirst($feeTemplate->billing_cycle) }}</p>
      @if(in_array($feeTemplate->billing_cycle, ['semester','annual']))
      <p><strong>Parts per {{ $feeTemplate->cycleLabel() }}:</strong> {{ $feeTemplate->parts_per_cycle }}</p>
      <p><strong>Program Length:</strong> {{ $feeTemplate->total_cycles ? $feeTemplate->total_cycles . ' ' . $feeTemplate->cycleLabel() . '(s)' : 'Not set — bulk "generate whole program" won\'t be available' }}</p>
      @endif
      <p><strong>Status:</strong> <span class="badge {{ $feeTemplate->is_active ? 'success' : 'gray' }}">{{ $feeTemplate->is_active ? 'Active' : 'Inactive' }}</span></p>
      @if($feeTemplate->description)<p><strong>Notes:</strong> {{ $feeTemplate->description }}</p>@endif
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Fee Head Breakdown</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fee Head</th><th>Amount</th></tr></thead>
        <tbody>
          @foreach($feeTemplate->items as $item)
          <tr>
            <td>{{ $item->feeHead->name }}</td>
            <td>
              @if(is_null($item->amount))
                <span style="color:var(--text-muted);font-style:italic">Not set yet</span>
              @else
                Rs. {{ number_format($item->amount, 2) }}
              @endif
            </td>
          </tr>
          @endforeach
          <tr style="font-weight:700"><td>Total</td><td>Rs. {{ number_format($feeTemplate->items->sum('amount'), 2) }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
