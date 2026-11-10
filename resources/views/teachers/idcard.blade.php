<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $teacher->name }} — Staff ID Card</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{
    --primary:#890C25; --primary-light:#c4213f; --primary-dark:#4a0513;
    --ink:#1a0608; --muted:#9a7f86; --line:#f0e3e6; --gold:#d4af6a;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  html,body{ height:100%; }
  body {
    font-family:'Segoe UI',Arial,sans-serif; background:#eef0f3;
    padding:48px 24px;
  }
  .page-wrap{ max-width:900px; margin:0 auto; }

  /* ── Toolbar ── */
  .toolbar{
    display:flex; justify-content:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;
  }
  .toolbar a, .toolbar button{
    font-family:inherit; font-size:13.5px; font-weight:700; border-radius:10px; cursor:pointer;
    padding:11px 20px; display:inline-flex; align-items:center; gap:8px; text-decoration:none;
    border:1px solid transparent; transition:all .18s;
  }
  .toolbar .btn-print{ background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%); color:#fff; box-shadow:0 8px 18px rgba(137,12,37,.28); }
  .toolbar .btn-print:hover{ transform:translateY(-2px); box-shadow:0 12px 22px rgba(137,12,37,.35); }
  .toolbar .btn-ghost{ background:#fff; color:var(--ink); border-color:#e2dfe0; }
  .toolbar .btn-ghost:hover{ border-color:var(--primary); color:var(--primary); }
  .sig-alert{
    max-width:760px; margin:0 auto 24px; background:#fff8e8; border:1px solid #f2d9a0; color:#8a6a10;
    font-size:12.5px; font-weight:600; padding:10px 16px; border-radius:10px; text-align:center;
    display:flex; align-items:center; justify-content:center; gap:8px;
  }
  .sig-alert a{ color:var(--primary); text-decoration:underline; font-weight:800; }

  /* ── Cards row (this is the only flex-wrap container for the cards) ── */
  .cards-row{
    display:flex; flex-wrap:wrap; gap:40px; justify-content:center; align-items:flex-start;
  }

  /* ── Card shell ── */
  .card{
    width:338px; max-width:100%; min-height:552px; border-radius:22px; overflow:hidden; background:#fff;
    position:relative; display:flex; flex-direction:column; flex:0 0 338px;
    box-shadow:0 4px 8px rgba(20,10,12,.05), 0 24px 40px -12px rgba(20,10,12,.22);
  }
  .punch{
    position:absolute; top:14px; left:50%; transform:translateX(-50%);
    width:56px; height:9px; border-radius:20px; background:rgba(255,255,255,.35);
    box-shadow:inset 0 1px 3px rgba(0,0,0,.25); z-index:5;
  }

  /* ══ FRONT ══ */
  .front .head{
    position:relative; background:linear-gradient(155deg,var(--primary-light) 0%,var(--primary) 45%,var(--primary-dark) 100%);
    color:#fff; padding:30px 20px 66px; text-align:center; overflow:hidden;
  }
  .front .head .pattern{
    position:absolute; inset:0; opacity:.14;
    background-image:radial-gradient(circle,#fff 1.4px,transparent 1.4px);
    background-size:16px 16px;
  }
  .front .head .ring{
    position:absolute; width:220px; height:220px; border-radius:50%; border:1.5px solid rgba(255,255,255,.25);
    top:-120px; right:-70px;
  }
  .front .head::after{
    content:""; position:absolute; left:0; right:0; bottom:-1px; height:34px; background:#fff;
    border-radius:50% 50% 0 0/100% 100% 0 0;
  }
  .brand{ display:flex; align-items:center; justify-content:center; gap:8px; position:relative; z-index:2; }
  .brand i{ font-size:13px; opacity:.9; }
  .school{ font-size:10.5px; letter-spacing:2.6px; opacity:.9; text-transform:uppercase; font-weight:600; }
  .school-name{ font-size:18px; font-weight:800; margin-top:5px; position:relative; z-index:2; letter-spacing:.2px; }
  .card-type{
    display:inline-block; margin-top:8px; font-size:9.5px; letter-spacing:1.6px; font-weight:700;
    background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); padding:3px 12px;
    border-radius:20px; position:relative; z-index:2; text-transform:uppercase;
  }

  .photo-wrap{
    width:112px; height:112px; border-radius:50%; background:#fff; margin:-50px auto 0; padding:4px;
    position:relative; z-index:3; box-shadow:0 10px 22px rgba(20,10,12,.22);
  }
  .photo-wrap img, .photo-wrap .initials{
    width:100%; height:100%; border-radius:50%; object-fit:cover; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(145deg,var(--primary-light),var(--primary-dark)); color:#fff; font-size:34px; font-weight:800;
  }
  .status-dot{
    position:absolute; right:4px; bottom:6px; width:20px; height:20px; border-radius:50%; border:3px solid #fff;
    background:{{ $teacher->status === 'active' ? '#10b981' : '#ef4444' }}; z-index:4;
  }

  .name{ text-align:center; font-size:20px; font-weight:800; color:var(--ink); margin-top:14px; padding:0 16px; }
  .role{
    text-align:center; font-size:11.5px; color:var(--primary); font-weight:700; margin-top:4px;
    text-transform:uppercase; letter-spacing:.4px;
  }

  .info{ padding:16px 24px 0; }
  .info-row{
    display:flex; justify-content:space-between; align-items:center; font-size:12px; padding:8px 0;
    border-bottom:1px dashed var(--line);
  }
  .info-row .lbl{ color:var(--muted); font-weight:600; display:flex; align-items:center; gap:6px; }
  .info-row .lbl i{ width:13px; text-align:center; color:var(--primary); opacity:.8; font-size:11px; }
  .info-row .val{ font-weight:700; color:var(--ink); text-align:right; }

  .idbar{
    margin-top:auto; position:relative; background:var(--ink); color:#fff; text-align:center;
    padding:13px 12px 12px; overflow:hidden;
  }
  .idbar .barcode{
    height:18px; margin:0 auto 6px; width:82%; max-width:220px;
    background-image:repeating-linear-gradient(90deg,#fff 0 2px,transparent 2px 4px,#fff 4px 5px,transparent 5px 9px,#fff 9px 12px,transparent 12px 14px);
    opacity:.9;
  }
  .idbar .idnum{ font-family:'Courier New',monospace; font-size:15px; font-weight:800; letter-spacing:3px; }

  /* ══ BACK ══ */
  .back{ padding:34px 24px 24px; position:relative; }
  .back .wm{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    font-size:64px; font-weight:900; color:var(--primary); opacity:.035; letter-spacing:2px;
    transform:rotate(-18deg); pointer-events:none; user-select:none; white-space:nowrap;
  }
  .back .strip{ height:34px; background:repeating-linear-gradient(135deg,var(--ink) 0 10px,#2b1216 10px 20px); margin:-34px -24px 22px; }
  .back .title{
    font-size:12.5px; font-weight:800; color:var(--primary); text-transform:uppercase;
    letter-spacing:.6px; margin-bottom:10px; display:flex; align-items:center; gap:7px; position:relative; z-index:1;
  }
  .back .terms{ list-style:none; font-size:11px; color:#5a4249; line-height:1.7; position:relative; z-index:1; }
  .back .terms li{ margin-bottom:8px; padding-left:16px; position:relative; }
  .back .terms li::before{ content:"›"; position:absolute; left:0; color:var(--primary); font-weight:800; }

  .sign-row{ display:flex; justify-content:space-between; margin-top:28px; position:relative; z-index:1; gap:12px; }
  .sign{ text-align:center; font-size:10.5px; color:var(--muted); flex:1; font-weight:600; }
  .sign .box{
    height:46px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:6px;
  }
  .sign .box img{ max-height:44px; max-width:100%; object-fit:contain; }
  .sign .line{ width:100%; border-top:1.4px solid var(--ink); margin-bottom:6px; }
  .sign .missing{ font-size:9.5px; color:#c9a63a; font-style:italic; }

  .back .emergency{
    margin-top:26px; padding-top:14px; border-top:1px solid var(--line); text-align:center; position:relative; z-index:1;
  }
  .back .emergency .row{ font-size:10.5px; color:var(--muted); margin-bottom:4px; }
  .back .emergency .row b{ color:var(--ink); }
  .valid{ position:absolute; bottom:14px; left:0; right:0; text-align:center; font-size:10px; color:var(--muted); z-index:1; }

  @media print {
    @page{ size:auto; margin:10mm; }
    html,body{ background:#fff; }
    body{ padding:0; }
    .toolbar, .sig-alert{ display:none; }
    .page-wrap{ max-width:none; }
    .cards-row{ gap:24px; }
    .card{ box-shadow:none; page-break-inside:avoid; border:1px solid #eee; }

    /* Chrome/Safari/Firefox all strip backgrounds on print unless told
       otherwise — this is what made the printed card look flat/colorless
       compared to the on-screen preview. Force exact color output so the
       gradient header, dark ID strip, barcode, and back-side stripe all
       print exactly as previewed. */
    *{
      -webkit-print-color-adjust:exact !important;
      print-color-adjust:exact !important;
      color-adjust:exact !important;
    }
  }
</style>
</head>
<body>
<div class="page-wrap">

<div class="toolbar">
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print ID Card</button>
  <a class="btn-ghost" href="{{ route('admin.teachers.show', $teacher) }}"><i class="fas fa-arrow-left"></i> Back to Profile</a>
  <a class="btn-ghost" href="{{ route('admin.teachers.edit', $teacher) }}"><i class="fas fa-pen"></i> Edit / Add Signature</a>
</div>

@if(!$teacher->signature)
<div class="sig-alert">
  <i class="fas fa-signature"></i> No signature on file yet —
  <a href="{{ route('admin.teachers.edit', $teacher) }}">add one here</a> so it appears on the card.
</div>
@endif

<div class="cards-row">

{{-- FRONT --}}
<div class="card front">
  <div class="punch"></div>
  <div class="head">
    <div class="pattern"></div>
    <div class="ring"></div>
    <div class="brand"><i class="fas fa-graduation-cap"></i><span class="school">Acadrix EMS</span></div>
    <div class="school-name">Staff Identity Card</div>
    <div class="card-type">Faculty · {{ $teacher->status === 'active' ? 'Active' : 'Inactive' }}</div>
  </div>
  <div class="photo-wrap">
    @if($teacher->photo)
      <img src="{{ storage_asset($teacher->photo) }}" alt="{{ $teacher->name }}">
    @else
      <div class="initials">{{ strtoupper(substr($teacher->name, 0, 2)) }}</div>
    @endif
    <div class="status-dot" title="{{ ucfirst($teacher->status) }}"></div>
  </div>
  <div class="name">{{ $teacher->name }}</div>
  <div class="role">{{ $teacher->designation ?: ($teacher->subject->name ?? 'Faculty Member') }}</div>

  <div class="info">
    <div class="info-row"><span class="lbl"><i class="fas fa-building"></i>Department</span><span class="val">{{ $teacher->department ?: '—' }}</span></div>
    <div class="info-row"><span class="lbl"><i class="fas fa-phone"></i>Phone</span><span class="val">{{ $teacher->phone ?: '—' }}</span></div>
    <div class="info-row"><span class="lbl"><i class="fas fa-droplet"></i>Blood Group</span><span class="val">{{ $teacher->blood_group ?: '—' }}</span></div>
    <div class="info-row"><span class="lbl"><i class="fas fa-calendar"></i>Joined</span><span class="val">{{ $teacher->joining_date?->format('M Y') ?: '—' }}</span></div>
  </div>

  <div class="idbar">
    <div class="barcode"></div>
    <div class="idnum">{{ $teacher->employee_id }}</div>
  </div>
</div>

{{-- BACK --}}
<div class="card back">
  <div class="punch"></div>
  <div class="strip"></div>
  <div class="wm">ACADRIX</div>

  <div class="title"><i class="fas fa-shield-halved"></i> Terms of Use</div>
  <ul class="terms">
    <li>This card is the property of Acadrix EMS and must be surrendered upon request or on separation from service.</li>
    <li>This card must be carried at all times while on campus and produced on demand.</li>
    <li>Loss of this card should be reported to administration immediately.</li>
    <li>This card is non-transferable and valid only for the named holder.</li>
  </ul>

  <div class="sign-row">
    <div class="sign">
      <div class="box">
        @if($teacher->signature)
          <img src="{{ storage_asset($teacher->signature) }}" alt="Signature">
        @endif
      </div>
      <div class="line"></div>
      Holder's Signature
      @if(!$teacher->signature)<div class="missing">not on file</div>@endif
    </div>
    <div class="sign">
      <div class="box">
        @if($authSignature = \App\Models\Setting::get('authorized_signatory_signature'))
          <img src="{{ storage_asset($authSignature) }}" alt="Authorized Signatory Signature">
        @endif
      </div>
      <div class="line"></div>
      Authorized Signatory
      @if($authName = \App\Models\Setting::get('authorized_signatory_name'))
        <div style="font-size:9.5px;color:var(--ink);font-weight:700;margin-top:2px">{{ $authName }}</div>
        @if($authTitle = \App\Models\Setting::get('authorized_signatory_title'))
          <div style="font-size:9px;color:var(--muted)">{{ $authTitle }}</div>
        @endif
      @elseif(!$authSignature)
        <div class="missing">not on file</div>
      @endif
    </div>
  </div>

  <div class="emergency">
    <div class="row">Emergency Contact: <b>{{ $teacher->emergency_contact ?: $teacher->phone ?: '—' }}</b></div>
    <div class="row">CNIC: <b>{{ $teacher->cnic ?: '—' }}</b></div>
  </div>

  <div class="valid">Issued {{ now()->format('M d, Y') }} · Acadrix EMS</div>
</div>

</div>{{-- /cards-row --}}
</div>{{-- /page-wrap --}}

</body>
</html>
