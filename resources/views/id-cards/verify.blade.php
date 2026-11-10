@extends('layouts.auth')
@section('title', 'Verify ID — ' . $student->name)
@section('content')
@php
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $isActive    = $student->isActiveEnrollment();
  $joinDate    = $student->academicYear?->start_date ?? $student->admission_date;
  $validUntil  = $student->idCardExpiryDate();
  $isExpired   = $validUntil && $validUntil->isPast();
@endphp
<div style="width:100%;max-width:380px;background:#fff;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.25);overflow:hidden">
  <div style="background:linear-gradient(135deg,var(--primary-light),var(--primary));padding:18px 20px;color:#fff;text-align:center">
    <div style="font-size:13px;font-weight:800;letter-spacing:.03em">{{ $institution }}</div>
    <div style="font-size:11px;opacity:.85;margin-top:2px">Student ID Verification</div>
  </div>

  <div style="padding:26px 24px;text-align:center">
    @if($isActive && !$isExpired)
      <div style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;margin-bottom:16px">
        <i class="fas fa-circle-check"></i> Valid Student ID
      </div>
    @else
      <div style="display:inline-flex;align-items:center;gap:6px;background:#fee2e2;color:#991b1b;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;margin-bottom:16px">
        <i class="fas fa-triangle-exclamation"></i> {{ $isExpired ? 'Card Expired' : ucfirst($student->status) }}
      </div>
    @endif

    @if($student->photo)
      <img src="{{ storage_asset($student->photo) }}" style="width:90px;height:90px;border-radius:16px;object-fit:cover;border:3px solid var(--primary);margin-bottom:12px">
    @else
      <div style="width:90px;height:90px;border-radius:16px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;margin:0 auto 12px">{{ strtoupper(substr($student->name,0,2)) }}</div>
    @endif

    <div style="font-size:18px;font-weight:800">{{ $student->name }}</div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $student->program?->name ?? '—' }} · {{ $student->schoolClass?->section_label ?? '—' }}</div>

    <div style="text-align:left;background:#f8fafc;border-radius:10px;padding:14px 16px;margin-top:18px;font-size:12.5px;line-height:1.9">
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Student Status</span><strong>{{ $student->statusLabel() }}{{ $isActive ? '' : ' — Disabled' }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Student ID</span><strong>{{ $student->student_id }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Program</span><strong>{{ $student->program?->name ?? '—' }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Part / Semester</span><strong>{{ $student->schoolClass?->section_label ?? '—' }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Session</span><strong>{{ $student->academicYear?->name ?? $student->session ?? '—' }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Valid Until</span><strong>{{ $validUntil?->format('d M, Y') ?? '—' }}</strong></div>
    </div>

    <p style="font-size:10.5px;color:var(--text-muted);margin-top:16px">
      This page only confirms the record tied to this specific card. It does not contain contact, fee, or academic details.
    </p>
  </div>
</div>
@endsection
