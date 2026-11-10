@extends('layouts.app')
@section('title', $staff->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="{{ route('admin.staff.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">{{ $staff->name }}</div>
  @php $st = ['active'=>'success','inactive'=>'gray','terminated'=>'danger'][$staff->status] ?? 'gray'; @endphp
  <span class="badge {{ $st }}">{{ ucfirst($staff->status) }}</span>
  <div style="margin-left:auto;display:flex;gap:8px">
    <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
  </div>
</div>

<div class="grid grid-3" style="gap:20px;align-items:start">
  <div class="card">
    <div class="card-body" style="text-align:center">
      <div style="width:90px;height:90px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:28px;margin:0 auto 14px;overflow:hidden">
        @if($staff->photo)<img src="{{ asset('storage/'.$staff->photo) }}" style="width:100%;height:100%;object-fit:cover">
        @else {{ strtoupper(substr($staff->name,0,2)) }}@endif
      </div>
      <div style="font-weight:700;font-size:16px">{{ $staff->name }}</div>
      <div style="color:var(--text-muted);font-size:13px">{{ $staff->designation }}{{ $staff->department ? ' · '.$staff->department : '' }}</div>
      <div style="color:var(--text-muted);font-size:12px;margin-top:4px">{{ $staff->employee_id }}</div>
      <hr style="border-color:var(--border);margin:16px 0">
      <div style="text-align:left;font-size:13px;display:flex;flex-direction:column;gap:8px">
        <div><i class="fas fa-envelope" style="width:16px;color:var(--text-muted)"></i> {{ $staff->email }}</div>
        <div><i class="fas fa-phone" style="width:16px;color:var(--text-muted)"></i> {{ $staff->phone ?: '—' }}</div>
        <div><i class="fas fa-calendar" style="width:16px;color:var(--text-muted)"></i> Joined {{ $staff->joining_date?->format('d M Y') ?? '—' }}</div>
        <div><i class="fas fa-briefcase" style="width:16px;color:var(--text-muted)"></i> {{ ucfirst(str_replace('_',' ',$staff->employment_type)) }}</div>
      </div>
    </div>
  </div>

  <div class="card" style="grid-column:span 2">
    <div class="card-header"><div class="card-title"><i class="fas fa-money-bill-wave"></i> Salary Details</div></div>
    <div class="card-body">
      <div class="grid grid-2" style="gap:16px">
        <div><div style="font-size:12px;color:var(--text-muted)">Base Monthly Salary</div><div style="font-size:20px;font-weight:800">Rs. {{ number_format($staff->salary, 2) }}</div></div>
        <div><div style="font-size:12px;color:var(--text-muted)">Bank</div><div style="font-weight:600">{{ $staff->bank_name ?: '—' }}</div></div>
        <div><div style="font-size:12px;color:var(--text-muted)">Account Title</div><div style="font-weight:600">{{ $staff->bank_account_title ?: '—' }}</div></div>
        <div><div style="font-size:12px;color:var(--text-muted)">Account Number</div><div style="font-weight:600">{{ $staff->bank_account_number ? '••••' . substr($staff->bank_account_number, -4) : '—' }} <i class="fas fa-lock" style="font-size:10px;color:var(--text-muted)" title="Decrypted on view only"></i></div></div>
      </div>
    </div>
  </div>
</div>

@endsection
