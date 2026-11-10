{{--
    Acadrix OriByte Banners — include at top of layouts/app.blade.php
    Include with: @include('partials.oribyte-banners')
    These banners render automatically from session data set by CheckOriByteLicense middleware.
--}}

{{-- ── License expiry warning ─────────────────────────────────────────── --}}
@if(session('license_warning'))
<div style="background:#7c2d0a;border-bottom:1px solid #a03a0e;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Outfit',sans-serif;font-size:13px;color:#fde8d8">
    <span>⚠ {{ session('license_warning') }}</span>
    <a href="https://acadrix.cloud/pricing" target="_blank" style="background:#ea580c;color:#fff;padding:5px 14px;border-radius:6px;text-decoration:none;font-weight:600;font-size:12px;white-space:nowrap">Renew Now</a>
</div>
@endif

{{-- ── Control Panel announcement ──────────────────────────────────────── --}}
@if(session('acadrix_announcement'))
@php $ann = session('acadrix_announcement'); @endphp
<div style="background:{{ $ann['type'] === 'warning' ? '#713f12' : ($ann['type'] === 'error' ? '#7f1d1d' : '#1e3a5f') }};border-bottom:1px solid rgba(255,255,255,.1);padding:10px 20px;display:flex;align-items:center;gap:10px;font-family:'Outfit',sans-serif;font-size:13px;color:#fff">
    <span style="font-size:16px">{{ $ann['type'] === 'warning' ? '⚠' : ($ann['type'] === 'error' ? '🚨' : 'ℹ') }}</span>
    <span><strong>{{ $ann['title'] ?? 'Notice' }}:</strong> {{ $ann['message'] ?? '' }}</span>
</div>
@endif

{{-- ── Plan limit warnings (from usage sync) ───────────────────────────── --}}
@if(session('acadrix_warnings'))
@foreach(session('acadrix_warnings') as $warning)
<div style="background:#451a03;border-bottom:1px solid #78350f;padding:8px 20px;font-family:'Outfit',sans-serif;font-size:12.5px;color:#fde68a">
    ⚡ {{ $warning }}
</div>
@endforeach
@endif

{{-- ── Mandatory update notice ──────────────────────────────────────────── --}}
@if(session('acadrix_mandatory_update'))
@php $upd = session('acadrix_mandatory_update'); @endphp
<div style="background:#1e1b4b;border-bottom:1px solid #312e81;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Outfit',sans-serif;font-size:13px;color:#c7d2fe">
    <span>🔄 A new version of Acadrix is available: <strong>v{{ $upd['version'] ?? '' }}</strong>. Update required to continue.</span>
    @if(!empty($upd['download_url']))
    <a href="{{ $upd['download_url'] }}" style="background:#4f46e5;color:#fff;padding:5px 14px;border-radius:6px;text-decoration:none;font-weight:600;font-size:12px;white-space:nowrap">Update Now</a>
    @endif
</div>
@endif

{{-- ── Optional update (non-mandatory) ────────────────────────────────── --}}
@if(session('acadrix_update_available') && !session('acadrix_mandatory_update'))
@php $upd = session('acadrix_update_available'); @endphp
<div style="background:#052e16;border-bottom:1px solid #14532d;padding:8px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Outfit',sans-serif;font-size:12.5px;color:#bbf7d0">
    <span>✨ Acadrix v{{ $upd['version'] ?? '' }} is available.</span>
    @if(!empty($upd['download_url']))
    <a href="{{ $upd['download_url'] }}" style="color:#4ade80;font-weight:600;text-decoration:none;font-size:12px">Download →</a>
    @endif
</div>
@endif
