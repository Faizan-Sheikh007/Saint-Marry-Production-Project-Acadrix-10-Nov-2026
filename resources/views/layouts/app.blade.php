<!DOCTYPE html>
<html lang="en-GB" data-theme="">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="theme-color" content="#890c25">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Acadrix">
@php
  // ── Work out which "section" of the app the logged-in user is actually
  // looking at, so the sidebar/branding matches the page instead of always
  // showing the Accounts nav.
  //
  // NOTE: this must key off the user's *role*, not the URL. A handful of
  // routes shared between Admin and Accounts (vouchers, fee-assignments,
  // settings, etc.) live under the `/accounts/*` prefix for historical
  // reasons, but Admin reaches them entirely from their own EMS ('web')
  // session and never needs (or should see) the Accounts sidebar/branding.
  // Only a user actually signed in as an accountant is "in Accounts".
  $__role = auth()->user()?->role;
  $__inAccounts = $__role === 'accountant';

  $__sidebarPartial = match(true) {
      $__inAccounts            => 'partials.sidebar',
      $__role === 'admin'      => 'partials.sidebar-admin',
      $__role === 'teacher'    => 'partials.sidebar-teacher',
      $__role === 'student'    => 'partials.sidebar-student',
      $__role === 'parent'     => 'partials.sidebar-parent',
      default                  => 'partials.sidebar',
  };

  $__sectionLabel = match(true) {
      $__inAccounts                              => 'Acadrix Accounts',
      in_array($__role, ['admin','teacher'])     => 'Acadrix EMS',
      default                                     => 'Acadrix',
  };
@endphp
<title>@yield('title','Dashboard') — {{ $__sectionLabel }}</title>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
  .gs-results{position:absolute;top:calc(100% + 8px);left:0;width:440px;max-width:calc(100vw - 32px);max-height:70vh;overflow-y:auto;
    background:var(--surface,var(--card,#fff));border:1px solid var(--border,#e2e8f0);border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.18);z-index:1100;padding:6px}
  .gs-group-title{font-size:10.5px;font-weight:800;letter-spacing:.6px;color:var(--text-muted,#7a5057);padding:10px 10px 4px;text-transform:uppercase}
  .gs-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;color:var(--text,#222);text-decoration:none;font-size:13px}
  .gs-item i{width:16px;text-align:center;color:var(--primary,#890c25);position:static;transform:none;font-size:13px}
  .gs-item .gs-title{font-weight:600;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .gs-item .gs-meta{font-size:11.5px;color:var(--text-muted,#7a5057);max-width:45%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .gs-item:hover,.gs-item.active{background:rgba(137,12,37,.09)}
  .gs-empty{padding:16px;text-align:center;font-size:12.5px;color:var(--text-muted,#7a5057)}
  .gs-item mark{background:rgba(250,204,21,.45);color:inherit;border-radius:3px;padding:0 1px}
</style>
@stack('styles')
</head>
<body>

@include('partials.boot-loader', ['label' => $__sectionLabel])

<div class="app-layout">
  <!-- Mobile-only tap-to-close overlay behind the sidebar drawer -->
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
     <img src="{{ asset('logo.png') }}" alt="{{ $__sectionLabel }}">
    </div>
    <div class="sidebar-user">
      <div class="sidebar-avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
      <div style="flex:1;min-width:0">
        <div class="sidebar-user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ auth()->user()->name }}</div>
        <div class="sidebar-user-role">{{ auth()->user()->role ?? 'Staff' }}</div>
      </div>
      <span class="sidebar-user-dot" title="Online"></span>
    </div>

    @include($__sidebarPartial)

    <div class="sidebar-signout">
      <form action="{{ $__inAccounts ? route('accounts.logout') : route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;color:var(--sidebar-text);border-color:rgba(255,255,255,.15)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </button>
      </form>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-wrap">
    <!-- TOPBAR -->
    <header class="topbar">
      <button class="btn btn-ghost" style="display:none" id="menu-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
      <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" id="globalSearchInput" placeholder="Search students, vouchers, pages…  ( / )" autocomplete="off" spellcheck="false" aria-label="Global search" role="combobox" aria-expanded="false" aria-controls="globalSearchResults">
        <div id="globalSearchResults" class="gs-results" role="listbox" style="display:none"></div>
      </div>
      <div class="topbar-right">
        @if(auth()->user()?->isAdmin() || auth()->user()?->isTeacher())
        <button class="ai-insights-pill" onclick="toggleAiInsights()" title="AI Insights" id="aiInsightsBtn">
          <i class="fas fa-brain"></i> <span class="ai-insights-pill-label">AI Insights</span> <span class="ai-insights-pill-dot"></span>
        </button>
        @endif
        <button class="topbar-btn" onclick="toggleAiAssist()" title="AI Assist" id="aiAssistBtn"><i class="fas fa-robot"></i></button>
        <div style="position:relative">
          <button class="topbar-btn" onclick="toggleNotifBell()" title="Notifications" id="notifBellBtn">
            <i class="fas fa-bell"></i>
            <span id="notifBellDot" style="display:none;position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#dc2626"></span>
          </button>
          <div id="notifBellPanel" style="display:none;position:absolute;top:44px;right:0;width:320px;max-width:calc(100vw - 32px);max-height:400px;overflow-y:auto;
            background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.18);z-index:1000">
            <div style="display:flex;align-items:center;padding:12px 14px;border-bottom:1px solid var(--border,#e2e8f0)">
              <b style="font-size:13px">Notifications</b>
              <button onclick="markAllNotifsRead()" style="margin-left:auto;background:none;border:none;color:var(--primary,#890c25);font-size:11.5px;cursor:pointer">Mark all read</button>
            </div>
            <div id="notifBellList" style="font-size:12.5px"></div>
          </div>
        </div>
        <button class="topbar-btn" onclick="toggleTheme()" title="Toggle dark mode"><i class="fas fa-moon"></i></button>
        <div class="topbar-user">
          <div class="topbar-user-avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
          <div class="topbar-user-text">
            <div style="font-size:13px;font-weight:600">{{ auth()->user()->name }}</div>
            <div style="font-size:10px;color:var(--text-muted)">{{ auth()->user()->role ?? 'Staff' }}</div>
          </div>
        </div>
      </div>
    </header>

    <!-- AI ASSIST PANEL — available to every role -->
    <div id="aiAssistPanel" style="display:none;position:fixed;top:70px;right:24px;width:340px;max-width:calc(100vw - 32px);
      background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,.18);
      z-index:1000;padding:18px 20px;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <div style="width:32px;height:32px;border-radius:9px;background:rgba(137,12,37,.1);color:var(--primary,#890c25);display:flex;align-items:center;justify-content:center"><i class="fas fa-robot"></i></div>
        <div>
          <div style="font-weight:700;font-size:13.5px">AI Assist</div>
          <div style="font-size:11px;color:var(--text-muted,#7a5057)">Rule-based, runs instantly — no external API</div>
        </div>
        <button onclick="toggleAiAssist()" style="margin-left:auto;background:none;border:none;color:var(--text-muted,#7a5057);cursor:pointer;font-size:14px"><i class="fas fa-times"></i></button>
      </div>
      <button type="button" id="aiAssistGenBtn" onclick="runAiAssist()" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
        <i class="fas fa-wand-magic-sparkles"></i> Get today's briefing
      </button>
      <div id="aiAssistOutput" style="display:none;margin-top:12px;padding-top:12px;border-top:1px dashed var(--border,#e2e8f0);font-size:13px;line-height:1.6"></div>
    </div>

    @if(auth()->user()?->isAdmin() || auth()->user()?->isTeacher())
    {{-- AI INSIGHTS — real-time panel: health score, 7-day attendance, action required / positive indicators --}}
    <div id="aiInsightsOverlay" class="ai-ins-overlay" onclick="if(event.target===this) toggleAiInsights(false)">
      <div class="ai-ins-panel">
        <div class="ai-ins-head">
          <div class="ai-ins-head-top">
            <div class="ai-ins-brand"><span class="ai-ins-logo"><i class="fas fa-brain"></i></span>
              <div><div class="ai-ins-title">Acadrix AI</div><div class="ai-ins-sub">Intelligence Engine · Zero API Cost</div></div>
            </div>
            <button class="ai-ins-close" onclick="toggleAiInsights(false)"><i class="fas fa-times"></i></button>
          </div>
          <div class="ai-ins-score-box">
            <div class="ai-ins-score-num" id="aiInsScore">—</div>
            <div>
              <div class="ai-ins-score-label" id="aiInsScoreLabel">SCHOOL HEALTH SCORE</div>
              <div class="ai-ins-score-status" id="aiInsScoreStatus">Loading…</div>
              <div class="ai-ins-score-bar"><div class="ai-ins-score-bar-fill" id="aiInsScoreBar"></div></div>
            </div>
          </div>
          <div class="ai-ins-tabs">
            <button class="ai-ins-tab active" data-tab="briefing" onclick="aiInsSwitchTab('briefing')"><i class="fas fa-bolt"></i> Briefing</button>
            <button class="ai-ins-tab" data-tab="fees" onclick="aiInsSwitchTab('fees')"><i class="fas fa-coins"></i> Fees</button>
            <button class="ai-ins-tab" data-tab="attend" onclick="aiInsSwitchTab('attend')"><i class="fas fa-calendar-check"></i> Attend.</button>
            <button class="ai-ins-tab" data-tab="search" onclick="aiInsSwitchTab('search')"><i class="fas fa-search"></i> Search</button>
          </div>
        </div>

        <div class="ai-ins-body">
          {{-- BRIEFING TAB --}}
          <div class="ai-ins-tabpanel" data-tabpanel="briefing">
            <div class="ai-ins-card">
              <div class="ai-ins-card-label" id="aiInsDateLabel">—</div>
              <div class="ai-ins-greeting" id="aiInsGreeting">Good morning</div>
              <div class="ai-ins-chart-label">7-DAY ATTENDANCE</div>
              <div class="ai-ins-chart" id="aiInsChart"></div>
            </div>
            <div class="ai-ins-stats" id="aiInsStats"></div>
            <div class="ai-ins-list-card">
              <div class="ai-ins-list-head"><i class="fas fa-triangle-exclamation" style="color:#dc2626"></i> Action Required <span class="ai-ins-badge danger" id="aiInsAlertCount">0 ALERTS</span></div>
              <div id="aiInsAlerts"></div>
            </div>
            <div class="ai-ins-list-card">
              <div class="ai-ins-list-head"><i class="fas fa-circle-check" style="color:#16a34a"></i> Positive Indicators</div>
              <div id="aiInsPositives"></div>
            </div>
          </div>

          {{-- FEES TAB --}}
          <div class="ai-ins-tabpanel" data-tabpanel="fees" style="display:none">
            <div class="ai-ins-stats" id="aiInsFeesStats"></div>
            <div class="ai-ins-list-card" id="aiInsFeesDetail"></div>
          </div>

          {{-- ATTENDANCE TAB --}}
          <div class="ai-ins-tabpanel" data-tabpanel="attend" style="display:none">
            <div class="ai-ins-card">
              <div class="ai-ins-chart-label">7-DAY ATTENDANCE</div>
              <div class="ai-ins-chart" id="aiInsChart2"></div>
            </div>
          </div>

          {{-- SEARCH TAB --}}
          <div class="ai-ins-tabpanel" data-tabpanel="search" style="display:none">
            <input type="text" id="aiInsSearchInput" class="ai-ins-search-input" placeholder="Search students, teachers…" oninput="aiInsSearch(this.value)">
            <div id="aiInsSearchResults"></div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- CONTENT -->
    <main class="page-content">

      {{-- Success --}}
      @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:16px">
          <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
      @endif

      {{-- Error --}}
      @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:16px">
          <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
      @endif

      @php
        // Resolve from the route, never from a foreach variable left over by a
        // directory view (otherwise its last row could lock the whole page).
        $profileStatusStudent = request()->route('student');
        if (!$profileStatusStudent instanceof \App\Models\Student) $profileStatusStudent = null;
        $statusVoucher = request()->route('voucher');
        $statusFeeAssignment = request()->route('feeAssignment');
        if (!$profileStatusStudent && $statusVoucher instanceof \App\Models\Voucher) $profileStatusStudent = $statusVoucher->student;
        if (!$profileStatusStudent && $statusFeeAssignment instanceof \App\Models\FeeAssignment) $profileStatusStudent = $statusFeeAssignment->student;
        if (!$profileStatusStudent && request()->routeIs('vouchers.index') && ($mode ?? null) === 'student'
            && ($student ?? null) instanceof \App\Models\Student) $profileStatusStudent = $student;
      @endphp
      @if($profileStatusStudent)
        @include('partials.student-status-banner', ['statusStudent' => $profileStatusStudent])
      @endif
      @yield('content')
    </main>
  </div>
</div>

@include('partials.credentials-modal')

<!-- ── Branded Confirm Modal (replaces native confirm()) ───────────── -->
<div id="acadrixConfirmOverlay" class="ax-confirm-overlay">
  <div class="ax-confirm-card">
    <div class="ax-confirm-icon" id="axConfirmIcon"><i class="fas fa-graduation-cap"></i></div>
    <div class="ax-confirm-title" id="axConfirmTitle">Are you sure?</div>
    <div class="ax-confirm-msg" id="axConfirmMsg">This action needs your confirmation.</div>
    <div class="ax-confirm-actions">
      <button type="button" class="ax-confirm-btn ax-confirm-cancel" id="axConfirmCancel">Cancel</button>
      <button type="button" class="ax-confirm-btn ax-confirm-ok" id="axConfirmOk">Yes, continue</button>
    </div>
  </div>
</div>
<style>
  .ax-confirm-overlay{
    position:fixed;inset:0;z-index:3000;display:none;align-items:center;justify-content:center;padding:20px;
    background:rgba(20,6,10,.55);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    opacity:0;transition:opacity .2s ease;
  }
  .ax-confirm-overlay.show{display:flex;opacity:1}
  .ax-confirm-card{
    width:100%;max-width:380px;background:#fff;border-radius:20px;padding:32px 28px 26px;text-align:center;
    box-shadow:0 40px 90px -20px rgba(20,6,10,.5);
    transform:translateY(14px) scale(.96);opacity:0;transition:transform .25s cubic-bezier(.2,.9,.3,1.3), opacity .2s ease;
    border:1px solid rgba(139,12,34,.08);
  }
  .ax-confirm-overlay.show .ax-confirm-card{transform:translateY(0) scale(1);opacity:1}
  .ax-confirm-icon{
    width:64px;height:64px;border-radius:50%;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;
    font-size:24px;color:#fff;
    background:linear-gradient(135deg,var(--crimson-mid,#a81030) 0%,var(--crimson,#890c25) 100%);
    box-shadow:0 12px 26px rgba(139,12,34,.35);
  }
  .ax-confirm-overlay[data-variant="warning"] .ax-confirm-icon{background:linear-gradient(135deg,#f0a83a 0%,#d97706 100%);box-shadow:0 12px 26px rgba(217,119,6,.35)}
  .ax-confirm-overlay[data-variant="danger"] .ax-confirm-icon{background:linear-gradient(135deg,#e04a5f 0%,#c0192e 100%);box-shadow:0 12px 26px rgba(192,25,46,.35)}
  .ax-confirm-title{font-family:'Outfit',sans-serif;font-size:19px;font-weight:800;color:#1a0608;margin-bottom:8px}
  .ax-confirm-msg{font-size:14px;color:#7a5057;line-height:1.55;margin-bottom:26px}
  .ax-confirm-actions{display:flex;gap:10px}
  .ax-confirm-btn{flex:1;padding:12px 16px;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:transform .12s,box-shadow .12s;font-family:inherit}
  .ax-confirm-btn:hover{transform:translateY(-1px)}
  .ax-confirm-cancel{background:#f3eced;color:#7a5057;border:1.5px solid #ecdfe1}
  .ax-confirm-cancel:hover{background:#ecdfe1}
  .ax-confirm-ok{color:#fff;background:linear-gradient(135deg,var(--crimson-mid,#a81030) 0%,var(--crimson,#890c25) 100%);box-shadow:0 10px 22px rgba(139,12,34,.3)}
  .ax-confirm-overlay[data-variant="warning"] .ax-confirm-ok{background:linear-gradient(135deg,#f0a83a 0%,#d97706 100%);box-shadow:0 10px 22px rgba(217,119,6,.3)}
  .ax-confirm-overlay[data-variant="danger"] .ax-confirm-ok{background:linear-gradient(135deg,#e04a5f 0%,#c0192e 100%);box-shadow:0 10px 22px rgba(192,25,46,.3)}
</style>

<style>
/* ── AI Insights pill button ─────────────────────────────────────── */
.ai-insights-pill{
  display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:999px;border:none;cursor:pointer;
  background:linear-gradient(135deg,#a91d3a 0%,#890c25 100%);color:#fff;font-weight:700;font-size:13px;
  box-shadow:0 6px 16px rgba(137,12,37,.32);position:relative;transition:transform .15s;
}
.ai-insights-pill:hover{transform:translateY(-1px)}
.ai-insights-pill-dot{width:9px;height:9px;border-radius:50%;background:#f0a83a;border:2px solid #fff;position:absolute;top:-3px;right:-3px}

/* ── AI Insights slide-in overlay/panel ──────────────────────────── */
.ai-ins-overlay{
  position:fixed;inset:0;z-index:2500;display:none;justify-content:flex-end;
  background:rgba(20,6,10,.45);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);
}
.ai-ins-overlay.show{display:flex}
.ai-ins-panel{
  width:400px;max-width:100vw;height:100vh;background:#f7f3f4;display:flex;flex-direction:column;
  box-shadow:-20px 0 60px rgba(0,0,0,.25);animation:aiInsSlide .25s cubic-bezier(.2,.9,.3,1.1);
  font-family:inherit;
}
@keyframes aiInsSlide{from{transform:translateX(24px);opacity:0}to{transform:translateX(0);opacity:1}}
.ai-ins-head{background:linear-gradient(160deg,#7a0a1f 0%,#5c0716 100%);color:#fff;padding:20px 20px 0;flex-shrink:0}
.ai-ins-head-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.ai-ins-brand{display:flex;align-items:center;gap:10px}
.ai-ins-logo{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;font-size:16px}
.ai-ins-title{font-weight:800;font-size:15px}
.ai-ins-sub{font-size:10.5px;opacity:.75;letter-spacing:.3px}
.ai-ins-close{background:rgba(255,255,255,.12);border:none;color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer}
.ai-ins-score-box{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.ai-ins-score-num{font-size:40px;font-weight:800;line-height:1}
.ai-ins-score-label{font-size:10.5px;letter-spacing:.6px;opacity:.75}
.ai-ins-score-status{font-weight:700;font-size:14px;margin:2px 0 6px}
.ai-ins-score-bar{width:170px;height:6px;border-radius:6px;background:rgba(255,255,255,.2);overflow:hidden}
.ai-ins-score-bar-fill{height:100%;background:linear-gradient(90deg,#f0a83a,#fff);border-radius:6px;width:0%;transition:width .5s}
.ai-ins-tabs{display:flex;gap:4px}
.ai-ins-tab{flex:1;background:none;border:none;color:rgba(255,255,255,.7);padding:10px 4px;font-size:11.5px;font-weight:700;
  border-bottom:2px solid transparent;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px}
.ai-ins-tab.active{color:#fff;border-bottom-color:#f0a83a}
.ai-ins-body{flex:1;overflow-y:auto;padding:16px}
.ai-ins-card{background:#fff;border-radius:14px;padding:16px;margin-bottom:14px;border:1px solid #ecdfe1}
.ai-ins-card-label{font-size:10.5px;font-weight:700;color:#a0808a;letter-spacing:.4px}
.ai-ins-greeting{font-size:17px;font-weight:800;color:#1a0608;margin:2px 0 14px}
.ai-ins-chart-label{font-size:10px;font-weight:700;color:#a0808a;letter-spacing:.4px;margin-bottom:8px}
.ai-ins-chart{display:flex;align-items:flex-end;gap:6px;height:60px}
.ai-ins-bar-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px}
.ai-ins-bar{width:100%;border-radius:4px;background:#f0c6cd;min-height:4px}
.ai-ins-bar.today{background:#16a34a}
.ai-ins-bar-day{font-size:9px;color:#a0808a;font-weight:600}
.ai-ins-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.ai-ins-stat{background:#fff;border-radius:12px;padding:12px 6px;text-align:center;border:1px solid #ecdfe1}
.ai-ins-stat-val{font-size:19px;font-weight:800;color:#1a0608}
.ai-ins-stat-lbl{font-size:9.5px;color:#a0808a;font-weight:700;letter-spacing:.3px;text-transform:uppercase;margin-top:2px}
.ai-ins-list-card{background:#fff;border-radius:14px;padding:14px 16px;margin-bottom:14px;border:1px solid #ecdfe1}
.ai-ins-list-head{display:flex;align-items:center;gap:8px;font-weight:800;font-size:13.5px;color:#1a0608;margin-bottom:10px}
.ai-ins-badge{margin-left:auto;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px}
.ai-ins-badge.danger{background:#fde2e2;color:#c0192e}
.ai-ins-row{display:flex;gap:8px;font-size:12.5px;line-height:1.5;color:#3a2226;padding:6px 0;border-top:1px dashed #ecdfe1}
.ai-ins-row:first-child{border-top:none}
.ai-ins-empty{font-size:12.5px;color:#a0808a;padding:6px 0}
.ai-ins-search-input{width:100%;padding:11px 14px;border-radius:10px;border:1px solid #ecdfe1;font-size:13.5px;margin-bottom:14px}
.ai-ins-search-group-title{font-size:10.5px;font-weight:700;color:#a0808a;letter-spacing:.4px;margin:10px 0 6px}
.ai-ins-search-item{display:flex;justify-content:space-between;padding:9px 12px;background:#fff;border:1px solid #ecdfe1;border-radius:10px;margin-bottom:6px;text-decoration:none;color:#1a0608;font-size:13px}
.ai-ins-search-item:hover{border-color:#a91d3a}
@media (max-width:480px){
  .ai-ins-panel{width:100vw}
  .ai-insights-pill{padding:9px 11px}
  .ai-insights-pill-label{display:none}
}
</style>
<script>
function toggleTheme(){
  const t=document.documentElement;
  t.dataset.theme = t.dataset.theme==='dark' ? '' : 'dark';
  localStorage.setItem('theme',t.dataset.theme);
}
(function(){ const t=localStorage.getItem('theme'); if(t) document.documentElement.dataset.theme=t; })();

// Mobile sidebar: the hamburger toggle only needs to be visible on small
// screens (handled by CSS media query on #menu-toggle). Opening it also
// shows a tap-to-close backdrop, and picking any nav link closes the
// drawer again so it doesn't stay open over the page content.
(function(){
  const sidebar  = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  function closeSidebar(){
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('open');
  }
  document.getElementById('menu-toggle')?.addEventListener('click', function(){
    sidebar?.classList.toggle('open');
    backdrop?.classList.toggle('open');
  });
  backdrop?.addEventListener('click', closeSidebar);
  sidebar?.querySelectorAll('.sidebar-nav a').forEach(a => a.addEventListener('click', closeSidebar));
})();

function toggleAiAssist(){
  const panel = document.getElementById('aiAssistPanel');
  if (!panel) return;
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
function runAiAssist(){
  const btn = document.getElementById('aiAssistGenBtn');
  const out = document.getElementById('aiAssistOutput');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking…';

  fetch("{{ route('ai-assist.briefing') }}", {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  })
    .then(res => res.json())
    .then(data => {
      out.style.display = 'block';
      out.textContent = data.briefing || 'No insight available right now.';
    })
    .catch(() => {
      out.style.display = 'block';
      out.textContent = 'Could not generate an insight right now — please try again.';
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    });
}

// ── AI Insights panel ──────────────────────────────────────────────
let aiInsLoaded = false;
function toggleAiInsights(force){
  const overlay = document.getElementById('aiInsightsOverlay');
  if (!overlay) return;
  const show = force === undefined ? !overlay.classList.contains('show') : force;
  overlay.classList.toggle('show', show);
  if (show && !aiInsLoaded) { aiInsLoadData(); aiInsLoaded = true; }
}
function aiInsSwitchTab(tab){
  document.querySelectorAll('.ai-ins-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  document.querySelectorAll('.ai-ins-tabpanel').forEach(p => p.style.display = (p.dataset.tabpanel === tab) ? 'block' : 'none');
  if (tab === 'fees') aiInsLoadFees();
  if (tab === 'attend') aiInsLoadAttendance();
}
function aiInsBarChart(days){
  if (!days || !days.length) return '<div class="ai-ins-empty">No attendance recorded yet.</div>';
  return days.map(d => `
    <div class="ai-ins-bar-col">
      <div class="ai-ins-bar ${d.is_today ? 'today' : ''}" style="height:${Math.max(6, d.pct)}%"></div>
      <div class="ai-ins-bar-day">${d.label}</div>
    </div>`).join('');
}
function aiInsRenderList(el, items, emptyText){
  if (!items || !items.length) { el.innerHTML = `<div class="ai-ins-empty">${emptyText}</div>`; return; }
  el.innerHTML = items.map(i => `<div class="ai-ins-row"><span>${i.icon || '•'}</span><span>${i.text}</span></div>`).join('');
}
function aiInsLoadData(){
  fetch("{{ route('ai-insights.data') }}", { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
      document.getElementById('aiInsScore').textContent = d.score ?? '—';
      document.getElementById('aiInsScoreStatus').textContent = d.score_label || '—';
      document.getElementById('aiInsScoreBar').style.width = (d.score || 0) + '%';
      document.getElementById('aiInsDateLabel').textContent = d.date_label || '';
      document.getElementById('aiInsGreeting').textContent = (d.greeting || 'Hello') + ', {{ auth()->user()->name ?? "there" }} 👋';
      document.getElementById('aiInsChart').innerHTML = aiInsBarChart(d.attendance_days);
      document.getElementById('aiInsStats').innerHTML = (d.stats || []).map(s => `
        <div class="ai-ins-stat"><div class="ai-ins-stat-val">${s.value}</div><div class="ai-ins-stat-lbl">${s.label}</div></div>`).join('');
      document.getElementById('aiInsAlertCount').textContent = (d.alerts?.length || 0) + ' ALERTS';
      aiInsRenderList(document.getElementById('aiInsAlerts'), d.alerts, 'Nothing needs attention right now.');
      aiInsRenderList(document.getElementById('aiInsPositives'), d.positives, 'No positive indicators yet.');
    })
    .catch(() => { document.getElementById('aiInsScoreStatus').textContent = 'Could not load insights'; });
}
function aiInsLoadFees(){
  const box = document.getElementById('aiInsFeesStats');
  const detail = document.getElementById('aiInsFeesDetail');
  if (box.dataset.loaded) return;
  fetch("{{ route('ai-insights.fees') }}", { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
      box.dataset.loaded = '1';
      box.innerHTML = `
        <div class="ai-ins-stat"><div class="ai-ins-stat-val">Rs.${d.collected_today}</div><div class="ai-ins-stat-lbl">Today</div></div>
        <div class="ai-ins-stat"><div class="ai-ins-stat-val">Rs.${d.collected_month}</div><div class="ai-ins-stat-lbl">This Month</div></div>
        <div class="ai-ins-stat"><div class="ai-ins-stat-val">Rs.${d.outstanding}</div><div class="ai-ins-stat-lbl">Outstanding</div></div>`;
      detail.innerHTML = `
        <div class="ai-ins-list-head"><i class="fas fa-file-invoice-dollar" style="color:#890c25"></i> Voucher Snapshot</div>
        <div class="ai-ins-row"><span>📄</span><span>${d.unpaid_vouchers} unpaid / partial voucher(s)</span></div>
        <div class="ai-ins-row"><span>⏰</span><span>${d.overdue_vouchers} overdue voucher(s)</span></div>`;
    });
}
function aiInsLoadAttendance(){
  const chart = document.getElementById('aiInsChart2');
  if (chart.dataset.loaded) return;
  fetch("{{ route('ai-insights.attendance') }}", { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => { chart.dataset.loaded = '1'; chart.innerHTML = aiInsBarChart(d.days); });
}

// ── Global header search ───────────────────────────────────────────────
// Matches (1) sidebar pages instantly in-browser, available to every role,
// and (2) records from the server, scoped to the user's role.
(function(){
  const input = document.getElementById('globalSearchInput');
  const box   = document.getElementById('globalSearchResults');
  if (!input || !box) return;

  const endpoint = @json(route('global-search'));
  let timer = null, reqId = 0, serverGroups = [], items = [], active = -1;

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function hl(text, q){
    const t = esc(text); if (!q) return t;
    const i = String(text).toLowerCase().indexOf(q.toLowerCase());
    if (i < 0) return t;
    return esc(text.slice(0,i)) + '<mark>' + esc(text.slice(i,i+q.length)) + '</mark>' + esc(text.slice(i+q.length));
  }

  function pageMatches(q){
    const ql = q.toLowerCase(), seen = new Set(), out = [];
    document.querySelectorAll('#sidebar a[href]').forEach(a => {
      const label = a.textContent.replace(/\s+/g,' ').trim();
      const href = a.getAttribute('href');
      if (!label || !href || href === '#' || href.startsWith('javascript') || seen.has(href)) return;
      if (label.toLowerCase().includes(ql)) { seen.add(href); out.push({ title: label, meta: 'Page', url: a.href }); }
    });
    return out.slice(0, 6);
  }

  function render(q){
    const groups = serverGroups.slice();
    const pages = pageMatches(q);
    if (pages.length) groups.push({ label: 'Pages', icon: 'fa-compass', items: pages });
    items = []; active = -1;
    if (!groups.length) {
      box.innerHTML = '<div class="gs-empty">No matches found for “' + esc(q) + '”.</div>';
    } else {
      box.innerHTML = groups.map(g => '<div class="gs-group-title">' + esc(g.label) + '</div>' +
        g.items.map(it => { items.push(it);
          return '<a class="gs-item" role="option" href="' + esc(it.url) + '" data-idx="' + (items.length-1) + '"><i class="fas ' + esc(g.icon) + '"></i>' +
                 '<span class="gs-title">' + hl(it.title, q) + '</span>' + (it.meta ? '<span class="gs-meta">' + esc(it.meta) + '</span>' : '') + '</a>';
        }).join('')).join('');
    }
    open();
  }

  function open(){ box.style.display = 'block'; input.setAttribute('aria-expanded','true'); }
  function close(){ box.style.display = 'none'; input.setAttribute('aria-expanded','false'); active = -1; }
  function setActive(i){
    const els = box.querySelectorAll('.gs-item'); if (!els.length) return;
    active = (i + els.length) % els.length;
    els.forEach((e,n) => e.classList.toggle('active', n === active));
    els[active].scrollIntoView({ block: 'nearest' });
  }

  input.addEventListener('input', () => {
    const q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { close(); box.innerHTML = ''; serverGroups = []; return; }
    serverGroups = [];
    render(q); // instant: pages first
    const id = ++reqId;
    timer = setTimeout(() => {
      fetch(endpoint + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => r.ok ? r.json() : { groups: [] })
        .then(d => { if (id !== reqId) return; serverGroups = d.groups || []; render(q); })
        .catch(() => {});
    }, 250);
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
    else if (e.key === 'Enter') {
      const els = box.querySelectorAll('.gs-item');
      const el = els[active] || els[0];
      if (el) { e.preventDefault(); window.location.href = el.href; }
    }
    else if (e.key === 'Escape') { close(); input.blur(); }
  });

  input.addEventListener('focus', () => { if (input.value.trim().length >= 2 && box.innerHTML) open(); });
  document.addEventListener('click', e => { if (!e.target.closest('.topbar-search')) close(); });
  document.addEventListener('keydown', e => {
    const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
    if ((e.key === '/' && !typing) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) { e.preventDefault(); input.focus(); input.select(); }
  });
})();

let aiInsSearchTimer = null;
function aiInsSearch(q){
  clearTimeout(aiInsSearchTimer);
  const results = document.getElementById('aiInsSearchResults');
  if (!q || q.trim().length < 2) { results.innerHTML = ''; return; }
  aiInsSearchTimer = setTimeout(() => {
    fetch("{{ route('ai-insights.search') }}?q=" + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(d => {
        let html = '';
        if (d.teachers?.length) {
          html += '<div class="ai-ins-search-group-title">TEACHERS</div>';
          html += d.teachers.map(t => `<a class="ai-ins-search-item" href="${t.url}"><span>${t.name}</span><span>${t.meta}</span></a>`).join('');
        }
        if (d.students?.length) {
          html += '<div class="ai-ins-search-group-title">STUDENTS</div>';
          html += d.students.map(s => `<a class="ai-ins-search-item" href="${s.url}"><span>${s.name}</span><span>${s.meta}</span></a>`).join('');
        }
        results.innerHTML = html || '<div class="ai-ins-empty">No matches found.</div>';
      });
  }, 300);
}

/**
 * Branded confirm modal — replaces the native browser confirm() popup.
 * Usage: acadrixConfirm('Delete this student?', { title:'Delete student', variant:'danger', icon:'fa-trash', okText:'Delete' })
 *   .then(ok => { if (ok) { ... } });
 * Returns a Promise<boolean>.
 */
function acadrixConfirm(message, opts = {}) {
  const overlay  = document.getElementById('acadrixConfirmOverlay');
  const titleEl  = document.getElementById('axConfirmTitle');
  const msgEl    = document.getElementById('axConfirmMsg');
  const iconEl   = document.getElementById('axConfirmIcon');
  const okBtn    = document.getElementById('axConfirmOk');
  const cancelBtn= document.getElementById('axConfirmCancel');

  titleEl.textContent = opts.title || 'Are you sure?';
  msgEl.textContent   = message || 'This action needs your confirmation.';
  okBtn.innerHTML      = opts.okText ? opts.okText : 'Yes, continue';
  cancelBtn.textContent = opts.cancelText || 'Cancel';
  iconEl.innerHTML     = `<i class="fas ${opts.icon || 'fa-circle-question'}"></i>`;
  overlay.dataset.variant = opts.variant || 'primary';

  return new Promise((resolve) => {
    function cleanup(result) {
      overlay.classList.remove('show');
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlay);
      document.removeEventListener('keydown', onKey);
      resolve(result);
    }
    function onOk()      { cleanup(true); }
    function onCancel()  { cleanup(false); }
    function onOverlay(e){ if (e.target === overlay) cleanup(false); }
    function onKey(e)    { if (e.key === 'Escape') cleanup(false); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlay);
    document.addEventListener('keydown', onKey);
    requestAnimationFrame(() => overlay.classList.add('show'));
  });
}

// Auto-wire any form or link carrying data-confirm="message" to use the
// branded modal instead of window.confirm(). Optional data-confirm-title,
// data-confirm-variant (primary|warning|danger), data-confirm-icon,
// data-confirm-ok-text.
document.addEventListener('submit', function (e) {
  const form = e.target.closest('form[data-confirm]');
  if (!form || form.dataset.axConfirmed === '1') return;
  e.preventDefault();
  acadrixConfirm(form.dataset.confirm, {
    title:   form.dataset.confirmTitle,
    variant: form.dataset.confirmVariant,
    icon:    form.dataset.confirmIcon,
    okText:  form.dataset.confirmOkText,
  }).then((ok) => {
    if (ok) { form.dataset.axConfirmed = '1'; form.submit(); }
  });
}, true);

// ── Project-wide DD/MM/YYYY date controls ───────────────────────────────
// Native <input type="date"> rendering follows the browser/OS locale, so
// lang="en-GB" alone cannot guarantee DD/MM/YYYY (Chrome may still show
// MM/DD/YYYY).  Keep the native ISO input for Laravel/database compatibility,
// but show a synced DD/MM/YYYY text control to the user everywhere.
(function installAcadrixDateControls() {
  const DATE_SELECTOR = 'input[type="date"], input[type="datetime-local"]';

  function pad2(value) {
    return String(value).padStart(2, '0');
  }

  function isoToDisplay(value, type) {
    if (!value) return '';
    const dateMatch = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!dateMatch) return value;

    const datePart = `${dateMatch[3]}/${dateMatch[2]}/${dateMatch[1]}`;
    if (type !== 'datetime-local') return datePart;

    const timeMatch = String(value).match(/T(\d{2}):(\d{2})/);
    return timeMatch ? `${datePart} ${timeMatch[1]}:${timeMatch[2]}` : datePart;
  }

  function parseDisplay(value, type) {
    const text = String(value || '').trim();
    if (!text) return { valid: true, iso: '' };

    const pattern = type === 'datetime-local'
      ? /^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))$/
      : /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const match = text.match(pattern);
    if (!match) return { valid: false, iso: '' };

    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);
    const check = new Date(Date.UTC(year, month - 1, day));
    if (
      check.getUTCFullYear() !== year ||
      check.getUTCMonth() !== month - 1 ||
      check.getUTCDate() !== day
    ) {
      return { valid: false, iso: '' };
    }

    let iso = `${year}-${pad2(month)}-${pad2(day)}`;
    if (type === 'datetime-local') {
      const hour = Number(match[4]);
      const minute = Number(match[5]);
      if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
        return { valid: false, iso: '' };
      }
      iso += `T${pad2(hour)}:${pad2(minute)}`;
    }

    return { valid: true, iso };
  }

  function validateDisplay(nativeInput, displayInput) {
    const parsed = parseDisplay(displayInput.value, nativeInput.type);
    let message = '';

    if (!displayInput.value.trim()) {
      if (nativeInput.dataset.axWasRequired === '1') {
        message = 'Please enter a date in DD/MM/YYYY format.';
      }
    } else if (!parsed.valid) {
      message = nativeInput.type === 'datetime-local'
        ? 'Please use DD/MM/YYYY HH:mm.'
        : 'Please use DD/MM/YYYY.';
    } else {
      const isoDate = parsed.iso.slice(0, 10);
      if (nativeInput.min && isoDate < nativeInput.min.slice(0, 10)) {
        message = `Date must be on or after ${isoToDisplay(nativeInput.min, 'date')}.`;
      }
      if (!message && nativeInput.max && isoDate > nativeInput.max.slice(0, 10)) {
        message = `Date must be on or before ${isoToDisplay(nativeInput.max, 'date')}.`;
      }
    }

    displayInput.setCustomValidity(message);
    return { ...parsed, valid: parsed.valid && !message };
  }

  function syncDisplayToNative(nativeInput, displayInput, emit = true) {
    const result = validateDisplay(nativeInput, displayInput);
    if (!result.valid) return false;

    if (nativeInput.value !== result.iso) {
      nativeInput.value = result.iso;
      nativeInput.dataset.axLastValue = result.iso;
      if (emit) {
        nativeInput.dispatchEvent(new Event('input', { bubbles: true }));
        nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
    return true;
  }

  function enhance(nativeInput) {
    if (!nativeInput || nativeInput.dataset.axDateEnhanced === '1') return;
    nativeInput.dataset.axDateEnhanced = '1';
    nativeInput.setAttribute('lang', 'en-GB');
    nativeInput.dataset.axWasRequired = nativeInput.required ? '1' : '0';
    nativeInput.dataset.axLastValue = nativeInput.value || '';

    const wrapper = document.createElement('span');
    wrapper.className = 'ax-date-control';

    const display = document.createElement('input');
    display.type = 'text';
    display.className = nativeInput.className;
    display.style.cssText = nativeInput.getAttribute('style') || '';
    display.placeholder = nativeInput.type === 'datetime-local'
      ? 'DD/MM/YYYY HH:mm'
      : 'DD/MM/YYYY';
    display.autocomplete = 'off';
    display.inputMode = 'numeric';
    display.value = isoToDisplay(nativeInput.value, nativeInput.type);
    display.disabled = nativeInput.disabled;
    display.readOnly = nativeInput.readOnly;
    display.setAttribute('data-date-format', nativeInput.type === 'datetime-local' ? 'DD/MM/YYYY HH:mm' : 'DD/MM/YYYY');
    if (nativeInput.getAttribute('aria-label')) {
      display.setAttribute('aria-label', nativeInput.getAttribute('aria-label'));
    }
    if (nativeInput.dataset.axWasRequired === '1') display.required = true;

    const pickerButton = document.createElement('button');
    pickerButton.type = 'button';
    pickerButton.className = 'ax-date-picker-btn';
    pickerButton.innerHTML = '<i class="fas fa-calendar-alt" aria-hidden="true"></i>';
    pickerButton.setAttribute('aria-label', 'Open calendar');
    pickerButton.disabled = nativeInput.disabled || nativeInput.readOnly;

    nativeInput.parentNode.insertBefore(wrapper, nativeInput);
    wrapper.appendChild(display);
    wrapper.appendChild(pickerButton);
    wrapper.appendChild(nativeInput);

    // Keep the real named ISO field in the form, but hide its browser-specific
    // MM/DD/YYYY presentation. The visible field intentionally has no name.
    nativeInput.classList.add('ax-date-native');
    nativeInput.style.cssText = 'position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important;left:0!important;bottom:0!important;padding:0!important;border:0!important;';
    nativeInput.tabIndex = -1;
    nativeInput.required = false;
    nativeInput.setAttribute('aria-hidden', 'true');

    display.addEventListener('input', () => {
      display.setCustomValidity('');
      // Sync as soon as a complete valid value exists so page-specific JS that
      // reads the native ISO value continues to work.
      const parsed = parseDisplay(display.value, nativeInput.type);
      if (parsed.valid && parsed.iso) syncDisplayToNative(nativeInput, display, true);
      if (!display.value.trim()) {
        nativeInput.value = '';
        nativeInput.dataset.axLastValue = '';
      }
    });

    display.addEventListener('blur', () => {
      if (syncDisplayToNative(nativeInput, display, true) && nativeInput.value) {
        display.value = isoToDisplay(nativeInput.value, nativeInput.type);
      }
    });

    pickerButton.addEventListener('click', () => {
      if (nativeInput.disabled || nativeInput.readOnly) return;
      try {
        if (typeof nativeInput.showPicker === 'function') {
          nativeInput.showPicker();
        } else {
          nativeInput.focus();
          nativeInput.click();
        }
      } catch (e) {
        nativeInput.focus();
        nativeInput.click();
      }
    });

    nativeInput.addEventListener('input', () => {
      nativeInput.dataset.axLastValue = nativeInput.value || '';
      display.value = isoToDisplay(nativeInput.value, nativeInput.type);
      display.setCustomValidity('');
    });
    nativeInput.addEventListener('change', () => {
      nativeInput.dataset.axLastValue = nativeInput.value || '';
      display.value = isoToDisplay(nativeInput.value, nativeInput.type);
      display.setCustomValidity('');
    });

    const form = nativeInput.form;
    if (form && form.dataset.axDateValidationBound !== '1') {
      form.dataset.axDateValidationBound = '1';
      form.addEventListener('submit', (event) => {
        let firstInvalid = null;
        form.querySelectorAll('.ax-date-control').forEach((control) => {
          const visible = control.querySelector('input[type="text"][data-date-format]');
          const real = control.querySelector('.ax-date-native');
          if (!visible || !real || visible.disabled) return;
          if (!syncDisplayToNative(real, visible, false) && !firstInvalid) {
            firstInvalid = visible;
          }
        });
        if (firstInvalid) {
          event.preventDefault();
          firstInvalid.reportValidity();
          firstInvalid.focus();
        }
      }, true);
    }
  }

  function apply(root) {
    if (!root || !root.querySelectorAll) return;
    if (root.matches?.(DATE_SELECTOR)) enhance(root);
    root.querySelectorAll(DATE_SELECTOR).forEach(enhance);
  }

  function syncProgrammaticChanges() {
    document.querySelectorAll('.ax-date-native').forEach((nativeInput) => {
      const wrapper = nativeInput.closest('.ax-date-control');
      const display = wrapper?.querySelector('input[type="text"][data-date-format]');
      if (!display) return;
      const current = nativeInput.value || '';
      if (current !== (nativeInput.dataset.axLastValue || '')) {
        nativeInput.dataset.axLastValue = current;
        display.value = isoToDisplay(current, nativeInput.type);
        display.setCustomValidity('');
      }
      display.disabled = nativeInput.disabled;
      display.readOnly = nativeInput.readOnly;
      const btn = wrapper.querySelector('.ax-date-picker-btn');
      if (btn) btn.disabled = nativeInput.disabled || nativeInput.readOnly;
    });
  }

  const style = document.createElement('style');
  style.textContent = `
    .ax-date-control{position:relative;display:inline-flex;width:100%;min-width:0;align-items:center;vertical-align:middle}
    .ax-date-control>input[type="text"]{width:100%;padding-right:42px!important}
    .ax-date-picker-btn{position:absolute;right:1px;top:1px;bottom:1px;width:38px;border:0;background:transparent;color:inherit;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;border-radius:8px}
    .ax-date-picker-btn:hover{background:rgba(0,0,0,.04)}
    .ax-date-picker-btn:disabled{cursor:not-allowed;opacity:.45}
  `;
  document.head.appendChild(style);

  apply(document);
  document.addEventListener('DOMContentLoaded', () => apply(document));
  new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
      if (node.nodeType === 1) apply(node);
    }));
  }).observe(document.documentElement, { childList: true, subtree: true });

  // Some screens populate native date values through JavaScript without
  // dispatching change/input events. Keep the DD/MM/YYYY mirror in sync.
  setInterval(syncProgrammaticChanges, 300);
})();

// ── Notification bell: poll, render, and beep on new items ─────────────────
(function () {
  let lastUnread = null;
  let audioCtx = null;

  function beep() {
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.001, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.18, audioCtx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.35);
      osc.connect(gain).connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.35);
    } catch (e) { /* audio not available — ignore */ }
  }

  function timeAgo(iso) {
    const s = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    if (s < 86400) return Math.floor(s / 3600) + 'h ago';
    return Math.floor(s / 86400) + 'd ago';
  }

  window.markAllNotifsRead = function () {
    fetch('{{ route("notifications.read-all") }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    }).then(pollNotifications);
  };

  window.toggleNotifBell = function () {
    const panel = document.getElementById('notifBellPanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  };

  document.addEventListener('click', function (e) {
    const panel = document.getElementById('notifBellPanel');
    const btn = document.getElementById('notifBellBtn');
    if (panel && panel.style.display === 'block' && !panel.contains(e.target) && !btn.contains(e.target)) {
      panel.style.display = 'none';
    }
  });

  function pollNotifications() {
    fetch('{{ route("notifications.poll") }}', { headers: { 'Accept': 'application/json' } })
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        if (!data) return;
        const dot = document.getElementById('notifBellDot');
        const list = document.getElementById('notifBellList');
        if (dot) dot.style.display = data.unread_count > 0 ? 'block' : 'none';

        if (lastUnread !== null && data.unread_count > lastUnread) beep();
        lastUnread = data.unread_count;

        if (list) {
          list.innerHTML = data.notifications.length
            ? data.notifications.map(n => `
                <div style="padding:10px 14px;border-bottom:1px solid var(--border,#f1f1f1);display:flex;gap:10px;${n.read ? 'opacity:.6' : ''}">
                  <i class="fas ${n.icon || 'fa-bell'}" style="color:var(--primary,#890c25);margin-top:2px"></i>
                  <div style="flex:1">
                    <div>${n.message}</div>
                    <div style="color:var(--text-muted,#7a5057);font-size:10.5px;margin-top:2px">${timeAgo(n.created_at)}</div>
                  </div>
                </div>`).join('')
            : '<div style="padding:20px;text-align:center;color:var(--text-muted,#7a5057)">No notifications yet.</div>';
        }
      })
      .catch(() => {});
  }

  if (document.getElementById('notifBellBtn')) {
    pollNotifications();
    setInterval(pollNotifications, 15000);
  }
})();
</script>
<script>
// Lock already-rendered mutation controls on archived profiles. Read-only links,
// saved receipts and printing remain usable. Middleware is the authoritative lock.
(() => {
  const root = document.querySelector('.page-content');
  if (!root || !root.querySelector('[data-profile-locked]')) return;
  const lockControls = () => {
    root.querySelectorAll('form').forEach(form => {
      if (form.hasAttribute('data-student-reactivation')) return;
      const method = (form.querySelector('[name="_method"]')?.value || form.method || 'get').toLowerCase();
      if (['get', 'head'].includes(method)) return;
      form.dataset.studentLocked = 'true';
      form.querySelectorAll('input,select,textarea,button').forEach(el => { el.disabled = true; });
      form.title = 'Profile disabled. Reactivate before making changes.';
    });
    root.querySelectorAll('a[href]').forEach(link => {
      const path = new URL(link.href, document.baseURI).pathname;
      if (!/\/(edit|create|renew|issue|reissue|message|id-card)\/?$/.test(path)) return;
      link.setAttribute('aria-disabled', 'true'); link.setAttribute('tabindex', '-1');
      link.dataset.studentLocked = 'true'; link.style.opacity = '.45';
      link.title = 'Profile disabled. Reactivate before making changes.';
    });
  };
  lockControls();
  document.addEventListener('click', e => {
    if (e.target.closest('[data-student-locked="true"]')) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);
  document.addEventListener('submit', e => {
    if (e.target.matches('form[data-student-locked="true"]')) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);
})();
</script>
@stack('scripts')
{{-- Inline on purpose: avoids /js/ajax-filters.js 404s on sub-folder/shared-host deployments. --}}
@include('partials.ajax-filters')
@include('partials.pwa-install-prompt')
</body>
</html>
