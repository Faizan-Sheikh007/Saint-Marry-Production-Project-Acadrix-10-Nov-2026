@php
  $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $campusName  = \App\Models\Setting::get('campus_name');
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
    @if($campusName)<div class="inst-address">{{ $campusName }}</div>@endif
    <div class="doc-name">{{ $reportTitle }}</div>
    @if(!empty($reportSubtitle))<div class="doc-sub">{{ $reportSubtitle }}</div>@endif
  </div>
  <div class="spacer"></div>
</div>
<hr class="rule">
@if(!empty($metaLines))
<div class="meta-grid">
  @foreach($metaLines as [$label, $value])
    <div class="row"><span class="lbl">{{ $label }}</span><span class="val">{{ $value }}</span></div>
  @endforeach
</div>
@endif
