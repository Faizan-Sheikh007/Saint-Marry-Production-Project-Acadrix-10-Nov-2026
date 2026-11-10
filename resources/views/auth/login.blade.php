@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
@php
  // Share the institution settings with both the welcome and login form.
  $loginInstitutionLogo = \App\Models\Setting::get('institution_logo');
  $loginInstitutionName = \App\Models\Setting::get('institution_name');
  $loginInstitutionTagline = \App\Models\Setting::get('institution_tagline');
@endphp
<style>
  :root{
    --gold:#c8973a; --gold-light:#eecb84;
    --maroon:#890c25; --maroon-dark:#3d0511; --maroon-mid:#5e0819;
    --navy:#0f2050; --navy-light:#1c3a86;
  }
  html,body{height:100%}

  /* ═══════════════════ Entry splash (shows for 5s) ═══════════════════ */
  #entry-splash{
    position:fixed;inset:0;z-index:10000;
    display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px;
    background:
      radial-gradient(900px 520px at 12% -8%, rgba(179,18,58,.5), transparent 60%),
      radial-gradient(800px 520px at 92% 6%, rgba(28,58,134,.42), transparent 60%),
      radial-gradient(1000px 700px at 50% 112%, rgba(200,151,58,.16), transparent 60%),
      #0f0a0d;
    transition:opacity .6s ease, visibility .6s ease;
    font-family:'Outfit',sans-serif;
  }
  #entry-splash.hidden{opacity:0;visibility:hidden;pointer-events:none}
  #entry-splash::before{content:'';position:absolute;inset:0;pointer-events:none;opacity:.04;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}
  .splash-ring-wrap{position:relative;width:132px;height:132px;display:flex;align-items:center;justify-content:center;margin-bottom:18px}
  .splash-ring{position:absolute;border-radius:50%;border:1.5px solid rgba(255,255,255,.14)}
  .splash-ring.r1{width:100px;height:100px;border-top-color:var(--gold-light);animation:splash-spin 2.4s linear infinite}
  .splash-ring.r2{width:130px;height:130px;border-style:dashed;border-color:rgba(255,255,255,.12);animation:splash-spin 5.2s linear infinite reverse}
  @keyframes splash-spin{to{transform:rotate(360deg)}}
  .splash-logo{width:74px;filter:drop-shadow(0 10px 24px rgba(0,0,0,.45));animation:splash-pulse 1.6s ease-in-out infinite}
  @keyframes splash-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
  .splash-title{font-family:'Fraunces',serif;font-weight:700;font-size:26px;color:#fff;letter-spacing:.01em;margin-top:4px}
  .splash-sub{font-size:12.5px;color:rgba(255,255,255,.6);letter-spacing:.14em;text-transform:uppercase;margin-top:6px}
  .splash-bar{width:220px;height:4px;border-radius:4px;overflow:hidden;background:rgba(255,255,255,.14);margin-top:22px}
  .splash-bar-fill{height:100%;width:0%;border-radius:4px;background:linear-gradient(90deg,var(--gold-light),#fff);animation:splash-fill 5s linear forwards}
  @keyframes splash-fill{from{width:0%}to{width:100%}}

  /* ═══════════════════ Main portal card (glass) ═══════════════════ */
  .portal-shell{
    position:fixed;inset:0;overflow-x:hidden;overflow-y:auto;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:0;padding:56px 32px 36px;
    font-family:'Outfit',sans-serif;
    background:
      radial-gradient(1000px 620px at 10% -10%, rgba(179,18,58,.42), transparent 60%),
      radial-gradient(900px 600px at 100% 8%, rgba(200,151,58,.2), transparent 60%),
      radial-gradient(1200px 800px at 50% 115%, rgba(28,58,134,.32), transparent 65%),
      #0f0a0d;
    opacity:0; transform:scale(.98); transition:opacity .7s ease, transform .7s cubic-bezier(.16,1,.3,1);
  }
  .portal-shell.revealed{opacity:1;transform:scale(1)}
  /* Settings-driven institution header, centered above the sign-in card. */
  .institution-identity{
    position:relative;z-index:2;width:100%;max-width:1040px;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:12px;margin:0 auto 32px;padding:0 16px;flex-shrink:0;
    text-align:center;color:#fff;
  }
  .institution-identity-logo{
    width:90px;height:90px;flex-shrink:0;object-fit:contain;display:block;
    filter:drop-shadow(0 5px 18px rgba(0,0,0,.3));
  }
  .institution-identity-text{display:flex;flex-direction:column;align-items:center;gap:10px;max-width:100%}
  .institution-identity-name{font-size:24px;font-weight:800;line-height:1.25;overflow-wrap:anywhere}
  .institution-identity-tagline{font-size:13px;line-height:1.5;color:rgba(255,255,255,.72);overflow-wrap:anywhere}
  @media (max-width:900px){
    .portal-shell{padding-top:38px}
    .institution-identity{margin:0 auto 28px}
  }
  @media (max-width:480px){
    .institution-identity{margin:0 auto 26px;padding:0 22px;gap:10px}
    .institution-identity-logo{width:64px;height:64px}
    .institution-identity-name{font-size:20px}
    .institution-identity-tagline{font-size:12px}
  }

  .portal-shell::before{content:'';position:fixed;inset:0;pointer-events:none;opacity:.04;z-index:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}

  .portal-card{
    position:relative;z-index:1;width:100%;max-width:1040px;min-height:600px;
    display:flex;border-radius:28px;overflow:hidden;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.16);
    backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
    box-shadow:0 50px 100px -35px rgba(0,0,0,.7);
  }
  @media (max-width:900px){.portal-card{flex-direction:column;min-height:auto}}

  /* left visual */
  .portal-visual{position:relative;flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;
    padding:50px 46px;color:#fff;overflow:hidden;
    background:linear-gradient(165deg,var(--maroon-dark) 0%,var(--maroon-mid) 46%,var(--navy) 100%);}
  @media (max-width:900px){.portal-visual{min-height:240px;padding:34px}}
  .portal-visual::after{content:'';position:absolute;inset:0;pointer-events:none;
    background-image:radial-gradient(rgba(255,255,255,.09) 1px, transparent 1px);background-size:26px 26px;}
  .pv-content{position:relative;z-index:1}
  .pv-brand{display:flex;align-items:center;gap:12px;margin-bottom:26px}
  .pv-brand-logo{height:44px;width:auto;max-width:190px;object-fit:contain;filter:drop-shadow(0 4px 14px rgba(0,0,0,.35))}
  .pv-brand-icon{width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);
    display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px)}
  .pv-brand-name{font-weight:800;font-size:19px;letter-spacing:.01em}
  .pv-badge{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;
    padding:8px 15px;border-radius:100px;margin-bottom:24px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.24);
    color:var(--gold-light);backdrop-filter:blur(8px)}
  .pv-headline{font-family:'Fraunces',serif;font-size:36px;font-weight:700;line-height:1.18;max-width:420px;margin-bottom:14px}
  .pv-headline em{font-style:italic;color:var(--gold-light)}
  .pv-sub{font-size:15.5px;color:rgba(255,255,255,.76);max-width:400px;line-height:1.6;margin-bottom:32px;font-weight:300}
  .pv-list{list-style:none;display:flex;flex-direction:column;gap:14px}
  .pv-list li{display:flex;align-items:center;gap:12px;font-size:14.5px;color:rgba(255,255,255,.9)}
  .pv-list .fi{width:26px;height:26px;border-radius:50%;flex-shrink:0;background:rgba(238,203,132,.18);
    border:1px solid rgba(238,203,132,.4);display:flex;align-items:center;justify-content:center}
  .pv-foot{position:relative;z-index:1;display:flex;gap:30px}
  @media (max-width:900px){.pv-foot{display:none}}
  .pv-stat-val{font-size:22px;font-weight:800}
  .pv-stat-lbl{font-size:11.5px;color:rgba(255,255,255,.6);margin-top:2px}
  .page-credit{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;gap:10px;
    padding:10px 0 26px;font-family:'Outfit',sans-serif}
  .page-credit img{height:20px;width:auto;opacity:.92}
  .page-credit-text{font-size:12px;color:rgba(255,255,255,.45)}
  .page-credit-text a{color:rgba(255,255,255,.8);text-decoration:none;font-weight:600}
  .page-credit-text a:hover{color:var(--gold-light);text-decoration:underline}

  /* right form (glass) */
  .portal-form-panel{flex:.92;display:flex;align-items:center;justify-content:center;padding:48px 42px;
    background:rgba(255,255,255,.045);position:relative;}
  .portal-form-card{width:100%;max-width:380px}

  .pf-badge{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:var(--gold-light);background:rgba(238,203,132,.1);border:1px solid rgba(238,203,132,.28);
    padding:6px 14px;border-radius:100px;margin-bottom:16px}
  .pf-title{font-family:'Fraunces',serif;font-size:29px;font-weight:700;color:#fff;margin-bottom:6px;letter-spacing:-.01em}
  .pf-sub{font-size:14px;color:rgba(255,255,255,.62);margin-bottom:28px}

  .pf-field{margin-bottom:18px}
  .pf-label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.55);margin-bottom:8px}
  .pf-input-wrap{position:relative}
  .pf-input-wrap .ic{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--gold-light);display:flex}
  .pf-input-wrap input{
    width:100%;padding:13px 16px 13px 42px;border:1.5px solid rgba(255,255,255,.14);border-radius:12px;
    font-size:14.5px;font-family:inherit;color:#fff;outline:none;transition:border-color .15s, box-shadow .15s, background .15s;
    background:rgba(255,255,255,.05);
  }
  .pf-input-wrap input::placeholder{color:rgba(255,255,255,.34)}
  .pf-input-wrap input:focus{border-color:var(--gold-light);background:rgba(255,255,255,.09);
    box-shadow:0 0 0 4px rgba(238,203,132,.14)}
  .pf-input-wrap .toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);
    color:rgba(255,255,255,.45);cursor:pointer;background:none;border:none;padding:4px;display:flex}
  .pf-error{color:#ff9797;font-size:12.5px;margin-top:7px}

  .pf-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
  .pf-remember{display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);cursor:pointer}

  .pf-submit{
    width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%);
    color:#2a1608;font-size:15px;font-weight:700;font-family:inherit;
    cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;
    box-shadow:0 12px 26px rgba(200,151,58,.28);transition:transform .15s,box-shadow .15s;
  }
  .pf-submit:hover{transform:translateY(-1px);box-shadow:0 16px 32px rgba(200,151,58,.4)}

  .pf-foot{text-align:center;margin-top:26px;font-size:12.5px;color:rgba(255,255,255,.42)}

  .staff-link-wrap{text-align:center;margin-top:18px}
  .staff-link{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.32);
    text-decoration:none;font-weight:500;letter-spacing:.02em;transition:color .2s}
  .staff-link:hover{color:rgba(255,255,255,.65)}
  .staff-link i{font-size:10px}

  @media (max-width:480px){
    .portal-shell{padding:28px 0 0}
    .portal-card{border-radius:0;min-height:100vh}
    .portal-visual{padding:28px 22px 22px;min-height:0}
    .pv-headline{font-size:24px}
    .pv-sub{font-size:13.5px;margin-bottom:20px}
    .portal-form-panel{padding:28px 22px 36px}
  }
</style>

<div id="entry-splash">
  <div class="splash-ring-wrap">
    <div class="splash-ring r2"></div>
    <div class="splash-ring r1"></div>
    <img class="splash-logo" src="{{ asset('logo.png') }}" alt="Acadrix">
  </div>
  <div class="splash-title">Acadrix</div>
  <div class="splash-sub">Parent &amp; Student Portal</div>
  <div class="splash-bar"><div class="splash-bar-fill"></div></div>
</div>

{{-- Institution-branded welcome stays visible for 3 seconds after the entry splash. --}}
@include('partials.institution-login-welcome')

<div class="portal-shell" id="portal-shell">
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
  <div class="portal-card">
    <div class="portal-visual">
      <div class="pv-content">
        <div class="pv-brand">
          <img src="{{ asset('logo.png') }}" alt="Acadrix" class="pv-brand-logo">
        </div>
        <span class="pv-badge"><i class="fas fa-house-user"></i> Parent &amp; Student Portal</span>
        <h1 class="pv-headline">Stay close to<br>every <em>milestone</em>.</h1>
        <p class="pv-sub">Attendance, results, fee status and messages from school — all in one calm, secure place.</p>
        <ul class="pv-list">
          <li><span class="fi"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#eecb84" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> Real-time attendance &amp; results</li>
          <li><span class="fi"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#eecb84" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> Fee vouchers &amp; payment history</li>
          <li><span class="fi"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#eecb84" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> Direct messages from teachers</li>
        </ul>
      </div>
      <div class="pv-foot">
        <div><div class="pv-stat-val">256-bit</div><div class="pv-stat-lbl">Encryption</div></div>
        <div><div class="pv-stat-val">24/7</div><div class="pv-stat-lbl">Access</div></div>
      </div>

    </div>

    <div class="portal-form-panel">
      <div class="portal-form-card">
        <span class="pf-badge"><i class="fas fa-lock"></i>&nbsp; Secure Sign In</span>
        <h2 class="pf-title">Welcome back</h2>
        <p class="pf-sub">Sign in with the email &amp; password issued by your school.</p>

        <form action="{{ route('login.submit') }}" method="POST" autocomplete="off">
          @csrf
          <div class="pf-field">
            <label class="pf-label">Email Address</label>
            <div class="pf-input-wrap">
              <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="M2 6.5l10 7 10-7"/></svg></span>
              <input type="email" name="email" value="{{ old('email') }}" placeholder="you@acadrix.com" required autofocus autocomplete="off">
            </div>
            @error('email')<p class="pf-error">{{ $message }}</p>@enderror
          </div>

          <div class="pf-field">
            <label class="pf-label">Password</label>
            <div class="pf-input-wrap">
              <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M7.5 10V7a4.5 4.5 0 019 0v3"/></svg></span>
              <input type="password" name="password" id="pwField" placeholder="••••••••" required autocomplete="new-password">
              <button type="button" class="toggle-pw" onclick="const f=document.getElementById('pwField');f.type=f.type==='password'?'text':'password';">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            @error('password')<p class="pf-error">{{ $message }}</p>@enderror
          </div>

          <div class="pf-row">
            <label class="pf-remember">
              <input type="checkbox" name="remember"> Remember me
            </label>
          </div>

          <button type="submit" class="pf-submit">
            Sign In
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <p class="pf-foot">Protected by 256-bit encryption</p>

        <div class="staff-link-wrap">
          <a class="staff-link" href="{{ route('staff.gate') }}">Login as Staff <i class="fas fa-chevron-right"></i></a>
        </div>
      </div>
    </div>
  </div>

  <div class="page-credit">
    <img src="{{ asset('cyberorion.png') }}" alt="CyberOrion">
    <span class="page-credit-text">Designed &amp; Developed by <a href="https://cyberorion.pk" target="_blank" rel="noopener">CyberOrion</a></span>
  </div>
</div>

<script>
(function () {
  var splash = document.getElementById('entry-splash');
  var welcome = document.getElementById('institution-login-welcome');
  var shell = document.getElementById('portal-shell');
  var finished = false;
  var entryTimer;

  function finishIntro() {
    if (finished) return;
    finished = true;
    window.clearTimeout(entryTimer);
    if (splash) splash.classList.add('hidden');
    if (welcome) {
      welcome.classList.remove('active');
      welcome.setAttribute('aria-hidden', 'true');
      window.setTimeout(function () { if (welcome.parentNode) welcome.remove(); }, 550);
    }
    if (shell) shell.classList.add('revealed');
  }

  function showInstitutionWelcome() {
    if (finished) return;
    if (splash) splash.classList.add('hidden');
    if (!welcome) { finishIntro(); return; }
    welcome.setAttribute('aria-hidden', 'false');
    welcome.classList.add('active');

    // The 3-second countdown starts after the overlay has faded in.
    window.setTimeout(function () {
      if (finished) return;
      welcome.classList.add('started');
      window.setTimeout(finishIntro, 3000);
    }, 420);
  }

  // Failed sign-in should show the validation errors immediately rather than
  // forcing the student to wait through another splash and welcome.
  var hasLoginError = @json($errors->any());
  if (hasLoginError) {
    finishIntro();
  } else {
    // Existing splash is 5s; branded welcome is stable for the next 3s.
    entryTimer = window.setTimeout(showInstitutionWelcome, 5000);
  }

  // Browser bfcache restores an already-loaded page; don't replay the intro.
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) finishIntro();
  });
})();
</script>
@endsection
