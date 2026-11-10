@extends('layouts.auth')
@section('title', ($roleLabel ?? 'Staff') . ' Sign In')
@php
  $role = $staffRole ?? null;
  switch ($role) {
      case 'admin':
          $roleLabel = 'Admin';
          $portalTag = 'Administrator Access';
          $headline  = 'Run the whole<br>institution, <em>calmly</em>.';
          $sub       = 'Admissions, staff, classes, examinations and finances — full oversight in one place.';
          $iconSvg   = '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/>';
          $theme     = ['dark'=>'#241537','mid'=>'#3a1c4d','bright'=>'#890c25'];
          break;
      case 'teacher':
          $roleLabel = 'Teacher';
          $portalTag = 'Teacher Access';
          $headline  = 'Your classroom,<br>always <em>on hand</em>.';
          $sub       = 'Attendance, gradebook, lesson materials and messages to parents — all in one place.';
          $iconSvg   = '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.3 2.7 3 6 3s6-1.7 6-3v-5"/><path d="M22 10v6"/>';
          $theme     = ['dark'=>'#0a1530','mid'=>'#14224d','bright'=>'#2451c9'];
          break;
      case 'accountant':
          $roleLabel = 'Accounts';
          $portalTag = 'Accounts Department';
          $headline  = 'Fees &amp; ledgers,<br>made <em>effortless</em>.';
          $sub       = 'Vouchers, ledgers and collections for your institute — organized and always up to date.';
          $iconSvg   = '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h3"/>';
          $theme     = ['dark'=>'#3d0511','mid'=>'#5e0819','bright'=>'#890c25'];
          break;
      default:
          $roleLabel = 'Staff';
          $portalTag = 'Staff Access';
          $headline  = 'Welcome back.';
          $sub       = 'Sign in to continue.';
          $iconSvg   = '<path d="M12 1v22M17 5.5H9a3.5 3.5 0 000 7h6a3.5 3.5 0 010 7H6"/>';
          $theme     = ['dark'=>'#241537','mid'=>'#3a1c4d','bright'=>'#890c25'];
  }
@endphp
@section('content')
@php
  $loginInstitutionLogo = \App\Models\Setting::get('institution_logo');
  $loginInstitutionName = \App\Models\Setting::get('institution_name');
  $loginInstitutionTagline = \App\Models\Setting::get('institution_tagline');
@endphp
<style>
  :root{ --gold:#c8973a; --gold-light:#eecb84; --p-dark:{{ $theme['dark'] }}; --p-mid:{{ $theme['mid'] }}; --p-bright:{{ $theme['bright'] }}; }
  html,body{height:100%}
  .sf-shell{
    position:fixed;inset:0;overflow-x:hidden;overflow-y:auto;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:56px 32px 36px;
    font-family:'Outfit',sans-serif;
    background:
      radial-gradient(1000px 620px at 10% -10%, color-mix(in srgb, var(--p-bright) 55%, transparent), transparent 60%),
      radial-gradient(900px 600px at 100% 8%, color-mix(in srgb, var(--gold) 22%, transparent), transparent 60%),
      radial-gradient(1200px 800px at 50% 115%, color-mix(in srgb, var(--p-mid) 45%, transparent), transparent 65%),
      var(--p-dark);
  }
  /* Consistent institution identity on admin, teacher and accounts sign-in. */
  .institution-identity{position:relative;z-index:2;width:100%;max-width:1040px;display:flex;
    flex-direction:column;align-items:center;justify-content:center;gap:12px;
    margin:0 auto 32px;padding:0 16px;flex-shrink:0;text-align:center;color:#fff}
  .institution-identity-logo{width:90px;height:90px;flex-shrink:0;object-fit:contain;display:block;
    filter:drop-shadow(0 5px 18px rgba(0,0,0,.3))}
  .institution-identity-text{display:flex;flex-direction:column;align-items:center;gap:10px;max-width:100%}
  .institution-identity-name{font-size:24px;font-weight:800;line-height:1.25;overflow-wrap:anywhere}
  .institution-identity-tagline{font-size:13px;line-height:1.5;color:rgba(255,255,255,.72);overflow-wrap:anywhere}
  @media(max-width:900px){.sf-shell{padding-top:38px}.institution-identity{margin-bottom:28px}}
  @media(max-width:480px){.sf-shell{padding:28px 0 36px}.institution-identity{padding:0 22px;gap:10px;margin-bottom:26px}
    .institution-identity-logo{width:64px;height:64px}.institution-identity-name{font-size:20px}
    .institution-identity-tagline{font-size:12px}}
  .sf-shell::before{content:'';position:fixed;inset:0;pointer-events:none;opacity:.04;z-index:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}

  .sf-card{
    position:relative;z-index:1;width:100%;max-width:1040px;min-height:600px;
    display:flex;border-radius:28px;overflow:hidden;
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16);
    backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
    box-shadow:0 50px 100px -35px rgba(0,0,0,.7);
    animation:sf-in .5s cubic-bezier(.16,1,.3,1);
  }
  @keyframes sf-in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  @media (max-width:900px){.sf-card{flex-direction:column;min-height:auto}}

  .sf-visual{position:relative;flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;
    padding:50px 46px;color:#fff;overflow:hidden;
    background:linear-gradient(165deg,var(--p-dark) 0%,var(--p-mid) 48%,#06040a 100%);}
  @media (max-width:900px){.sf-visual{min-height:240px;padding:34px}}
  .sf-visual::after{content:'';position:absolute;inset:0;pointer-events:none;
    background-image:radial-gradient(rgba(255,255,255,.09) 1px, transparent 1px);background-size:26px 26px;}
  .sfv-content{position:relative;z-index:1}
  .sfv-brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}
  .sfv-brand-logo{height:44px;width:auto;max-width:190px;object-fit:contain;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
  .sfv-brand-icon{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);
    display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px)}
  .sfv-brand-name{font-weight:800;font-size:19px}
  .sfv-badge{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;
    padding:8px 15px;border-radius:100px;margin-bottom:24px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.24);
    color:var(--gold-light);backdrop-filter:blur(8px)}
  .sfv-headline{font-family:'Fraunces',serif;font-size:36px;font-weight:700;line-height:1.18;max-width:420px;margin-bottom:14px}
  .sfv-headline em{font-style:italic;color:var(--gold-light)}
  .sfv-sub{font-size:15.5px;color:rgba(255,255,255,.76);max-width:400px;line-height:1.6;font-weight:300}
  .sfv-foot{position:relative;z-index:1;display:flex;gap:30px}
  @media (max-width:900px){.sfv-foot{display:none}}
  .sfv-stat-val{font-size:22px;font-weight:800}
  .sfv-stat-lbl{font-size:11.5px;color:rgba(255,255,255,.6);margin-top:2px}

  .sf-form-panel{flex:.92;display:flex;align-items:center;justify-content:center;padding:48px 42px;background:rgba(255,255,255,.045)}
  .sf-form-card{width:100%;max-width:380px}
  .sf-badge{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:var(--gold-light);background:rgba(238,203,132,.1);border:1px solid rgba(238,203,132,.28);
    padding:6px 14px;border-radius:100px;margin-bottom:16px}
  .sf-title{font-family:'Fraunces',serif;font-size:29px;font-weight:700;color:#fff;margin-bottom:6px}
  .sf-sub{font-size:14px;color:rgba(255,255,255,.62);margin-bottom:28px}

  .sf-field{margin-bottom:18px}
  .sf-label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.55);margin-bottom:8px}
  .sf-input-wrap{position:relative}
  .sf-input-wrap .ic{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gold-light);display:flex}
  .sf-input-wrap input{width:100%;padding:13px 16px 13px 42px;border:1.5px solid rgba(255,255,255,.14);border-radius:12px;
    font-size:14.5px;font-family:inherit;color:#fff;outline:none;transition:border-color .15s,box-shadow .15s,background .15s;background:rgba(255,255,255,.05)}
  .sf-input-wrap input::placeholder{color:rgba(255,255,255,.34)}
  .sf-input-wrap input:focus{border-color:var(--gold-light);background:rgba(255,255,255,.09);box-shadow:0 0 0 4px rgba(238,203,132,.14)}
  .sf-input-wrap .toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.45);cursor:pointer;background:none;border:none;padding:4px;display:flex}
  .sf-error{color:#ff9797;font-size:12.5px;margin-top:7px}

  .sf-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
  .sf-remember{display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);cursor:pointer}

  .sf-submit{width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%);
    color:#2a1608;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:9px;
    box-shadow:0 12px 26px rgba(200,151,58,.28);transition:transform .15s,box-shadow .15s}
  .sf-submit:hover{transform:translateY(-1px);box-shadow:0 16px 32px rgba(200,151,58,.4)}

  .sf-foot{text-align:center;margin-top:26px;font-size:12.5px;color:rgba(255,255,255,.42)}
  .sf-back{display:block;text-align:center;margin-top:16px;font-size:12.5px;color:rgba(255,255,255,.4);text-decoration:none}
  .sf-back:hover{color:rgba(255,255,255,.7)}

  @media (max-width:480px){
    .sf-shell{padding:28px 0 36px}
    .sf-card{border-radius:0;min-height:0}
    .sf-visual{padding:28px 22px 22px;min-height:0}
    .sfv-headline{font-size:24px}
    .sf-form-panel{padding:28px 22px 36px}
  }
</style>

<div class="sf-shell">
  @if($loginInstitutionLogo || $loginInstitutionName || $loginInstitutionTagline)
    <div class="institution-identity" aria-label="Institution information">
      @if($loginInstitutionLogo)
        <img class="institution-identity-logo" src="{{ storage_asset($loginInstitutionLogo) }}" alt="{{ $loginInstitutionName ?: 'Institution' }} logo">
      @endif
      @if($loginInstitutionName || $loginInstitutionTagline)
        <div class="institution-identity-text">
          @if($loginInstitutionName)<div class="institution-identity-name">{{ $loginInstitutionName }}</div>@endif
          @if($loginInstitutionTagline)<div class="institution-identity-tagline">{{ $loginInstitutionTagline }}</div>@endif
        </div>
      @endif
    </div>
  @endif
  <div class="sf-card">
    <div class="sf-visual">
      <div class="sfv-content">
        <div class="sfv-brand">
          <img src="{{ asset('logo.png') }}" alt="Acadrix" class="sfv-brand-logo">
        </div>
        <span class="sfv-badge"><i class="fas fa-shield-halved"></i> {{ $portalTag }}</span>
        <h1 class="sfv-headline">{!! $headline !!}</h1>
        <p class="sfv-sub">{{ $sub }}</p>
      </div>
      <div class="sfv-foot">
        <div><div class="sfv-stat-val">256-bit</div><div class="sfv-stat-lbl">Encryption</div></div>
        <div><div class="sfv-stat-val">PIN + Pwd</div><div class="sfv-stat-lbl">Two-step access</div></div>
      </div>
    </div>

    <div class="sf-form-panel">
      <div class="sf-form-card">
        <span class="sf-badge"><i class="fas fa-lock"></i>&nbsp; {{ $roleLabel }} Login</span>
        <h2 class="sf-title">{{ $roleLabel }} Sign In</h2>
        <p class="sf-sub">Restricted access &mdash; authorized {{ strtolower($roleLabel) }} accounts only.</p>

        <form action="{{ route('staff.login.submit') }}" method="POST" autocomplete="off">
          @csrf
          <div class="sf-field">
            <label class="sf-label">Email Address</label>
            <div class="sf-input-wrap">
              <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="M2 6.5l10 7 10-7"/></svg></span>
              <input type="email" name="email" value="{{ old('email') }}" placeholder="you@acadrix.com" required autofocus autocomplete="off">
            </div>
            @error('email')<p class="sf-error">{{ $message }}</p>@enderror
          </div>

          <div class="sf-field">
            <label class="sf-label">Password</label>
            <div class="sf-input-wrap">
              <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M7.5 10V7a4.5 4.5 0 019 0v3"/></svg></span>
              <input type="password" name="password" id="pwField" placeholder="••••••••" required autocomplete="new-password">
              <button type="button" class="toggle-pw" onclick="const f=document.getElementById('pwField');f.type=f.type==='password'?'text':'password';">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            @error('password')<p class="sf-error">{{ $message }}</p>@enderror
          </div>

          <div class="sf-row">
            <label class="sf-remember"><input type="checkbox" name="remember"> Remember me</label>
          </div>

          <button type="submit" class="sf-submit">
            Sign In
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <p class="sf-foot">Protected by 256-bit encryption &middot; PIN-gated access</p>
        <a class="sf-back" href="{{ route('login') }}">&larr; Back to main sign in</a>
      </div>
    </div>
  </div>
</div>
@endsection
