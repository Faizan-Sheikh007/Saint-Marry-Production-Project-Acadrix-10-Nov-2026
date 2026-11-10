@extends('layouts.auth')
@section('title', 'Verify Fee Plan — ' . $feeAssignment->student->name)
@section('content')
@php
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $student = $feeAssignment->student;
@endphp
<div style="width:100%;max-width:380px;background:#fff;border-radius:18px;box-shadow:0 12px 40px rgba(0,0,0,.25);overflow:hidden">
  <div style="background:linear-gradient(135deg,var(--primary-light),var(--primary));padding:18px 20px;color:#fff;text-align:center">
    <div style="font-size:13px;font-weight:800;letter-spacing:.03em">{{ $institution }}</div>
    <div style="font-size:11px;opacity:.85;margin-top:2px">Fee Plan Verification</div>
  </div>

  <div style="padding:26px 24px;text-align:center">
    <div style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#166534;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;margin-bottom:16px">
      <i class="fas fa-circle-check"></i> Valid Fee Plan
    </div>

    <div style="font-size:18px;font-weight:800">{{ $student->name }}</div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $student->schoolClass->name ?? '' }} {{ $student->section }}</div>

    <div style="text-align:left;background:#f8fafc;border-radius:10px;padding:14px 16px;margin-top:18px;font-size:12.5px;line-height:1.9">
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">G.R. No</span><strong>{{ $student->student_id }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Fee Template</span><strong>{{ $feeAssignment->feeTemplate->name ?? '—' }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Installments</span><strong>{{ $feeAssignment->installments }}</strong></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Total Payable</span><strong>Rs. {{ number_format($feeAssignment->total_amount, 2) }}</strong></div>
    </div>

    <p style="font-size:10.5px;color:var(--text-muted);margin-top:16px">
      This page only confirms this fee plan belongs to this student's record. It does not display payment history.
    </p>
  </div>
</div>
@endsection
