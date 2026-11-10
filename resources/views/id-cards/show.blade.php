@extends('layouts.app')
@section('title', 'ID Card - ' . $student->name)
@section('page_title', 'Student ID Card')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px" class="no-print">
  <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
  @if(!$student->id_card_number)
  <form action="{{ route('students.id-card.issue', $student) }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-id-card"></i> Issue ID Card</button>
  </form>
  @else
  <form action="{{ route('students.id-card.reissue', $student) }}" method="POST" onsubmit="return confirm('Reissue this ID card? This may generate a fee voucher.')">
    @csrf
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Reissue (Lost/Damaged)</button>
  </form>
  @endif
</div>

@php
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $tagline     = \App\Models\Setting::get('institution_tagline', 'Tagline goes here');
  $logoPath    = \App\Models\Setting::get('institution_logo');
  $logoUrl     = $logoPath ? storage_asset($logoPath) : asset('logo.png');
  $session     = $student->academicYear;
  $joinDateValue = $session?->start_date ?? $student->admission_date;
  $joinDate      = $joinDateValue?->format('d-m-Y') ?? '—';
  $validUntilDate = $student->idCardExpiryDate();
  $validUntil     = $validUntilDate?->format('d-m-Y') ?? '—';
  $sessionName = $session?->name ?? ($student->session ?: '—');
  $programName = $student->program?->name ?? $student->schoolClass?->program?->name ?? '—';
  $partName    = $student->schoolClass?->section_label ?? $student->schoolClass?->name ?? '—';
  $photoUrl    = $student->photo ? storage_asset($student->photo) : null;
@endphp

<div class="id-card-pair">

  {{-- ───────────────────────── FRONT ───────────────────────── --}}
  <div class="id-card id-card-front">
    <svg class="id-card-bg id-card-bg-front" viewBox="0 0 320 500" preserveAspectRatio="none">
      <polygon points="0,0 320,0 320,100 0,128" fill="var(--primary)"/>
      <polygon points="0,100 320,64 320,116 0,158" fill="var(--primary-dark)" opacity="0.55"/>
      <polygon points="320,320 260,375 320,420" fill="var(--primary)" opacity="0.85"/>
      <polygon points="0,500 0,420 78,500" fill="var(--primary)"/>
    </svg>

    <div class="id-card-content">
      <div class="id-card-top-text">
        <div class="id-card-brand">
          <img src="{{ $logoUrl }}" class="id-card-logo" alt="{{ $institution }} logo">
          <div>
            <div class="id-card-company">{{ $institution }}</div>
            <div class="id-card-tagline">{{ $tagline }}</div>
          </div>
        </div>
      </div>

      <div class="id-card-photo-wrap">
        @if($photoUrl)
          <img src="{{ $photoUrl }}" class="id-card-photo" alt=""
               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        @endif
        <div class="id-card-photo id-card-photo-fallback" style="{{ $photoUrl ? 'display:none' : '' }}">
          {{ strtoupper(substr($student->name,0,2)) }}
        </div>
      </div>

      <div class="id-card-body">
        <div class="id-card-name">{{ $student->name }}</div>
        <div class="id-card-role">{{ $programName }}</div>
        <div class="id-card-roll">{{ $partName }}</div>
        <div class="id-card-roll">Student ID: {{ $student->student_id }}</div>

        <div class="id-card-front-terms">
          <div class="id-card-front-terms-title">Terms &amp; Conditions</div>
          <ul>
            <li>Property of {{ $institution }} — must be returned on demand.</li>
            <li>Non-transferable. Report loss immediately to administration.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  {{-- ───────────────────────── BACK ───────────────────────── --}}
  <div class="id-card id-card-back">
    <svg class="id-card-bg" viewBox="0 0 300 440" preserveAspectRatio="none">
      <polygon points="0,0 90,0 0,90" fill="var(--primary)"/>
      <polygon points="300,440 210,440 300,350" fill="var(--primary)"/>
    </svg>

    <div class="id-card-content">
      <div class="id-card-back-header">Terms &amp; Condition</div>
      <ul class="id-card-terms">
        <li>This card remains the property of {{ $institution }} and must be surrendered upon request.</li>
        <li>Report loss or theft of this card immediately to the administration office.</li>
      </ul>

      <div class="id-card-fields">
        <div><span>STUDENT ID</span><strong>{{ $student->student_id }}</strong></div>
        <div><span>PROGRAM</span><strong>{{ $programName }}</strong></div>
        <div><span>PART / SEM</span><strong>{{ $partName }}</strong></div>
        <div><span>SESSION</span><strong>{{ $sessionName }}</strong></div>
      </div>

      <div class="id-card-fields" style="margin-top:14px">
        <div><span>JOIN DATE</span><strong>{{ $joinDate }}</strong></div>
        <div><span>EXPIRE DATE</span><strong>{{ $validUntil }}</strong></div>
      </div>

      <div class="id-card-qr-wrap">
        <div id="qr-{{ $student->id }}" class="id-card-qr"></div>
        <div class="id-card-qr-caption">Scan to verify this ID</div>
      </div>

      <div class="id-card-back-footer">
        <div class="id-card-company" style="color:var(--primary)">{{ $institution }}</div>
        <div class="id-card-tagline" style="color:var(--text-muted)">{{ $tagline }}</div>
      </div>
    </div>
  </div>

</div>

@if($student->id_card_issued_at)
<p style="font-size:12px;color:var(--text-muted);margin-top:14px" class="no-print">
  Issued: {{ $student->id_card_issued_at->format('M d, Y') }}
  @if($student->id_card_reissue_count > 0) &middot; Reissued {{ $student->id_card_reissue_count }} time(s) @endif
</p>
@endif

<style>
.id-card-pair{ display:flex; gap:24px; flex-wrap:wrap; }
.id-card{
  position:relative; width:320px; max-width:100%; min-height:460px; border-radius:18px; overflow:hidden;
  background:#fff; box-shadow:0 8px 30px rgba(0,0,0,.15); border:1px solid var(--border);
}
.id-card-front{ min-height:520px; }
.id-card-bg{ position:absolute; inset:0; width:100%; height:100%; z-index:0; }
.id-card-content{ position:relative; z-index:1; display:flex; flex-direction:column; height:100%; }
.id-card-top-text{ padding:22px 22px 0; color:#fff; }
.id-card-brand{ display:flex; align-items:center; gap:10px; }
.id-card-logo{ width:42px; height:42px; object-fit:contain; border-radius:8px; background:#fff; padding:3px; box-shadow:0 2px 8px rgba(0,0,0,.14); flex-shrink:0; }
.id-card-company{ font-size:14px; font-weight:800; letter-spacing:.03em; }
.id-card-tagline{ font-size:10px; opacity:.85; margin-top:2px; }
.id-card-photo-wrap{ display:flex; justify-content:center; margin-top:20px; }
.id-card-photo{
  width:158px; height:158px; border-radius:18px; object-fit:cover; border:5px solid #fff;
  box-shadow:0 6px 18px rgba(0,0,0,.25); background:#f1f5f9;
}
.id-card-photo-fallback{
  display:flex; align-items:center; justify-content:center; font-size:46px; font-weight:800;
  color:#fff; background:var(--primary);
}
.id-card-body{ text-align:center; padding:16px 20px 22px; flex:1; }
.id-card-name{ font-size:24px; font-weight:900; color:var(--primary); margin-top:6px; line-height:1.15; }
.id-card-role{ font-size:15px; color:#334155; font-weight:700; letter-spacing:.01em; margin-top:4px; }
.id-card-roll{ font-size:12px; color:var(--text-muted); font-weight:600; margin-top:2px; }
.id-card-front-terms{ text-align:left; background:#f8fafc; border-radius:10px; padding:12px 14px; margin-top:18px; }
.id-card-front-terms-title{ font-size:11px; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px; }
.id-card-front-terms ul{ margin:0; padding-left:16px; font-size:10px; color:var(--text-muted); line-height:1.5; }
.id-card-front-terms li{ margin-bottom:4px; }
.id-card-back .id-card-content{ padding-top:28px; box-sizing:border-box; }
.id-card-back-header{ font-size:15px; font-weight:800; color:#1e293b; margin:0 28px; text-align:center; }
.id-card-terms{ font-size:9.5px; color:var(--text-muted); line-height:1.5; margin:14px auto 0; padding-left:16px; width:calc(100% - 56px); box-sizing:border-box; }
.id-card-terms li{ margin-bottom:8px; }
.id-card-fields{ font-size:11px; margin:10px auto 0; width:calc(100% - 56px); box-sizing:border-box; }
.id-card-fields div{ display:flex; gap:8px; padding:3px 0; }
.id-card-fields span{ width:78px; flex-shrink:0; color:var(--text-muted); font-weight:700; letter-spacing:.02em; }
.id-card-fields strong{ font-weight:600; word-break:break-all; }
.id-card-qr-wrap{ display:flex; flex-direction:column; align-items:center; margin-top:16px; }
.id-card-qr{ line-height:0; }
.id-card-qr-caption{ font-size:9px; color:var(--text-muted); margin-top:4px; }
.id-card-back-footer{ text-align:center; margin-top:auto; padding:0 20px 20px; }
@media print { .id-card{ box-shadow:none; border:1px solid #ddd; } }
</style>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qr-{{ $student->id }}'), {
  text: @json($qrUrl),
  width: 96,
  height: 96,
  colorDark: '#111111',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.M
});
</script>
@endpush
@endsection
