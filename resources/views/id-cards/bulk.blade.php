@extends('layouts.app')
@section('title', 'Bulk ID Cards - ' . $schoolClass->name)
@section('page_title', 'Bulk ID Card Printing')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px" class="no-print">
  <a href="{{ route('classes.show', $schoolClass) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print All</button>
</div>

@php $institution = \App\Models\Setting::get('institution_name', 'Your Institution'); $logoPath = \App\Models\Setting::get('institution_logo'); $logoUrl = $logoPath ? storage_asset($logoPath) : asset('logo.png'); @endphp

<div class="bulk-grid">
  @forelse($students as $student)
  @php
    $photoUrl = $student->photo ? storage_asset($student->photo) : null;
    $bulkJoinDate = $student->academicYear?->start_date ?? $student->admission_date;
    $bulkExpiryDate = $student->idCardExpiryDate();
  @endphp
  <div class="mini-card">
    <svg class="mini-card-bg" viewBox="0 0 220 300" preserveAspectRatio="none">
      <polygon points="0,0 220,0 220,55 0,72" fill="var(--primary)"/>
      <polygon points="0,300 0,255 45,300" fill="var(--primary)"/>
    </svg>
    <div class="mini-card-content">
      <div class="mini-card-company"><img src="{{ $logoUrl }}" alt="" style="width:22px;height:22px;object-fit:contain;background:#fff;border-radius:5px;padding:2px;vertical-align:middle;margin-right:5px">{{ $institution }}</div>
      <div class="mini-card-photo-wrap">
        @if($photoUrl)
          <img src="{{ $photoUrl }}" class="mini-card-photo" alt=""
               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        @endif
        <div class="mini-card-photo mini-card-photo-fallback" style="{{ $photoUrl ? 'display:none' : '' }}">
          {{ strtoupper(substr($student->name,0,2)) }}
        </div>
      </div>
      <div class="mini-card-body">
        <div class="mini-card-name">{{ $student->name }}</div>
        <div class="mini-card-meta"><strong>{{ $student->student_id }}</strong></div>
        <div class="mini-card-meta">{{ $student->program?->name ?? '—' }} · {{ $student->schoolClass?->section_label ?? '—' }}</div>
        <div class="mini-card-meta">{{ $bulkJoinDate?->format('d M Y') ?? '—' }} — {{ $bulkExpiryDate?->format('d M Y') ?? '—' }}</div>
        <div class="mini-card-qr-wrap">
          <div id="qr-{{ $student->id }}" class="mini-card-qr"></div>
        </div>
      </div>
    </div>
  </div>
  @empty
  <p style="color:var(--text-muted)">No active students found in this class.</p>
  @endforelse
</div>

<style>
.bulk-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
.mini-card{ position:relative; border-radius:14px; overflow:hidden; background:#fff; box-shadow:0 4px 16px rgba(0,0,0,.1); border:1px solid var(--border); min-height:300px; }
.mini-card-bg{ position:absolute; inset:0; width:100%; height:100%; z-index:0; }
.mini-card-content{ position:relative; z-index:1; }
.mini-card-company{ font-size:11px; font-weight:800; letter-spacing:.02em; color:#fff; padding:12px 14px 0; }
.mini-card-photo-wrap{ display:flex; justify-content:center; margin-top:14px; }
.mini-card-photo{ width:74px; height:74px; border-radius:14px; object-fit:cover; border:3px solid #fff; box-shadow:0 3px 10px rgba(0,0,0,.2); background:#f1f5f9; }
.mini-card-photo-fallback{ display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; color:#fff; background:var(--primary); }
.mini-card-body{ text-align:center; padding:8px 12px 16px; }
.mini-card-name{ font-size:13px; font-weight:800; margin-top:2px; }
.mini-card-meta{ font-size:10px; color:var(--text-muted); margin-top:2px; }
.mini-card-qr-wrap{ display:flex; justify-content:center; margin-top:10px; line-height:0; }
@media print { .mini-card{ box-shadow:none; border:1px solid #ddd; } .bulk-grid{ grid-template-columns:repeat(3,1fr); } }
</style>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
@foreach($students as $student)
new QRCode(document.getElementById('qr-{{ $student->id }}'), {
  text: @json($qrUrls[$student->id]),
  width: 56,
  height: 56,
  colorDark: '#111111',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.M
});
@endforeach
</script>
@endpush
@endsection
