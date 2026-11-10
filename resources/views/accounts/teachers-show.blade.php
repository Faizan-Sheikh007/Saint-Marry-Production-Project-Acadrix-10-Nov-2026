@extends('layouts.app')
@section('title', $teacher->name)
@section('content')

<div class="card">
  <div class="card-body" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div style="width:56px;height:56px;border-radius:14px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px">
      {{ strtoupper(substr($teacher->name,0,2)) }}
    </div>
    <div>
      <div style="font-weight:800;font-size:18px">{{ $teacher->name }} <span style="color:var(--text-muted);font-weight:500;font-size:14px">({{ $teacher->employee_id }})</span></div>
      <div style="font-size:13px;color:var(--text-muted)">{{ $teacher->department ?? '—' }} &middot; {{ $teacher->designation ?? 'Teacher' }}</div>
    </div>
    <div style="margin-left:auto;font-size:11px;color:var(--text-muted)"><i class="fas fa-lock"></i> Read-only — profile edits are managed by EMS</div>
  </div>
</div>

<div class="card" style="margin-top:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-sack-dollar"></i> Salary (view-only)</div></div>
  <div class="card-body" style="display:flex;gap:32px;flex-wrap:wrap;font-size:13px">
    <div><strong>Monthly Salary:</strong> Rs.{{ number_format($teacher->salary ?? 0) }}</div>
    <div><strong>Bank:</strong> {{ $teacher->bank_name ?? '—' }}</div>
    <div><strong>Email:</strong> {{ $teacher->email ?? '—' }}</div>
    <div><strong>Phone:</strong> {{ $teacher->phone ?? '—' }}</div>
  </div>
</div>

@endsection
