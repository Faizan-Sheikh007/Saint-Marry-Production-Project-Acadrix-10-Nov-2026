@extends('layouts.auth')
@section('title', 'Staff Access')
@section('content')
<style>
  :root{ --gold:#c8973a; --gold-light:#eecb84; }
  html,body{height:100%}
  .gate-shell{
    position:fixed;inset:0;overflow:auto;display:flex;align-items:center;justify-content:center;padding:24px;
    font-family:'Outfit',sans-serif;
    background:
      radial-gradient(900px 560px at 50% -10%, rgba(28,58,134,.4), transparent 60%),
      radial-gradient(1000px 700px at 50% 115%, rgba(200,151,58,.14), transparent 60%),
      #0a0710;
  }
  .gate-shell::before{content:'';position:fixed;inset:0;pointer-events:none;opacity:.035;z-index:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}

  .gate-card{
    position:relative;z-index:1;width:100%;max-width:400px;padding:44px 38px 38px;border-radius:24px;
    background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.14);
    backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
    box-shadow:0 40px 90px -30px rgba(0,0,0,.75);
    text-align:center;
    animation:gate-in .5s cubic-bezier(.16,1,.3,1);
  }
  @keyframes gate-in{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}

  .gate-icon{width:64px;height:64px;border-radius:18px;margin:0 auto 22px;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,rgba(238,203,132,.18),rgba(200,151,58,.08));
    border:1px solid rgba(238,203,132,.3);color:var(--gold-light);}

  .gate-title{font-family:'Fraunces',serif;font-size:25px;font-weight:700;color:#fff;margin-bottom:8px}
  .gate-sub{font-size:13.5px;color:rgba(255,255,255,.55);line-height:1.6;margin-bottom:30px;max-width:300px;margin-left:auto;margin-right:auto}

  .pin-input{
    width:100%;text-align:center;letter-spacing:.5em;font-size:24px;font-weight:700;padding:15px 12px 15px 20px;
    border:1.5px solid rgba(255,255,255,.16);border-radius:14px;color:#fff;background:rgba(255,255,255,.05);
    outline:none;transition:border-color .15s,box-shadow .15s,background .15s;font-family:inherit;
  }
  .pin-input::placeholder{letter-spacing:.5em;color:rgba(255,255,255,.22)}
  .pin-input:focus{border-color:var(--gold-light);background:rgba(255,255,255,.09);box-shadow:0 0 0 4px rgba(238,203,132,.14)}

  .gate-error{color:#ff9797;font-size:13px;margin-top:12px}

  .gate-submit{
    width:100%;margin-top:22px;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,var(--gold-light) 0%,var(--gold) 100%);
    color:#2a1608;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:9px;
    box-shadow:0 12px 26px rgba(200,151,58,.28);transition:transform .15s,box-shadow .15s;
  }
  .gate-submit:hover{transform:translateY(-1px);box-shadow:0 16px 32px rgba(200,151,58,.4)}

  .gate-back{display:inline-flex;align-items:center;gap:6px;margin-top:26px;font-size:12.5px;color:rgba(255,255,255,.4);
    text-decoration:none;font-weight:500}
  .gate-back:hover{color:rgba(255,255,255,.7)}
</style>

<div class="gate-shell">
  <div class="gate-card">
    <div class="gate-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2.5"/><path d="M7.5 10V7a4.5 4.5 0 019 0v3"/><circle cx="12" cy="15" r="1.6" fill="currentColor" stroke="none"/></svg>
    </div>
    <h1 class="gate-title">Staff Access</h1>
    <p class="gate-sub">Enter your access PIN to continue. Your PIN determines which staff sign-in form you'll see next.</p>

    <form action="{{ route('staff.gate.verify') }}" method="POST" autocomplete="off">
      @csrf
      <input
        type="password"
        inputmode="numeric"
        name="pin"
        class="pin-input"
        placeholder="••••••"
        maxlength="32"
        autofocus
        autocomplete="off"
        required
      >
      @error('pin')<p class="gate-error">{{ $message }}</p>@enderror

      <button type="submit" class="gate-submit">
        Continue
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <a class="gate-back" href="{{ route('login') }}"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Back to main sign in</a>
  </div>
</div>
@endsection
