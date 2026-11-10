@extends('layouts.app')
@section('title','Admin Dashboard')
@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');

  :root {
    --crimson:      #8b0c22;
    --crimson-mid:  #a81030;
    --crimson-dark: #5c0718;
    --crimson-glow: rgba(139,12,34,.18);
    --bg:           #f7f3f4;
    --surface:      #ffffff;
    --surface-2:    #fdf0f2;
    --border:       rgba(139,12,34,.10);
    --text:         #1a0608;
    --muted:        #7a5057;
    --success:      #0d9e6e;
    --warning:      #d97706;
    --info:         #2563eb;
    --radius:       14px;
    --shadow:       0 2px 16px rgba(139,12,34,.08);
    --shadow-lg:    0 8px 40px rgba(139,12,34,.14);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

  /* ── Hero Banner ── */
  .db-hero {
    background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 55%, #c0192e 100%);
    border-radius: var(--radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    color: #fff;
    box-shadow: var(--shadow-lg);
  }
  .db-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
  }
  .db-hero::after {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,.07) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }
  .db-hero-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
  .db-hero-left { }
  .db-hero-date { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; opacity: .65; margin-bottom: 6px; }
  .db-hero-title { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; margin-bottom: 4px; }
  .db-hero-sub { font-size: 13.5px; opacity: .75; }
  .db-hero-right { display: flex; gap: 20px; flex-wrap: wrap; }
  .db-hero-stat { text-align: center; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 12px; padding: 12px 22px; backdrop-filter: blur(8px); }
  .db-hero-stat-val { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; line-height: 1; }
  .db-hero-stat-lbl { font-size: 11px; opacity: .7; margin-top: 4px; font-weight: 500; letter-spacing: .04em; }

  /* ── Stat Cards ── */
  .db-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
  @media (max-width: 900px) { .db-stats { grid-template-columns: repeat(2,1fr); } }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow);
    transition: transform .18s, box-shadow .18s;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
  .stat-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
  .stat-icon-wrap.red    { background: rgba(139,12,34,.10); color: var(--crimson); }
  .stat-icon-wrap.blue   { background: rgba(37,99,235,.10);  color: var(--info); }
  .stat-icon-wrap.green  { background: rgba(13,158,110,.10); color: var(--success); }
  .stat-icon-wrap.yellow { background: rgba(217,119,6,.10);  color: var(--warning); }
  .stat-body { flex: 1; min-width: 0; }
  .stat-val { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: var(--text); line-height: 1.1; }
  .stat-lbl { font-size: 12px; color: var(--muted); margin-top: 3px; font-weight: 500; }
  .stat-trend { font-size: 11px; margin-top: 5px; font-weight: 600; }
  .stat-trend.up   { color: var(--success); }
  .stat-trend.down { color: var(--crimson); }

  /* ── Cards ── */
  .db-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
  @media (max-width: 900px) { .db-row { grid-template-columns: 1fr; } }

  .db-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
  }
  .db-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--border);
  }
  .db-card-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; }
  .db-card-body { padding: 20px 22px; }

  /* ── Attendance Chart ── */
  .att-chart { display: flex; align-items: flex-end; gap: 8px; height: 110px; }
  .att-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
  .att-bar { width: 100%; border-radius: 6px 6px 0 0; transition: opacity .2s; min-height: 6px; }
  .att-bar.present { background: linear-gradient(180deg, #c0192e 0%, var(--crimson-dark) 100%); }
  .att-bar.absent  { background: rgba(139,12,34,.15); }
  .att-label { font-size: 10px; color: var(--muted); font-weight: 600; letter-spacing: .04em; }
  .att-pct   { font-size: 10px; font-weight: 700; color: var(--crimson); }
  .att-legend { display: flex; gap: 16px; margin-top: 14px; }
  .att-legend-dot { width: 8px; height: 8px; border-radius: 50%; }
  .att-legend-item { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--muted); font-weight: 500; }

  /* ── Fee Donut ── */
  .fee-donut-wrap { display: flex; align-items: center; gap: 24px; }
  .fee-donut-svg { flex-shrink: 0; }
  .fee-legend-list { display: flex; flex-direction: column; gap: 10px; }
  .fee-legend-row { display: flex; align-items: center; gap: 10px; }
  .fee-leg-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
  .fee-leg-label { font-size: 12px; color: var(--muted); flex: 1; }
  .fee-leg-val { font-size: 13px; font-weight: 700; color: var(--text); }
  .fee-total-line { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); font-size: 13px; color: var(--muted); }
  .fee-total-line strong { font-family: 'Outfit', sans-serif; font-size: 18px; color: var(--text); display: block; margin-top: 2px; }

  /* ── Table ── */
  .db-table { width: 100%; border-collapse: collapse; }
  .db-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); padding: 0 16px 10px; text-align: left; }
  .db-table td { padding: 11px 16px; border-top: 1px solid var(--border); font-size: 13.5px; }
  .db-table tr:hover td { background: var(--surface-2); }
  .student-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; }
  .student-cell { display: flex; align-items: center; gap: 10px; }
  .student-name { font-weight: 600; font-size: 13.5px; }
  .student-id { font-size: 11px; color: var(--muted); }

  .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: .03em; }
  .badge-success  { background: rgba(13,158,110,.1);  color: var(--success); }
  .badge-warning  { background: rgba(217,119,6,.12);  color: var(--warning); }

  /* ── Timeline ── */
  .timeline { display: flex; flex-direction: column; gap: 0; }
  .tl-item { display: flex; gap: 14px; padding: 10px 0; position: relative; }
  .tl-item + .tl-item::before { content: ''; position: absolute; left: 15px; top: 0; width: 1px; height: 10px; background: var(--border); }
  .tl-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff; flex-shrink: 0; }
  .tl-dot.blue    { background: var(--info); }
  .tl-dot.green   { background: var(--success); }
  .tl-dot.yellow  { background: var(--warning); }
  .tl-dot.crimson { background: var(--crimson); }
  .tl-dot.purple  { background: #7c3aed; }
  .tl-content { flex: 1; min-width: 0; }
  .tl-desc { font-size: 13px; line-height: 1.45; }
  .tl-desc strong { font-weight: 600; }
  .tl-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .live-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; background: rgba(13,158,110,.12); color: var(--success); font-size: 11px; font-weight: 700; }
  .live-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--success); animation: pulse 1.4s ease-in-out infinite; }
  @keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(.8); } }

  /* ── Quick Actions ── */
  .qa-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; }
  @media (max-width: 700px) { .qa-grid { grid-template-columns: repeat(2,1fr); } }
  .qa-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 8px; padding: 18px 10px;
    border-radius: 12px; border: 1.5px solid var(--border);
    background: var(--surface); text-decoration: none; color: var(--text);
    transition: border-color .15s, background .15s, transform .15s, box-shadow .15s;
    cursor: pointer;
  }
  .qa-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--crimson-glow); border-color: var(--crimson); background: rgba(139,12,34,.04); }
  .qa-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; }
  .qa-label { font-size: 12px; font-weight: 600; text-align: center; color: var(--text); }

  /* ── Calendar ── */
  .cal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
  .cal-day-name { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); text-align: center; padding: 4px 0 8px; }
  .cal-day { aspect-ratio: 1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text); cursor: pointer; transition: background .12s; }
  .cal-day:hover { background: var(--surface-2); }
  .cal-day.other { color: var(--muted); opacity: .4; }
  .cal-day.today { background: var(--crimson); color: #fff; font-weight: 800; }
  .cal-day.has-event { position: relative; }
  .cal-day.has-event::after { content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: var(--crimson); }
  .cal-day.today.has-event::after { background: #fff; }
  .event-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border-radius: 10px; border-left: 3px solid var(--crimson); background: var(--surface-2); margin-top: 10px; }
  .event-icon { color: var(--crimson); font-size: 13px; padding-top: 1px; }
  .event-title { font-size: 13px; font-weight: 600; }
  .event-sub { font-size: 11px; color: var(--muted); margin-top: 1px; }

  .btn-outline {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px; border: 1.5px solid var(--border);
    background: transparent; color: var(--muted); font-size: 12px; font-weight: 600;
    text-decoration: none; transition: border-color .15s, color .15s;
    cursor: pointer;
  }
  .btn-outline:hover { border-color: var(--crimson); color: var(--crimson); }

  .btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 8px; border: none;
    background: var(--crimson); color: #fff; font-size: 12px; font-weight: 700;
    text-decoration: none; cursor: pointer; transition: background .15s;
  }
  .btn-primary:hover { background: var(--crimson-mid); }

  .db-bottom { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 900px) { .db-bottom { grid-template-columns: 1fr; } }
  .db-license-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
  @media (max-width: 700px) { .db-license-row { grid-template-columns: 1fr; } }
</style>

{{-- ── Hero Banner ── --}}
<div class="db-hero">
  <div class="db-hero-inner">
    <div class="db-hero-left">
      <div class="db-hero-date">{{ now()->format('l, F j Y') }}</div>
      <h1 class="db-hero-title">Good Morning, {{ explode(' ',$auth_user->name ?? 'Administrator')[0] }}! 👋</h1>
      <p class="db-hero-sub">Welcome back to Acadrix — here's what's happening today.</p>
    </div>
    <div class="db-hero-right">
      <div class="db-hero-stat">
        <div class="db-hero-stat-val">{{ $attendanceToday }}%</div>
        <div class="db-hero-stat-lbl">Attendance Today</div>
      </div>
      <div class="db-hero-stat">
        <div class="db-hero-stat-val">Rs.{{ number_format($totalRevenue/1000000,1) }}M</div>
        <div class="db-hero-stat-lbl">Fee Collected</div>
      </div>
      <div class="db-hero-stat">
        <div class="db-hero-stat-val">{{ $totalCourses }}</div>
        <div class="db-hero-stat-lbl">Courses Active</div>
      </div>
    </div>
  </div>
</div>

{{-- ── AI Insights teaser (opens the full real-time panel) ── --}}
@include('partials.ai-insights-teaser', ['teaserScopeLabel' => 'Students'])

{{-- ── AI Morning Briefing ── --}}
<div class="ai-briefing-card" id="aiBriefingCard">
  <div class="ai-briefing-head">
    <div class="ai-briefing-icon"><i class="fas fa-robot"></i></div>
    <div>
      <div class="ai-briefing-title">AI Morning Briefing</div>
      <div class="ai-briefing-sub">A quick, rule-based read of today's numbers — generated instantly, no external API.</div>
    </div>
    <button type="button" class="ai-briefing-btn" id="aiBriefingBtn">
      <i class="fas fa-wand-magic-sparkles"></i> Generate
    </button>
  </div>
  <div class="ai-briefing-body" id="aiBriefingBody" style="display:none"></div>
</div>
<style>
  .ai-briefing-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:24px;box-shadow:var(--shadow)}
  .ai-briefing-head{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
  .ai-briefing-icon{width:42px;height:42px;border-radius:12px;background:var(--crimson-glow);color:var(--crimson);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
  .ai-briefing-title{font-weight:700;font-size:15px;color:var(--text)}
  .ai-briefing-sub{font-size:12.5px;color:var(--muted);margin-top:2px}
  .ai-briefing-btn{margin-left:auto;background:linear-gradient(135deg,var(--crimson) 0%,var(--crimson-mid) 100%);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:transform .15s}
  .ai-briefing-btn:hover{transform:translateY(-1px)}
  .ai-briefing-btn:disabled{opacity:.6;cursor:progress}
  .ai-briefing-body{margin-top:16px;padding-top:16px;border-top:1px dashed var(--border);font-size:13.5px;line-height:1.65;color:var(--text)}
</style>
<script>
  document.getElementById('aiBriefingBtn')?.addEventListener('click', function () {
    const btn  = this;
    const body = document.getElementById('aiBriefingBody');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating…';

    fetch("{{ route('admin.dashboard.ai-briefing') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    })
      .then(res => res.json())
      .then(data => {
        body.style.display = 'block';
        body.textContent = data.briefing || 'No briefing available right now.';
      })
      .catch(() => {
        body.style.display = 'block';
        body.textContent = 'Could not generate a briefing right now — please try again.';
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Regenerate';
      });
  });
</script>

{{-- ── Stat Cards ── --}}
<div class="db-stats">
  <div class="stat-card">
    <div class="stat-icon-wrap red"><i class="fas fa-user-graduate"></i></div>
    <div class="stat-body">
      <div class="stat-val">{{ $totalStudents }}</div>
      <div class="stat-lbl">Total Students</div>
      <div class="stat-trend up"><i class="fas fa-arrow-up" style="font-size:9px"></i> +23 this month</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap blue"><i class="fas fa-chalkboard-teacher"></i></div>
    <div class="stat-body">
      <div class="stat-val">{{ $totalTeachers }}</div>
      <div class="stat-lbl">Teachers &amp; Staff</div>
      <div class="stat-trend up"><i class="fas fa-arrow-up" style="font-size:9px"></i> +2 this month</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap green"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-body">
      <div class="stat-val">Rs.{{ number_format($totalRevenue/1000000,1) }}M</div>
      <div class="stat-lbl">Monthly Revenue</div>
      <div class="stat-trend up"><i class="fas fa-arrow-up" style="font-size:9px"></i> +12.5%</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap yellow"><i class="fas fa-clipboard-check"></i></div>
    <div class="stat-body">
      <div class="stat-val">{{ $attendanceToday }}%</div>
      <div class="stat-lbl">Attendance Rate</div>
      <div class="stat-trend down"><i class="fas fa-arrow-down" style="font-size:9px"></i> -1.2% vs last week</div>
    </div>
  </div>
</div>

{{-- ── License Status + Pending Resources Banner ── --}}
@php
  $ls        = $licenseStatus    ?? 'unknown';
  $lsTrial   = $licenseIsTrial   ?? false;
  $lsDays    = $licenseDaysLeft  ?? null;
  $lsExpiry  = $licenseExpiresAt ?? null;
  $lsPlan    = $licensePlan      ?? null;
  $lsBadge   = match($ls) {
      'active'          => ['label'=>'Active',    'bg'=>'#0d9e6e', 'icon'=>'fa-check-circle'],
      'trial'           => ['label'=>'Trial',     'bg'=>'#2563eb', 'icon'=>'fa-flask'],
      'expired'         => ['label'=>'Expired',   'bg'=>'#dc2626', 'icon'=>'fa-times-circle'],
      'suspended'       => ['label'=>'Suspended', 'bg'=>'#d97706', 'icon'=>'fa-pause-circle'],
      'trial_expired'   => ['label'=>'Trial Ended','bg'=>'#dc2626','icon'=>'fa-hourglass-end'],
      default           => ['label'=>'Unknown',   'bg'=>'#64748b', 'icon'=>'fa-question-circle'],
  };
  $pendingAll  = $pendingResources  ?? 0;
  $pendingStu  = $pendingStudents   ?? 0;
  $pendingFee  = $pendingFeeCount   ?? 0;
@endphp
<div class="db-license-row">

  {{-- License Status Card --}}
  <div class="db-card" style="overflow:visible">
    <div class="db-card-header">
      <span class="db-card-title"><i class="fas fa-shield-alt" style="color:var(--crimson);margin-right:6px"></i>License Status</span>
      <span style="background:{{ $lsBadge['bg'] }}18;color:{{ $lsBadge['bg'] }};font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:5px">
        <i class="fas {{ $lsBadge['icon'] }}" style="font-size:10px"></i> {{ $lsBadge['label'] }}{{ $lsTrial ? ' (Trial)' : '' }}
      </span>
    </div>
    <div class="db-card-body" style="display:flex;align-items:center;gap:20px;padding:18px 22px">
      <div style="width:54px;height:54px;border-radius:14px;background:{{ $lsBadge['bg'] }}18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="fas {{ $lsBadge['icon'] }}" style="font-size:22px;color:{{ $lsBadge['bg'] }}"></i>
      </div>
      <div style="flex:1;min-width:0">
        @if($lsPlan)
          <div style="font-size:15px;font-weight:700;color:var(--text)">{{ $lsPlan }}</div>
        @endif
        @if($lsExpiry)
          <div style="font-size:12px;color:var(--muted);margin-top:3px">
            {{ $ls === 'expired' ? 'Expired' : 'Expires' }}:
            <strong style="color:var(--text)">{{ \Carbon\Carbon::parse($lsExpiry)->format('d M Y') }}</strong>
          </div>
        @endif
        @if($lsDays !== null && $lsDays >= 0 && in_array($ls, ['active','trial']))
          <div style="margin-top:8px">
            @php $barW = max(4, min(100, ($lsDays / 365) * 100)); @endphp
            <div style="height:5px;background:#f0e6e8;border-radius:10px;overflow:hidden">
              <div style="height:100%;width:{{ $barW }}%;background:{{ $lsDays <= 7 ? '#dc2626' : $lsBadge['bg'] }};border-radius:10px;transition:width .4s"></div>
            </div>
            <div style="font-size:11px;color:{{ $lsDays <= 7 ? '#dc2626' : 'var(--muted)' }};margin-top:4px;font-weight:600">
              {{ $lsDays }} day{{ $lsDays === 1 ? '' : 's' }} remaining
            </div>
          </div>
        @elseif($ls === 'expired' || $ls === 'trial_expired')
          <a href="https://acadrix.cloud/pricing" target="_blank"
             style="display:inline-block;margin-top:8px;font-size:12px;font-weight:700;background:var(--crimson);color:#fff;padding:5px 14px;border-radius:8px;text-decoration:none">
            Renew Now →
          </a>
        @endif
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Connected</div>
        <div style="font-size:22px;font-weight:800;color:{{ $lsBadge['bg'] }}">
          {{ in_array($ls, ['active','trial']) ? '✓' : '✗' }}
        </div>
      </div>
    </div>
  </div>

  {{-- Pending Resources Card --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title"><i class="fas fa-clock" style="color:var(--warning);margin-right:6px"></i>Pending Resources</span>
      @if($pendingAll > 0)
        <span style="background:#d9770618;color:var(--warning);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
          {{ $pendingAll }} pending
        </span>
      @else
        <span style="background:#0d9e6e18;color:var(--success);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
          <i class="fas fa-check" style="font-size:10px"></i> All clear
        </span>
      @endif
    </div>
    <div class="db-card-body" style="padding:18px 22px">
      <div style="display:flex;flex-direction:column;gap:12px">

        {{-- Pending Students / Provisioning --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface-2);border-radius:10px;border:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:8px;background:#2563eb18;display:flex;align-items:center;justify-content:center">
              <i class="fas fa-user-graduate" style="font-size:13px;color:#2563eb"></i>
            </div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--text)">Pending Provisioning</div>
              <div style="font-size:11px;color:var(--muted)">Clients awaiting setup</div>
            </div>
          </div>
          <div style="font-size:20px;font-weight:800;color:{{ $pendingStu > 0 ? 'var(--warning)' : 'var(--success)' }}">
          {{ $pendingStu }}
          </div>
        </div>

        {{-- Pending Fees --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface-2);border-radius:10px;border:1px solid var(--border)">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:8px;background:#d9770618;display:flex;align-items:center;justify-content:center">
              <i class="fas fa-money-bill-wave" style="font-size:13px;color:var(--warning)"></i>
            </div>
            <div>
              <div style="font-size:13px;font-weight:600;color:var(--text)">Pending Payments</div>
              <div style="font-size:11px;color:var(--muted)">Unreviewed transactions</div>
            </div>
          </div>
          <div style="font-size:20px;font-weight:800;color:{{ $pendingFee > 0 ? 'var(--crimson)' : 'var(--success)' }}">
            {{ $pendingFee }}
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

{{-- ── Row 2: Attendance Chart + Fee Collection ── --}}
<div class="db-row">
  {{-- Attendance Chart --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title">Monthly Attendance Overview</span>
      <div style="display:flex;gap:8px">
        <button class="btn-outline" style="background:var(--crimson);color:#fff;border-color:var(--crimson)">Weekly</button>
        <button class="btn-outline">Monthly</button>
      </div>
    </div>
    <div class="db-card-body">
      <div class="att-chart">
        @foreach($chartData as $d)
        <div class="att-bar-wrap">
          <span class="att-pct">{{ $d['pct'] }}%</span>
          <div class="att-bar present" style="height:{{ max(8,$d['pct']*0.9) }}px"></div>
          <span class="att-label">{{ $d['day'] }}</span>
        </div>
        @endforeach
      </div>
      <div class="att-legend">
        <div class="att-legend-item"><div class="att-legend-dot" style="background:var(--crimson)"></div> Present</div>
        <div class="att-legend-item"><div class="att-legend-dot" style="background:rgba(139,12,34,.2)"></div> Absent</div>
      </div>
    </div>
  </div>

  {{-- Fee Collection Status --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title">Fee Collection Status</span>
      <a href="{{ route('vouchers.index') }}" class="btn-outline"><i class="fas fa-external-link-alt" style="font-size:10px"></i> Details</a>
    </div>
    <div class="db-card-body">
      <div class="fee-donut-wrap">
        <svg class="fee-donut-svg" width="100" height="100" viewBox="0 0 100 100">
          {{-- 72% paid, 18% partial, 10% overdue --}}
          <circle cx="50" cy="50" r="38" fill="none" stroke="#f0e6e8" stroke-width="14"/>
          <circle cx="50" cy="50" r="38" fill="none" stroke="var(--crimson)" stroke-width="14"
                  stroke-dasharray="{{ 72*2.388 }} {{ 100*2.388 }}" stroke-dashoffset="{{ 0.25*2.388*100 }}" stroke-linecap="round"/>
          <circle cx="50" cy="50" r="38" fill="none" stroke="var(--warning)" stroke-width="14"
                  stroke-dasharray="{{ 18*2.388 }} {{ 100*2.388 }}" stroke-dashoffset="{{ -(72*2.388)+(0.25*2.388*100) }}" stroke-linecap="round"/>
          <circle cx="50" cy="50" r="38" fill="none" stroke="#f87171" stroke-width="14"
                  stroke-dasharray="{{ 10*2.388 }} {{ 100*2.388 }}" stroke-dashoffset="{{ -(90*2.388)+(0.25*2.388*100) }}" stroke-linecap="round"/>
          <text x="50" y="48" text-anchor="middle" font-family="Syne,sans-serif" font-size="16" font-weight="800" fill="var(--crimson)">72%</text>
          <text x="50" y="61" text-anchor="middle" font-family="DM Sans,sans-serif" font-size="8" fill="var(--muted)">Collected</text>
        </svg>
        <div style="flex:1">
          <div class="fee-legend-list">
            <div class="fee-legend-row"><div class="fee-leg-dot" style="background:var(--crimson)"></div><span class="fee-leg-label">Paid in Full</span><span class="fee-leg-val">72%</span></div>
            <div class="fee-legend-row"><div class="fee-leg-dot" style="background:var(--warning)"></div><span class="fee-leg-label">Partial Payment</span><span class="fee-leg-val">18%</span></div>
            <div class="fee-legend-row"><div class="fee-leg-dot" style="background:#f87171"></div><span class="fee-leg-label">Overdue</span><span class="fee-leg-val">10%</span></div>
          </div>
          <div class="fee-total-line">
            Total Collected
            <strong>Rs.{{ number_format($totalRevenue) }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Row 3: Recent Admissions + Recent Activity ── --}}
<div class="db-row">
  {{-- Recent Admissions --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title">Recent Admissions</span>
      <a href="{{ route('students.create') }}" class="btn-primary"><i class="fas fa-plus" style="font-size:10px"></i> Add Student</a>
    </div>
    <table class="db-table">
      <thead><tr>
        <th>Student</th><th>Class</th><th>Date</th><th>Status</th>
      </tr></thead>
      <tbody>
        @php $avatarColors = ['#8b0c22','#2563eb','#0d9e6e','#7c3aed','#d97706']; @endphp
        @forelse($recentStudents as $i => $s)
        <tr>
          <td>
            <div class="student-cell">
              <div class="student-avatar" style="background:{{ $avatarColors[$i % count($avatarColors)] }}">{{ strtoupper(substr($s->name,0,2)) }}</div>
              <div>
                <div class="student-name">{{ $s->name }}</div>
                <div class="student-id">{{ $s->student_id }}</div>
              </div>
            </div>
          </td>
          <td style="font-size:13px">{{ $s->schoolClass->name ?? '—' }}</td>
          <td style="font-size:12px;color:var(--muted)">{{ $s->created_at->format('M d, Y') }}</td>
          <td><span class="badge badge-success">Active</span></td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:28px;color:var(--muted);font-size:13px">No students yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Recent Activity --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title">Recent Activity</span>
      <span class="live-badge">Live</span>
    </div>
    <div class="db-card-body">
      <div class="timeline">
        @php
          $dotColors = ['blue','green','yellow','crimson','purple'];
          $dotIcons  = ['fa-user-plus','fa-money-bill','fa-calendar-check','fa-book','fa-book-reader'];
        @endphp
        @forelse($activities as $i => $a)
        <div class="tl-item">
          <div class="tl-dot {{ $dotColors[$i % count($dotColors)] }}"><i class="fas {{ $dotIcons[$i % count($dotIcons)] }}" style="font-size:11px"></i></div>
          <div class="tl-content">
            <div class="tl-desc">{{ $a->description }}</div>
            <div class="tl-meta">{{ $a->created_at->diffForHumans() }} &middot; {{ $a->causer_name ?? 'System' }}</div>
          </div>
        </div>
        @empty
        <p style="color:var(--muted);font-size:13px">No recent activity.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>

{{-- ── Row 4: Quick Actions + Calendar ── --}}
<div class="db-row" style="margin-bottom:22px">
  {{-- Post an Announcement to Teachers --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title"><i class="fas fa-bullhorn" style="color:var(--crimson);margin-right:6px"></i>Post Announcement to Teachers</span>
      <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline btn-sm">Manage All</a>
    </div>
    <div class="db-card-body">
      <form method="POST" action="{{ route('admin.announcements.store') }}">
        @csrf
        <input type="text" name="title" placeholder="Announcement title" required style="width:100%;padding:10px 12px;margin-bottom:8px;border:1px solid var(--border);border-radius:8px">
        <textarea name="content" rows="3" placeholder="Write the announcement..." required style="width:100%;padding:10px 12px;margin-bottom:8px;border:1px solid var(--border);border-radius:8px;resize:vertical"></textarea>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <select name="audience" onchange="document.getElementById('adm-ann-teacher').style.display=(this.value==='specific'?'inline-block':'none')" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px">
            <option value="all">All Teachers</option>
            <option value="specific">Specific Teacher</option>
          </select>
          <select id="adm-ann-teacher" name="teacher_id" style="display:none;padding:8px 10px;border:1px solid var(--border);border-radius:8px">
            @foreach($teachersForWidgets ?? \App\Models\Teacher::where('status','active')->orderBy('name')->get(['id','name']) as $t)
              <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
          </select>
          <label style="display:flex;align-items:center;gap:6px;font-size:12.5px"><input type="checkbox" name="pinned" value="1"> Pin</label>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto"><i class="fas fa-paper-plane"></i> Post</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Unread teacher messages + pending submissions --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title"><i class="fas fa-comments" style="color:var(--info);margin-right:6px"></i>Teacher Inbox</span>
      <a href="{{ route('admin.messages.index') }}" class="btn btn-outline btn-sm">Open Chat</a>
    </div>
    <div class="db-card-body" style="padding:0">
      @forelse(($unreadTeacherMessages ?? collect()) as $m)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border)">
          <div style="width:34px;height:34px;border-radius:50%;background:var(--crimson);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0">{{ strtoupper(substr($m->teacher->name ?? '?',0,2)) }}</div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:13px">{{ $m->teacher->name ?? 'Teacher' }}</div>
            <div class="text-sm text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $m->message }}</div>
          </div>
          <a href="{{ route('admin.messages.show', $m->teacher_id) }}" class="btn btn-outline btn-xs">Reply</a>
        </div>
      @empty
        <div class="empty-state" style="padding:24px"><i class="fas fa-check-circle"></i><p>No unread messages from teachers.</p></div>
      @endforelse
      @if(($pendingSubmissionsCount ?? 0) > 0)
        <div style="padding:12px 20px">
          <a href="{{ route('admin.activities.requests') }}" class="alert alert-warning" style="display:flex;align-items:center;gap:8px;text-decoration:none">
            <i class="fas fa-file-lines"></i> {{ $pendingSubmissionsCount }} paragraph submission(s) awaiting your review — open Activity Requests
          </a>
        </div>
      @endif
    </div>
  </div>
</div>

<div class="db-bottom">
  {{-- Quick Actions --}}
  <div class="db-card">
    <div class="db-card-header"><span class="db-card-title">Quick Actions</span></div>
    <div class="db-card-body">
      <div class="qa-grid">
        @foreach([
          ['Add Student',      'fas fa-user-plus',       'students.create',   '#8b0c22'],
          ['Take Attendance',  'fas fa-clipboard-check', 'admin.attendance.index',  '#2563eb'],
          ['Collect Fee',      'fas fa-money-bill-wave', 'vouchers.index',        '#0d9e6e'],
          ['Enter Marks',      'fas fa-pen-nib',         'admin.reports.index',     '#7c3aed'],
          ['Message Teacher',  'fas fa-comments',        'admin.messages.index',   '#2563eb'],
          ['Assign Activity',  'fas fa-diagram-project',  'admin.activities.index', '#0d9e6e'],
          ['View Reports',     'fas fa-chart-bar',       'admin.reports.index',     '#64748b'],
          ['Generate ID',      'fas fa-id-card',         'students.index',    '#0891b2'],
        ] as $q)
        <a href="{{ route($q[2]) }}" class="qa-btn">
          <div class="qa-icon" style="background:{{ $q[3] }}18;color:{{ $q[3] }}"><i class="{{ $q[1] }}"></i></div>
          <span class="qa-label">{{ $q[0] }}</span>
        </a>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Calendar --}}
  <div class="db-card">
    <div class="db-card-header">
      <span class="db-card-title" id="calMonthLabel">{{ now()->format('F Y') }}</span>
      <div style="display:flex;gap:6px;align-items:center">
        <button class="btn-outline" style="padding:5px 10px" onclick="calNav(-1)"><i class="fas fa-chevron-left" style="font-size:10px"></i></button>
        <button class="btn-outline" style="padding:5px 10px" onclick="calNav(1)"><i class="fas fa-chevron-right" style="font-size:10px"></i></button>
        <button class="btn-primary" style="padding:6px 14px;font-size:12px;border-radius:8px;gap:5px" onclick="openEventModal()">
          <i class="fas fa-plus" style="font-size:10px"></i> Add Event
        </button>
      </div>
    </div>
    <div class="db-card-body">
      {{-- Calendar Grid --}}
      <div class="cal-grid" id="calGrid">
        @php
          $dayNames = ['Su','Mo','Tu','We','Th','Fr','Sa'];
        @endphp
        @foreach($dayNames as $d)
          <div class="cal-day-name">{{ $d }}</div>
        @endforeach
        {{-- Days injected by JS --}}
      </div>

      {{-- Upcoming Events --}}
      <div style="margin-top:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)">Upcoming Events</div>
          <span id="eventCount" style="font-size:11px;color:var(--muted)"></span>
        </div>
        <div id="eventsList"></div>
      </div>
    </div>
  </div>
</div>

{{-- ── Add Event Modal ── --}}
<div id="eventModalBackdrop" style="display:none;position:fixed;inset:0;background:rgba(15,5,7,.48);backdrop-filter:blur(4px);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--surface);border-radius:18px;width:100%;max-width:460px;margin:20px;box-shadow:0 24px 60px rgba(139,12,34,.22);overflow:hidden;animation:evtIn .25s cubic-bezier(.34,1.56,.64,1)">
    <style>@keyframes evtIn{from{opacity:0;transform:scale(.93) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}</style>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border)">
      <span style="font-family:'Outfit',sans-serif;font-size:17px;font-weight:800">Add Calendar Event</span>
      <button onclick="closeEventModal()" style="width:32px;height:32px;border-radius:8px;border:none;background:var(--crimson-soft);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;transition:background .12s,color .12s" onmouseover="this.style.background='var(--crimson)';this.style.color='#fff'" onmouseout="this.style.background='var(--crimson-soft)';this.style.color='var(--muted)'">&times;</button>
    </div>
    <div style="padding:20px 24px">
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Event Title *</label>
        <input id="evtTitle" type="text" placeholder="e.g. Parent–Teacher Meeting"
          style="width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:'Outfit',sans-serif;font-size:13.5px;transition:border-color .15s;outline:none"
          onfocus="this.style.borderColor='var(--crimson)'" onblur="this.style.borderColor='var(--border)'">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
        <div>
          <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Date *</label>
          <input id="evtDate" type="date"
            style="width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:'Outfit',sans-serif;font-size:13px;transition:border-color .15s;outline:none"
            onfocus="this.style.borderColor='var(--crimson)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Color</label>
          <select id="evtColor"
            style="width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:'Outfit',sans-serif;font-size:13px;cursor:pointer;outline:none;transition:border-color .15s"
            onfocus="this.style.borderColor='var(--crimson)'" onblur="this.style.borderColor='var(--border)'">
            <option value="#8b0c22">🔴 Crimson</option>
            <option value="#2563eb">🔵 Blue</option>
            <option value="#0d9e6e">🟢 Green</option>
            <option value="#d97706">🟡 Amber</option>
            <option value="#7c3aed">🟣 Purple</option>
            <option value="#0891b2">🩵 Teal</option>
          </select>
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Time (optional)</label>
        <input id="evtTime" type="text" placeholder="e.g. 9:00 AM – 3:00 PM"
          style="width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:'Outfit',sans-serif;font-size:13.5px;transition:border-color .15s;outline:none"
          onfocus="this.style.borderColor='var(--crimson)'" onblur="this.style.borderColor='var(--border)'">
      </div>
      <div>
        <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px">Note (optional)</label>
        <input id="evtNote" type="text" placeholder="e.g. All classes, Auditorium"
          style="width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:'Outfit',sans-serif;font-size:13.5px;transition:border-color .15s;outline:none"
          onfocus="this.style.borderColor='var(--crimson)'" onblur="this.style.borderColor='var(--border)'">
      </div>
      <div id="evtError" style="display:none;margin-top:10px;font-size:12.5px;color:var(--crimson);background:rgba(139,12,34,.07);padding:8px 12px;border-radius:8px"></div>
    </div>
    <div style="padding:16px 24px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
      <button onclick="closeEventModal()" style="padding:9px 18px;border-radius:9px;border:1.5px solid var(--border);background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:13px;font-weight:600;cursor:pointer">Cancel</button>
      <button onclick="saveEvent()" class="btn-primary" style="padding:9px 20px;border-radius:9px;font-size:13px">
        <i class="fas fa-calendar-plus" style="font-size:11px"></i> Save Event
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
// ── State ──────────────────────────────────────────────────────────────────
let calYear  = {{ now()->year }};
let calMonth = {{ now()->month - 1 }}; // 0-indexed
const todayD = {{ now()->day }};
const todayM = {{ now()->month - 1 }};
const todayY = {{ now()->year }};

// Load events from localStorage
let events = [];
try { events = JSON.parse(localStorage.getItem('cal_events') || '[]'); } catch(e) {}

const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];

// ── Calendar render ────────────────────────────────────────────────────────
function renderCalendar() {
  const grid     = document.getElementById('calGrid');
  const label    = document.getElementById('calMonthLabel');
  const daysInM  = new Date(calYear, calMonth + 1, 0).getDate();
  const firstDow = new Date(calYear, calMonth, 1).getDay();

  label.textContent = MONTH_NAMES[calMonth] + ' ' + calYear;

  // Keep the 7 day-name headers, remove old day cells
  while (grid.children.length > 7) grid.removeChild(grid.lastChild);

  // Leading empty cells
  for (let i = 0; i < firstDow; i++) {
    const el = document.createElement('div');
    el.className = 'cal-day other';
    const prevDays = new Date(calYear, calMonth, 0).getDate();
    el.textContent = prevDays - firstDow + 1 + i;
    grid.appendChild(el);
  }

  for (let d = 1; d <= daysInM; d++) {
    const el      = document.createElement('div');
    const dateStr = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
    const hasEvt  = events.some(e => e.date === dateStr);
    const isToday = d === todayD && calMonth === todayM && calYear === todayY;

    el.className = 'cal-day' + (isToday ? ' today' : '') + (hasEvt ? ' has-event' : '');
    el.textContent = d;
    el.style.cursor = 'pointer';
    el.title = hasEvt ? 'Has event – click to add' : 'Click to add event';
    el.addEventListener('click', () => {
      document.getElementById('evtDate').value = dateStr;
      openEventModal();
    });
    grid.appendChild(el);
  }

  renderEventsList();
}

// ── Events list ────────────────────────────────────────────────────────────
function renderEventsList() {
  const list    = document.getElementById('eventsList');
  const counter = document.getElementById('eventCount');
  const now     = new Date(calYear, calMonth, 1);
  const end     = new Date(calYear, calMonth + 1, 0);

  const monthEvts = events
    .filter(e => {
      const d = new Date(e.date);
      return d >= now && d <= end;
    })
    .sort((a,b) => a.date.localeCompare(b.date));

  counter.textContent = monthEvts.length ? monthEvts.length + ' event' + (monthEvts.length > 1 ? 's' : '') : '';

  if (!monthEvts.length) {
    list.innerHTML = `<div style="text-align:center;padding:20px 0;color:var(--muted);font-size:13px">
      <i class="fas fa-calendar-plus" style="font-size:22px;opacity:.25;display:block;margin-bottom:8px"></i>
      No events this month. Click a date or "+ Add Event".
    </div>`;
    return;
  }

  list.innerHTML = monthEvts.map((evt, idx) => {
    const d     = new Date(evt.date + 'T00:00:00');
    const day   = d.getDate();
    const month = MONTH_NAMES[d.getMonth()].substring(0,3);
    return `
    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:10px;border-left:3px solid ${evt.color};background:${evt.color}0d;margin-bottom:7px;transition:background .12s"
         onmouseover="this.style.background='${evt.color}1a'" onmouseout="this.style.background='${evt.color}0d'">
      <div style="flex-shrink:0;text-align:center;min-width:34px">
        <div style="font-size:16px;font-weight:800;line-height:1;color:${evt.color};font-family:'Outfit',sans-serif">${day}</div>
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600">${month}</div>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:700;color:var(--text)">${evt.title}</div>
        ${evt.time || evt.note ? `<div style="font-size:11.5px;color:var(--muted);margin-top:2px">${[evt.time, evt.note].filter(Boolean).join(' · ')}</div>` : ''}
      </div>
      <button onclick="deleteEvent(${idx})" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:12px;padding:2px 4px;border-radius:5px;opacity:.5;transition:opacity .12s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.5'" title="Delete">
        <i class="fas fa-times"></i>
      </button>
    </div>`;
  }).join('');
}

// ── Nav ────────────────────────────────────────────────────────────────────
function calNav(dir) {
  calMonth += dir;
  if (calMonth > 11) { calMonth = 0; calYear++; }
  if (calMonth < 0)  { calMonth = 11; calYear--; }
  renderCalendar();
}

// ── Modal ──────────────────────────────────────────────────────────────────
function openEventModal(prefillDate) {
  if (prefillDate) document.getElementById('evtDate').value = prefillDate;
  document.getElementById('evtError').style.display = 'none';
  document.getElementById('eventModalBackdrop').style.display = 'flex';
}
function closeEventModal() {
  document.getElementById('eventModalBackdrop').style.display = 'none';
  document.getElementById('evtTitle').value = '';
  document.getElementById('evtDate').value  = '';
  document.getElementById('evtTime').value  = '';
  document.getElementById('evtNote').value  = '';
}

// ── Save ───────────────────────────────────────────────────────────────────
function saveEvent() {
  const title = document.getElementById('evtTitle').value.trim();
  const date  = document.getElementById('evtDate').value;
  const color = document.getElementById('evtColor').value;
  const time  = document.getElementById('evtTime').value.trim();
  const note  = document.getElementById('evtNote').value.trim();
  const err   = document.getElementById('evtError');

  if (!title) { err.textContent = 'Please enter an event title.'; err.style.display='block'; return; }
  if (!date)  { err.textContent = 'Please select a date.'; err.style.display='block'; return; }

  events.push({ title, date, color, time, note });
  localStorage.setItem('cal_events', JSON.stringify(events));

  // Navigate to that month
  const d = new Date(date + 'T00:00:00');
  calYear  = d.getFullYear();
  calMonth = d.getMonth();

  closeEventModal();
  renderCalendar();
}

// ── Delete ─────────────────────────────────────────────────────────────────
function deleteEvent(idx) {
  // Find actual index in global events array matching the filtered view
  const now = new Date(calYear, calMonth, 1);
  const end = new Date(calYear, calMonth + 1, 0);
  const monthEvts = events
    .map((e, i) => ({...e, _i: i}))
    .filter(e => { const d = new Date(e.date); return d >= now && d <= end; })
    .sort((a,b) => a.date.localeCompare(b.date));
  const realIdx = monthEvts[idx]?._i;
  if (realIdx !== undefined) {
    events.splice(realIdx, 1);
    localStorage.setItem('cal_events', JSON.stringify(events));
    renderCalendar();
  }
}

// ── Close on backdrop click / Escape ──────────────────────────────────────
document.getElementById('eventModalBackdrop').addEventListener('click', function(e) {
  if (e.target === this) closeEventModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEventModal(); });

// ── Init ───────────────────────────────────────────────────────────────────
renderCalendar();
</script>
@endpush

@endsection
