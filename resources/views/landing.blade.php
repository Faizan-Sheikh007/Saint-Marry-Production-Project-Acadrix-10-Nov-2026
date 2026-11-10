<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acadrix — University Management &amp; Accounts</title>
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="theme-color" content="#890c25">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Acadrix">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  :root{
    --maroon:#890c25; --maroon-dark:#4a0716; --maroon-light:#b3123a;
    --navy:#0f2050; --navy-light:#1c3a86;
    --gold:#c8973a; --gold-light:#eecb84;
    --ink:#0f0a0d; --paper:#faf7f2;
    --glass:rgba(255,255,255,.06); --glass-brd:rgba(255,255,255,.14);
  }
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{height:100%}
  body{
    font-family:'Outfit',sans-serif; color:var(--paper);
    background:
      radial-gradient(900px 500px at 8% -5%, rgba(179,18,58,.45), transparent 60%),
      radial-gradient(800px 500px at 95% 8%, rgba(28,58,134,.4), transparent 60%),
      radial-gradient(1000px 700px at 50% 110%, rgba(200,151,58,.14), transparent 60%),
      var(--ink);
    overflow-x:hidden;
  }
  .grain{position:fixed;inset:0;pointer-events:none;z-index:5;opacity:.035;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");}

  /* ── Top mark ───────────────────────────────────── */
  .topbar{position:relative;z-index:3;display:flex;align-items:center;justify-content:space-between;
    padding:30px clamp(20px,5vw,64px);}
  .brand{display:flex;align-items:center;gap:13px;font-family:'Fraunces',serif;font-weight:700;font-size:23px;letter-spacing:.01em}
  .brand-mark{width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,var(--gold-light),var(--gold));
    display:flex;align-items:center;justify-content:center;color:var(--maroon-dark);font-size:19px;font-weight:800;font-family:'Outfit',sans-serif;
    box-shadow:0 8px 20px rgba(200,151,58,.35)}
  .topbar a.loginlink{color:rgba(250,247,242,.85);text-decoration:none;font-size:15px;font-weight:600;
    border:1px solid var(--glass-brd);background:var(--glass);backdrop-filter:blur(10px);
    padding:11px 24px;border-radius:100px;transition:.25s}
  .topbar a.loginlink:hover{color:var(--ink);background:var(--gold-light);border-color:var(--gold-light)}

  /* ── Hero ───────────────────────────────────────── */
  .hero{position:relative;z-index:2;text-align:center;padding:min(6vh,64px) 24px 12px;}
  .eyebrow{font-size:13px;letter-spacing:.24em;text-transform:uppercase;color:var(--gold-light);
    font-weight:700;margin-bottom:24px;display:inline-flex;align-items:center;gap:12px}
  .eyebrow::before,.eyebrow::after{content:'';width:32px;height:1px;background:rgba(238,203,132,.5)}
  h1.hero-title{font-family:'Fraunces',serif;font-weight:700;line-height:1.06;
    font-size:clamp(40px,6.6vw,84px);max-width:1000px;margin:0 auto 24px;letter-spacing:-.01em}
  h1.hero-title .accent{font-style:italic;color:var(--gold-light)}
  .hero-sub{max-width:640px;margin:0 auto;color:rgba(250,247,242,.72);font-size:19px;line-height:1.65;font-weight:300}

  /* ── The choice ─────────────────────────────────── */
  .choice-wrap{position:relative;z-index:2;max-width:1200px;margin:min(6vh,56px) auto 0;padding:0 clamp(20px,4vw,48px);
    display:grid;grid-template-columns:1fr 1fr;gap:28px;}
  @media (max-width:900px){.choice-wrap{grid-template-columns:1fr}}

  .portal-card{position:relative;border-radius:26px;overflow:hidden;min-height:480px;
    display:flex;flex-direction:column;justify-content:flex-end;
    background:var(--glass);border:1px solid var(--glass-brd);backdrop-filter:blur(18px);
    box-shadow:0 40px 80px -30px rgba(0,0,0,.65);
    transition:transform .45s cubic-bezier(.16,1,.3,1), box-shadow .45s, border-color .45s;}
  .portal-card:hover{transform:translateY(-8px);border-color:rgba(238,203,132,.4)}
  .portal-card:hover .portal-photo{transform:scale(1.06)}
  .portal-photo{position:absolute;inset:0;background-size:cover;background-position:center;
    transition:transform 1.2s cubic-bezier(.16,1,.3,1);filter:saturate(.85) contrast(1.05)}
  .portal-shade{position:absolute;inset:0;
    background:linear-gradient(180deg, rgba(15,10,13,.1) 0%, rgba(15,10,13,.6) 52%, rgba(10,6,9,.95) 100%);}
  .portal-content{position:relative;z-index:1;padding:38px 40px 40px;}
  .portal-tag{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
    padding:8px 16px;border-radius:100px;margin-bottom:20px;backdrop-filter:blur(8px)}
  .tag-ems{background:rgba(28,58,134,.35);color:#a9c0ff;border:1px solid rgba(169,192,255,.4)}
  .tag-accounts{background:rgba(200,151,58,.24);color:var(--gold-light);border:1px solid rgba(238,203,132,.4)}
  .portal-title{font-family:'Fraunces',serif;font-weight:700;font-size:42px;color:#fff;margin-bottom:12px;letter-spacing:-.01em}
  .portal-desc{color:rgba(250,247,242,.78);font-size:16.5px;line-height:1.6;max-width:420px;margin-bottom:26px;font-weight:300}
  .portal-cta{display:inline-flex;align-items:center;gap:10px;color:#1c1210;font-weight:700;font-size:15.5px;
    background:linear-gradient(135deg,var(--gold-light),var(--gold));padding:14px 26px;border-radius:100px;
    box-shadow:0 10px 26px rgba(200,151,58,.3);transition:.25s}
  .portal-card:hover .portal-cta{gap:14px;transform:translateX(2px);box-shadow:0 14px 32px rgba(200,151,58,.42)}
  .portal-cta i{font-size:13px}

  /* ── Feature strip ──────────────────────────────── */
  .feature-strip{position:relative;z-index:2;max-width:1200px;margin:44px auto 0;padding:0 clamp(20px,4vw,48px);
    display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
  @media (max-width:900px){.feature-strip{grid-template-columns:1fr 1fr}}
  @media (max-width:520px){.feature-strip{grid-template-columns:1fr}}
  .feature-pill{display:flex;align-items:flex-start;gap:14px;padding:20px 20px;border-radius:18px;
    background:var(--glass);border:1px solid var(--glass-brd);backdrop-filter:blur(14px);}
  .feature-ic{flex-shrink:0;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    background:rgba(238,203,132,.14);border:1px solid rgba(238,203,132,.3);color:var(--gold-light);font-size:16px}
  .feature-h{font-size:15px;font-weight:700;color:#fff;margin-bottom:3px}
  .feature-p{font-size:13px;color:rgba(250,247,242,.6);line-height:1.5}

  /* ── Footer strip ───────────────────────────────── */
  .foot{position:relative;z-index:2;text-align:center;padding:48px 24px 56px;color:rgba(250,247,242,.5);
    font-size:14px;letter-spacing:.02em;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap}
  .foot a{color:var(--gold-light);text-decoration:none;font-weight:600}
  .foot .dot{opacity:.4}
</style>
</head>
<body>
<div class="grain"></div>

<header class="topbar">
  <div class="brand"><span class="brand-mark">A</span> Acadrix</div>
  <a class="loginlink" href="{{ route('login') }}">Sign in <i class="fas fa-arrow-right" style="margin-left:8px;font-size:12px"></i></a>
</header>

<section class="hero">
  <div class="eyebrow">One Campus, One Ledger</div>
  <h1 class="hero-title">One Platform. <span class="accent">Every Record.</span></h1>
  <p class="hero-sub">Acadrix keeps your institution and its finances on the same page — literally the same student record. Choose where you'd like to go.</p>
</section>

<div class="choice-wrap">
  <a href="{{ route('login') }}" class="portal-card">
    <div class="portal-photo" style="background-image:url('https://images.unsplash.com/photo-1741637647629-e111d70f1638?w=1200&q=80&auto=format&fit=crop')"></div>
    <div class="portal-shade"></div>
    <div class="portal-content">
      <span class="portal-tag tag-ems"><i class="fas fa-graduation-cap"></i> University Management</span>
      <div class="portal-title">EMS</div>
      <p class="portal-desc">Admissions, classes, attendance, examinations, LMS, HR &amp; every academic record — for admins, teachers, students and parents.</p>
      <span class="portal-cta">Enter EMS <i class="fas fa-arrow-right"></i></span>
    </div>
  </a>

  <a href="{{ route('accounts.login') }}" class="portal-card">
    <div class="portal-photo" style="background-image:url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&q=80&auto=format&fit=crop')"></div>
    <div class="portal-shade"></div>
    <div class="portal-content">
      <span class="portal-tag tag-accounts"><i class="fas fa-file-invoice-dollar"></i> Accounts Management</span>
      <div class="portal-title">Accounts</div>
      <p class="portal-desc">Fee heads, installment plans, vouchers, payments, ledger &amp; reports — one source of truth shared straight from EMS.</p>
      <span class="portal-cta">Enter Accounts <i class="fas fa-arrow-right"></i></span>
    </div>
  </a>
</div>

<div class="feature-strip">
  <div class="feature-pill">
    <div class="feature-ic"><i class="fas fa-shield-halved"></i></div>
    <div><div class="feature-h">Unified Student Record</div><div class="feature-p">One student. One record. All departments.</div></div>
  </div>
  <div class="feature-pill">
    <div class="feature-ic"><i class="fas fa-arrows-rotate"></i></div>
    <div><div class="feature-h">Real-time Sync</div><div class="feature-p">Data flows in real-time across EMS &amp; Accounts.</div></div>
  </div>
  <div class="feature-pill">
    <div class="feature-ic"><i class="fas fa-lock"></i></div>
    <div><div class="feature-h">Secure &amp; Reliable</div><div class="feature-p">Enterprise-grade security you can trust.</div></div>
  </div>
  <div class="feature-pill">
    <div class="feature-ic"><i class="fas fa-chart-column"></i></div>
    <div><div class="feature-h">Smart Reports</div><div class="feature-p">Insights that help you decide, faster.</div></div>
  </div>
</div>

<p class="foot"><i class="fas fa-users" style="opacity:.6"></i> Students and parents get read-only fee visibility automatically — no separate login. <span class="dot">·</span> <a href="{{ route('login') }}">Sign in</a></p>

@include('partials.pwa-install-prompt')
</body>
</html>
