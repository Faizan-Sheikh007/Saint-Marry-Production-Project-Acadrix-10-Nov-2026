<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Timetable — {{ $heading }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
@php $isMergedPrint = (bool) ($mergedPrint ?? false); @endphp
<style>
  /* The normal timetable targets A4 landscape. The merged all-classes report
     uses a wider A3 landscape canvas plus compact cells so the weekly matrix
     is adjusted as a single printable page rather than one page per class. */
  @if($isMergedPrint)
    @page { size: A3 landscape; margin: 5mm; }
  @else
    @page { size: A4 landscape; margin: 8mm; }
  @endif

  .sheet, .sheet-inner { max-width: 1180px; }
  .tt-grid th, .tt-grid td { border-color: var(--line); }
  .tt-print-title {
    font-size: 27px; font-weight: 800; letter-spacing: 2.3px;
    text-transform: uppercase; color: var(--primary); margin-top: 7px;
  }
  .tt-context { text-align:center; margin:0 0 9px; }
  .tt-context-heading { font-size:14px; font-weight:700; color:var(--ink); }
  .tt-context-sub { font-size:10.5px; color:var(--muted); margin-top:2px; }
  .tt-print-meta { margin:0 0 10px; }
  .tt-print-meta .row { min-width:0; }
  .tt-print-meta .val { overflow-wrap:anywhere; }

  @if($isMergedPrint)
    .sheet { max-width:none; width:100%; }
    .sheet-inner { max-width:none; padding:10px 12px 12px; }
    .sheet-inner::before { inset:3px; }
    .letterhead .logo-wrap, .letterhead .spacer { width:48px; }
    .letterhead .logo-wrap .logo-fallback { width:44px; height:44px; font-size:14px; }
    .letterhead .inst-name { font-size:17px; }
    .letterhead .inst-affil { font-size:8.5px; }
    .tt-print-title { font-size:20px; margin-top:3px; letter-spacing:1.6px; }
    hr.rule { margin:7px 0; }
    .tt-context { margin-bottom:5px; }
    .tt-context-heading { font-size:11px; }
    .tt-context-sub { font-size:8px; }
    .meta-grid { font-size:9px; gap:2px 10px; }
    .tt-print-meta { margin-bottom:5px; }
    .tt-grid th { font-size:7px !important; padding:4px 3px !important; }
    .tt-grid td { padding:2px !important; }
    .tt-grid td:first-child { font-size:7.5px !important; padding:4px 2px !important; }
    .tt-grid-cell { padding:3px 4px !important; border-radius:3px !important; }
    .tt-grid-cell-stack { gap:2px !important; }
    .tt-grid-subject { font-size:7.8px !important; line-height:1.12 !important; }
    .tt-grid-secondary { font-size:6.7px !important; line-height:1.12 !important; }
    .tt-grid-days { font-size:5.8px !important; padding:1px 3px !important; }
    .tt-grid-break-label { font-size:7px !important; }
    .tt-grid-break-cell { padding:3px !important; }
    .foot-row { margin-top:12px; }
    .sign-block { min-width:150px; }
    .sign-line, .gen-date { font-size:8px; }
    .disclaimer { font-size:7px; margin-top:8px; padding-top:4px; }
  @endif

  @media print {
    .sheet-inner { break-inside:avoid-page; }
    @if($isMergedPrint)
      /* Chromium/Edge print engines support zoom and use it here only as a
         final safety margin for dense merged timetables. */
      .sheet-inner { zoom:.88; }
    @endif
  }
</style>
</head>
<body class="{{ $isMergedPrint ? 'tt-merged-print' : '' }}">

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="sheet">
<div class="sheet-inner">

  @php
    $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
    $instTagline = \App\Models\Setting::get('institution_tagline');
    $logoPath    = \App\Models\Setting::get('institution_logo');
  @endphp

  <div class="letterhead">
    <div class="logo-wrap">
      @if($logoPath)
        <img src="{{ storage_asset($logoPath) }}" alt="Logo">
      @else
        <div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $instName), 0, 2)) ?: 'IN' }}</div>
      @endif
    </div>
    <div class="center">
      <div class="inst-name">{{ $instName }}</div>
      @if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif
      <div class="tt-print-title">TimeTable</div>
    </div>
    <div class="spacer"></div>
  </div>

  <hr class="rule">

  <div class="tt-context">
    <div class="tt-context-heading">{{ $heading }}</div>
    @if(!empty($subheading))<div class="tt-context-sub">{{ $subheading }}</div>@endif
  </div>

  @if(!empty($meta))
    <div class="meta-grid tt-print-meta">
      @foreach($meta as $label => $value)
        <div class="row"><span class="lbl">{{ $label }}</span><span class="val">{{ $value }}</span></div>
      @endforeach
    </div>
  @endif

  @include('timetable._grid', ['items' => $items, 'context' => $context])

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>

  <div class="disclaimer">This is a system-generated report — for internal administrative use only.</div>

</div>
</div>

</body>
</html>
