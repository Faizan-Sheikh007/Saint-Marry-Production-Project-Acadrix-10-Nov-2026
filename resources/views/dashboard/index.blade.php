@extends('layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('content')

<style>
  .db-hero {
    background: linear-gradient(135deg,#5c0718 0%,#8b0c22 55%,#c0192e 100%);
    border-radius:18px; padding:30px 34px; margin-bottom:22px; color:#fff;
    position: relative; overflow: hidden;
  }
  .db-hero::before {
    content:''; position:absolute; inset:0;
    background-image: radial-gradient(rgba(255,255,255,.07) 1.5px, transparent 1.5px);
    background-size: 22px 22px;
  }
  .db-hero::after {
    content:''; position:absolute; right:-70px; top:-70px; width:260px; height:260px;
    background: radial-gradient(circle, rgba(255,215,106,.12) 0%, transparent 70%);
    border-radius:50%;
  }
  .db-hero-inner { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
  .db-hero-date { font-size:12px; opacity:.75; letter-spacing:.08em; text-transform:uppercase; font-weight:600; }
  .db-hero-title { font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; margin:6px 0 6px; }
  .db-hero-sub { font-size:13.5px; opacity:.8; }
  .db-hero-right { display:flex; gap:14px; flex-wrap:wrap; }
  .db-hero-stat {
    text-align:center; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16);
    border-radius:14px; padding:12px 22px; backdrop-filter: blur(8px);
  }
  .db-hero-stat-val { font-family:'Outfit',sans-serif; font-size:21px; font-weight:800; }
  .db-hero-stat-lbl { font-size:10.5px; opacity:.75; margin-top:3px; letter-spacing:.03em; }

  .db-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
  @media (max-width:900px) { .db-stats { grid-template-columns:repeat(2,1fr); } }
  .stat-card {
    background:var(--surface,#fff); border:1px solid var(--border); border-radius:16px;
    padding:20px 20px; display:flex; gap:14px; align-items:center;
    box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 8px 20px rgba(137,12,37,.04);
    transition: transform .18s ease, box-shadow .18s ease;
    position: relative; overflow: hidden;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(137,12,37,.12); }
  .stat-icon-wrap {
    width:48px; height:48px; border-radius:13px; display:flex; align-items:center; justify-content:center;
    flex-shrink:0; color:#fff;
  }
  .stat-icon-wrap svg { width: 22px; height: 22px; }
  .stat-icon-wrap.red    { background:linear-gradient(135deg,#a81030,#5c0718); box-shadow:0 6px 16px rgba(137,12,37,.35); }
  .stat-icon-wrap.green  { background:linear-gradient(135deg,#12b886,#0a7a58); box-shadow:0 6px 16px rgba(13,158,110,.32); }
  .stat-icon-wrap.orange { background:linear-gradient(135deg,#f5a524,#c67505); box-shadow:0 6px 16px rgba(217,119,6,.3); }
  .stat-icon-wrap.blue   { background:linear-gradient(135deg,#3b82f6,#1d4ed8); box-shadow:0 6px 16px rgba(37,99,235,.3); }
  .stat-val { font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; line-height:1.15; }
  .stat-lbl { font-size:12px; color:var(--text-muted); margin-top:3px; font-weight:500; }

  .db-bottom { display:grid; grid-template-columns:1.3fr 1fr; gap:16px; }
  @media (max-width:900px) { .db-bottom { grid-template-columns:1fr; } }
  .db-card { background:var(--surface,#fff); border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
  .db-card-header { display:flex; align-items:center; justify-content:space-between; padding:17px 20px 13px; border-bottom:1px solid var(--border); }
  .db-card-title { font-family:'Outfit',sans-serif; font-size:14.5px; font-weight:700; display:flex; align-items:center; gap:8px; }
  .db-card-body { padding:18px 20px; }

  .qa-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
  @media (max-width:900px) { .qa-grid { grid-template-columns:repeat(2,1fr); } }
  @media (max-width:480px) { .qa-grid { grid-template-columns:1fr; } }
  .qa-btn {
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px;
    padding:20px 10px; border-radius:14px; border:1.5px solid var(--border);
    background: linear-gradient(180deg, #fff 0%, #fdf8f9 100%);
    text-decoration:none; color:var(--text);
    transition: border-color .18s, transform .18s, box-shadow .18s;
  }
  .qa-btn:hover { transform:translateY(-3px); border-color:transparent; box-shadow: 0 10px 24px rgba(137,12,37,.18); }
  .qa-icon {
    width:42px; height:42px; border-radius:12px;
    background:linear-gradient(135deg,#a81030,#6b0a1e); color:#fff;
    display:flex; align-items:center; justify-content:center;
  }
  .qa-icon svg { width: 19px; height: 19px; }
  .qa-btn .qa-icon.qa-green  { background:linear-gradient(135deg,#12b886,#0a7a58); }
  .qa-btn .qa-icon.qa-orange { background:linear-gradient(135deg,#f5a524,#c67505); }
  .qa-btn .qa-icon.qa-blue   { background:linear-gradient(135deg,#3b82f6,#1d4ed8); }
  .qa-btn .qa-icon.qa-teal   { background:linear-gradient(135deg,#0891b2,#0e7490); }
  .qa-btn .qa-icon.qa-purple { background:linear-gradient(135deg,#8b5cf6,#6d28d9); }
  .qa-label { font-size:12px; font-weight:700; text-align:center; }

  .db-table { width:100%; border-collapse:collapse; }
  .db-table th { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); padding:0 0 10px; text-align:left; }
  .db-table td { padding:11px 0; border-top:1px solid var(--border); font-size:13px; }
  .pay-student { display:flex; align-items:center; gap:9px; }
  .pay-avatar {
    width:28px; height:28px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,#a81030,#6b0a1e); color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700;
  }

  .timeline { display:flex; flex-direction:column; gap:0; }
  .tl-item { display:flex; gap:12px; padding:10px 0; }
  .tl-dot {
    width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    color:#fff; flex-shrink:0; background:linear-gradient(135deg,#a81030,#6b0a1e);
  }
  .tl-dot svg { width: 12px; height: 12px; }
  .tl-desc { font-size:12.5px; line-height:1.4; }
  .tl-meta { font-size:10.5px; color:var(--text-muted); margin-top:2px; }

  .empty-illus { text-align:center; padding:30px 10px; color:var(--text-muted); }
  .empty-illus svg { opacity:.55; margin-bottom:10px; }


  /* ── Student Accounts Explorer ───────────────────────────────────── */
  .ax-explorer-card { margin-bottom:20px; min-width:0; max-width:100%; }
  .ax-explorer-header { align-items:flex-start; gap:18px; }
  .ax-explorer-heading { display:flex; align-items:flex-start; gap:12px; }
  .ax-explorer-heading-icon {
    width:42px; height:42px; border-radius:12px; flex:0 0 auto;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,rgba(137,12,37,.14),rgba(137,12,37,.05));
    color:var(--primary,#890c25); font-size:17px;
  }
  .ax-explorer-title { font-family:'Outfit',sans-serif; font-size:16px; font-weight:800; color:var(--text); }
  .ax-explorer-help { margin-top:4px; font-size:12px; color:var(--text-muted); line-height:1.5; }
  .ax-explorer-all-link { font-size:12px; font-weight:700; color:var(--primary); white-space:nowrap; }

  .ax-program-list { padding:16px; display:flex; flex-direction:column; gap:10px; min-width:0; max-width:100%; background:linear-gradient(180deg,var(--card,#fff),var(--bg)); }
  .ax-program-item { border:1px solid var(--border); border-radius:14px; background:var(--card,#fff); overflow:hidden; min-width:0; max-width:100%; transition:border-color .16s, box-shadow .16s; }
  .ax-program-item.is-open { border-color:rgba(137,12,37,.28); box-shadow:0 8px 24px rgba(59,20,28,.07); }
  .ax-program-toggle {
    width:100%; border:0; background:transparent; color:var(--text); cursor:pointer;
    display:grid; grid-template-columns:minmax(0,1fr) auto auto; align-items:center; gap:14px;
    padding:16px 18px; text-align:left; font-family:inherit;
  }
  .ax-program-toggle:hover { background:rgba(137,12,37,.025); }
  .ax-program-main { display:flex; align-items:center; gap:12px; min-width:0; }
  .ax-program-icon {
    width:38px; height:38px; border-radius:11px; background:var(--bg); color:var(--primary);
    display:flex; align-items:center; justify-content:center; flex:0 0 auto;
  }
  .ax-program-name { font-size:14px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .ax-program-meta { margin-top:3px; font-size:11px; color:var(--text-muted); display:flex; gap:8px; flex-wrap:wrap; }
  .ax-program-counts { display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
  .ax-count-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:999px; background:var(--bg); color:var(--text-muted); font-size:10.5px; font-weight:700; }
  .ax-program-chevron { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text-muted); transition:transform .18s; }
  .ax-program-item.is-open .ax-program-chevron { transform:rotate(180deg); color:var(--primary); }
  .ax-program-body { border-top:1px solid var(--border); padding:0; min-width:0; max-width:100%; background:var(--bg); }

  .ax-stage-heading { display:flex; align-items:center; gap:10px; padding:15px 16px 10px; }
  .ax-stage-heading > span:last-child { display:flex; flex-direction:column; gap:2px; }
  .ax-stage-heading strong { font-size:12px; color:var(--text); font-weight:800; }
  .ax-stage-heading small { font-size:10.5px; color:var(--text-muted); font-weight:500; }
  .ax-stage-badge { width:24px; height:24px; border-radius:8px; background:var(--primary); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; flex:0 0 auto; box-shadow:0 4px 10px rgba(137,12,37,.16); }

  .ax-session-stack {
    padding:0 14px 14px;
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    align-items:start;
  }
  .ax-session-item {
    min-width:0;
    border:1px solid var(--border);
    border-radius:14px;
    overflow:hidden;
    background:var(--card,#fff);
    transition:border-color .16s,box-shadow .16s,transform .16s;
  }
  .ax-session-item:not(.is-open):hover {
    transform:translateY(-2px);
    border-color:rgba(137,12,37,.28);
    box-shadow:0 8px 20px rgba(59,20,28,.06);
  }
  .ax-session-item.is-open {
    grid-column:1 / -1;
    border-color:rgba(137,12,37,.28);
    box-shadow:0 7px 20px rgba(59,20,28,.055);
  }
  .ax-session-item.is-selected {
    border-color:var(--primary);
    box-shadow:0 0 0 2px rgba(137,12,37,.08),0 8px 20px rgba(59,20,28,.06);
  }
  .ax-session-item.is-selected > .ax-session-toggle { background:rgba(137,12,37,.04); }
  .ax-session-item.is-selected .ax-session-chevron { color:var(--primary); transform:translateX(2px); }
  .ax-session-toggle {
    width:100%; border:0; background:transparent; color:var(--text); cursor:pointer; font-family:inherit;
    display:grid; grid-template-columns:auto minmax(0,1fr) auto auto; align-items:center; gap:11px; padding:12px 13px; text-align:left;
  }
  .ax-session-toggle:hover { background:rgba(137,12,37,.025); }
  .ax-session-icon { width:34px; height:34px; border-radius:9px; background:rgba(137,12,37,.08); color:var(--primary); display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
  .ax-session-copy { min-width:0; display:flex; flex-direction:column; gap:2px; }
  .ax-session-name { font-size:12.5px; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .ax-session-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:10.5px; color:var(--text-muted); }
  .ax-default-label { display:inline-flex; align-items:center; gap:4px; color:#0d9e6e; font-weight:700; }
  .ax-session-students { min-width:78px; text-align:right; color:var(--text); font-size:11.5px; font-weight:800; }
  .ax-session-students span { color:var(--text-muted); font-size:10px; font-weight:600; }
  .ax-session-chevron { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text-muted); transition:transform .18s,color .18s; }
  .ax-session-item.is-open .ax-session-chevron { transform:rotate(180deg); color:var(--primary); }
  .ax-session-item.is-open > .ax-session-toggle { background:rgba(137,12,37,.025); }
  .ax-session-panel { border-top:1px solid var(--border); background:linear-gradient(180deg,var(--bg),var(--card,#fff)); padding:0 12px 12px; }
  .ax-stage-heading-semester { padding-left:4px; padding-right:4px; }
  .ax-stage-heading-semester .ax-stage-badge { background:#64748b; box-shadow:none; }

  .ax-semester-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:9px; }
  .ax-term-btn {
    min-height:64px; border:1px solid var(--border); background:var(--card,#fff); border-radius:11px; padding:11px 12px;
    display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:10px; text-align:left; color:var(--text); cursor:pointer; font-family:inherit;
    transition:transform .15s,border-color .15s,box-shadow .15s,background .15s;
  }
  .ax-term-btn:hover { transform:translateY(-1px); border-color:rgba(137,12,37,.42); box-shadow:0 7px 18px rgba(59,20,28,.06); }
  .ax-term-btn.is-active { border-color:var(--primary); box-shadow:0 0 0 2px rgba(137,12,37,.08); background:rgba(137,12,37,.035); }
  .ax-term-icon { width:34px; height:34px; border-radius:9px; background:rgba(137,12,37,.08); color:var(--primary); display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
  .ax-term-copy { min-width:0; display:flex; flex-direction:column; gap:2px; }
  .ax-term-title { font-size:12.5px; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .ax-term-count { font-size:10.5px; color:var(--text-muted); }
  .ax-term-arrow { color:var(--text-muted); font-size:10px; transition:transform .15s,color .15s; }
  .ax-term-btn:hover .ax-term-arrow,.ax-term-btn.is-active .ax-term-arrow { color:var(--primary); transform:translateX(2px); }
  .ax-no-sessions { display:flex; align-items:center; gap:9px; font-size:12px; color:var(--text-muted); padding:14px 16px; }

  .ax-detail-shell { margin:2px 6px 8px; border:1px solid rgba(137,12,37,.18); border-radius:15px; overflow:hidden; min-width:0; max-width:calc(100% - 12px); background:var(--card,#fff); box-shadow:0 12px 32px rgba(59,20,28,.06); }
  .ax-detail-top { padding:16px 18px; border-bottom:1px solid var(--border); background:linear-gradient(135deg,rgba(137,12,37,.055),transparent 70%); }
  .ax-selection-breadcrumb { display:flex; align-items:center; gap:7px; flex-wrap:wrap; font-size:12px; color:var(--text-muted); }
  .ax-selection-breadcrumb strong { color:var(--text); }
  .ax-selection-breadcrumb i { font-size:8px; opacity:.65; }
  .ax-view-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-top:13px; }
  .ax-view-tab {
    border:1px solid var(--border); border-radius:9px; background:var(--card,#fff); color:var(--text-muted);
    padding:8px 12px; cursor:pointer; font:inherit; font-size:11.5px; font-weight:800; display:inline-flex; align-items:center; gap:7px;
  }
  .ax-view-tab:hover { color:var(--primary); border-color:rgba(137,12,37,.35); }
  .ax-view-tab.is-active { color:#fff; background:linear-gradient(135deg,#8b0c22,#5c0718); border-color:transparent; box-shadow:0 5px 14px rgba(137,12,37,.2); }

  .ax-detail-tools { padding:12px 18px; display:flex; align-items:center; gap:10px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
  .ax-filter-control { min-width:210px; display:flex; flex-direction:column; gap:4px; }
  .ax-filter-control label { font-size:10.5px; font-weight:800; color:var(--text-muted); display:flex; align-items:center; gap:6px; }
  .ax-filter-control select { height:38px; min-width:210px; font-size:11.5px; }
  .ax-selection-bar { padding:10px 18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; border-bottom:1px solid var(--border); background:rgba(100,116,139,.035); }
  .ax-select-all-control { display:inline-flex; align-items:center; gap:8px; font-size:11.5px; font-weight:800; color:var(--text); cursor:pointer; }
  .ax-select-all-control input,.ax-student-checkbox { width:16px; height:16px; accent-color:var(--primary); cursor:pointer; }
  .ax-selected-count { font-size:11px; font-weight:800; color:var(--primary); padding:5px 9px; border-radius:999px; background:rgba(137,12,37,.07); }
  .ax-selection-bar .btn:first-of-type { margin-left:auto; }
  .ax-search-wrap { position:relative; flex:1; min-width:220px; }
  .ax-search-wrap i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:12px; }
  .ax-search-input { width:100%; padding-left:34px !important; height:38px; }
  .ax-search-reset { display:none; }
  .ax-search-reset.is-visible { display:inline-flex; }
  .ax-table-zone { min-height:150px; position:relative; min-width:0; max-width:100%; overflow:hidden; }
  .ax-loading { padding:36px 18px; display:flex; align-items:center; justify-content:center; gap:10px; color:var(--text-muted); font-size:12px; }
  .ax-loading-spinner { width:18px; height:18px; border:2px solid var(--border); border-top-color:var(--primary); border-radius:50%; animation:axSpin .7s linear infinite; }
  @keyframes axSpin { to { transform:rotate(360deg); } }

  .ax-explorer-result-meta { padding:10px 16px; display:flex; justify-content:space-between; gap:10px; align-items:center; background:var(--bg); border-bottom:1px solid var(--border); font-size:11.5px; color:var(--text-muted); }
  .ax-explorer-result-meta strong { color:var(--text); }
  .ax-explorer-table-wrap {
    width:100%; max-width:100%; min-width:0; max-height:560px;
    overflow:auto; -webkit-overflow-scrolling:touch; overscroll-behavior:contain;
    scrollbar-gutter:stable both-edges;
  }
  .ax-explorer-table { width:100%; border-collapse:separate; border-spacing:0; }
  .ax-explorer-table th { position:sticky; top:0; z-index:2; background:var(--card,#fff); padding:10px 9px; font-size:10px; white-space:nowrap; }
  .ax-explorer-table td { padding:10px 9px; font-size:11.5px; vertical-align:middle; white-space:nowrap; }
  /* Keep the page inside the viewport. Wider views scroll only inside this table area. */
  .ax-student-details-table { min-width:1510px !important; }
  .ax-enrollment-table { min-width:1430px !important; }
  .ax-fees-table { min-width:1320px !important; }
  /* Actions always remain visible at the right edge while the data area scrolls. */
  .ax-explorer-table th:last-child,
  .ax-explorer-table td:last-child {
    position:sticky; right:0; background:var(--card,#fff); z-index:3;
    box-shadow:-10px 0 14px -16px rgba(15,23,42,.55);
  }
  .ax-explorer-table th:last-child { z-index:5; }
  .ax-select-col { width:58px; text-align:center !important; }
  .ax-sr { width:50px; color:var(--text-muted); text-align:center; font-weight:700; }
  .ax-id-chip { display:inline-flex; padding:4px 7px; border-radius:7px; background:var(--bg); font-size:10.5px; font-weight:800; color:var(--text); }
  .ax-student-photo { width:36px; height:36px; border-radius:10px; object-fit:cover; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); }
  .ax-student-photo-fallback { background:linear-gradient(135deg,#8b0c22,#5c0718); color:#fff; font-size:10px; font-weight:800; border:0; }
  .ax-student-name { font-size:12.5px; font-weight:800; color:var(--text); }
  .ax-student-name-sm { font-size:11.5px; }
  .ax-student-sub { font-size:10px; color:var(--text-muted); margin-top:2px; }
  .ax-soft-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:7px; background:rgba(59,130,246,.07); color:#2563eb; font-size:10.5px; font-weight:700; }
  .ax-money { text-align:right; font-weight:800; font-variant-numeric:tabular-nums; }
  .ax-money-due { color:var(--danger); }
  .ax-money-clear, .ax-money-paid { color:var(--success); }
  .ax-row-action { justify-content:center; }
  .ax-last-paid { display:inline-flex; align-items:center; gap:7px; color:var(--text); }
  .ax-last-paid > i { width:25px; height:25px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:rgba(13,158,110,.09); color:var(--success); font-size:10px; }
  .ax-last-paid span { display:flex; flex-direction:column; line-height:1.15; }
  .ax-last-paid strong { font-size:11px; font-weight:800; }
  .ax-last-paid small { display:none; margin-top:2px; font-size:9.5px; color:var(--text-muted); font-weight:600; }
  .ax-last-paid-none { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted); font-size:10.5px; font-weight:700; }
  .ax-student-actions { display:flex; align-items:center; gap:5px; min-width:0; }
  .ax-mini-action { height:30px; padding:0 7px; border-radius:8px; border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; gap:4px; text-decoration:none; font-size:9.5px; font-weight:800; white-space:nowrap; transition:transform .14s ease,border-color .14s ease,background .14s ease,box-shadow .14s ease; }
  .ax-mini-action:hover { transform:translateY(-1px); box-shadow:0 5px 12px rgba(15,23,42,.08); }
  .ax-mini-action-pending { color:#b45309; background:rgba(245,158,11,.07); border-color:rgba(245,158,11,.22); }
  .ax-mini-action-pending:hover { color:#92400e; background:rgba(245,158,11,.13); border-color:rgba(245,158,11,.38); }
  .ax-mini-action-paid { color:#0b8f64; background:rgba(13,158,110,.07); border-color:rgba(13,158,110,.20); }
  .ax-mini-action-paid:hover { color:#087253; background:rgba(13,158,110,.12); border-color:rgba(13,158,110,.34); }
  .ax-mini-action-profile { color:var(--primary); background:rgba(137,12,37,.055); border-color:rgba(137,12,37,.16); }
  .ax-mini-action-profile:hover { background:rgba(137,12,37,.10); border-color:rgba(137,12,37,.3); }
  .ax-empty-cell { text-align:center !important; padding:44px 20px !important; color:var(--text-muted); }
  .ax-empty-cell > i { display:block; font-size:26px; opacity:.35; margin-bottom:8px; }
  .ax-empty-cell > strong { display:block; color:var(--text); font-size:12.5px; margin-bottom:3px; }
  .ax-empty-cell > span { display:block; font-size:11px; }
  .ax-explorer-pagination { padding:12px 16px; border-top:1px solid var(--border); background:var(--card,#fff); }
  .ax-explorer-pagination nav { margin:0; }

  @media (max-width:1550px) {
    .ax-mini-action { width:30px; padding:0; }
    .ax-mini-action span { display:none; }
  }
  @media (max-width:1240px) {
    .ax-session-stack { grid-template-columns:repeat(3,minmax(0,1fr)); }
  }
  @media (max-width:980px) {
    .ax-session-stack { grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media (max-width:760px) {
    .ax-explorer-header { flex-direction:column; }
    .ax-explorer-all-link { margin-left:54px; }
    .ax-program-toggle { grid-template-columns:minmax(0,1fr) auto; gap:8px; padding:14px; }
    .ax-program-counts { grid-column:1 / -1; justify-content:flex-start; padding-left:50px; }
    .ax-program-chevron { grid-column:2; grid-row:1; }
    .ax-program-body { padding:0; }
    .ax-semester-grid { grid-template-columns:1fr 1fr; }
    .ax-session-toggle { grid-template-columns:auto minmax(0,1fr) auto; }
    .ax-session-students { display:none; }
    .ax-detail-shell { margin:2px 0 8px; }
    .ax-detail-tools { align-items:stretch; }
    .ax-filter-control,.ax-filter-control select { min-width:100%; width:100%; }
    .ax-search-wrap { min-width:100%; }
    .ax-selection-bar .btn:first-of-type { margin-left:0; }
  }
  @media (max-width:480px) {
    .ax-program-list { padding:10px; }
    .ax-semester-grid { grid-template-columns:1fr; }
    .ax-session-stack { padding-left:10px; padding-right:10px; grid-template-columns:1fr; }
    .ax-view-tab { flex:1; justify-content:center; min-width:130px; }
    .ax-detail-top,.ax-detail-tools { padding-left:12px; padding-right:12px; }
    .ax-explorer-result-meta { align-items:flex-start; flex-direction:column; }
  }

</style>

{{-- Hero --}}
<div class="db-hero">
  <div class="db-hero-inner">
    <div>
      <div class="db-hero-date">{{ now()->format('l, F j Y') }}</div>
      <h1 class="db-hero-title">Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ explode(' ', auth()->user()->name)[0] }}! 👋</h1>
      <p class="db-hero-sub">Welcome back to Acadrix Accounts — here's today's snapshot.</p>
    </div>
    <div class="db-hero-right">
      <div class="db-hero-stat">
        <div class="db-hero-stat-val">Rs. {{ number_format($totalCollected/1000, 1) }}K</div>
        <div class="db-hero-stat-lbl">TOTAL COLLECTED</div>
      </div>
      <div class="db-hero-stat">
        <div class="db-hero-stat-val">{{ $overdueCount }}</div>
        <div class="db-hero-stat-lbl">OVERDUE VOUCHERS</div>
      </div>
    </div>
  </div>
</div>

{{-- Stat Cards --}}
<div class="db-stats">
  <div class="stat-card">
    <div class="stat-icon-wrap red">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
    </div>
    <div><div class="stat-val">{{ $totalStudents }}</div><div class="stat-lbl">Active Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5.5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <div><div class="stat-val">Rs. {{ number_format($collectedThisMonth, 0) }}</div><div class="stat-lbl">Collected This Month</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap orange">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div><div class="stat-val">Rs. {{ number_format($totalOutstanding, 0) }}</div><div class="stat-lbl">Total Outstanding</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon-wrap blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </div>
    <div><div class="stat-val">{{ $overdueCount }}</div><div class="stat-lbl">Overdue Vouchers</div></div>
  </div>
</div>

{{-- Student Accounts Explorer: Program -> Session -> all students (Semester/Part is a filter) --}}
<div class="db-card ax-explorer-card" id="student-accounts-explorer">
  <div class="db-card-header ax-explorer-header">
    <div class="ax-explorer-heading">
      <div class="ax-explorer-heading-icon"><i class="fas fa-users-viewfinder"></i></div>
      <div>
        <div class="ax-explorer-title">Student Accounts Explorer</div>
        <div class="ax-explorer-help">Select a Program, then choose a Session to show all students in that Session. Use the Semester / Part filter above the list when you need a specific semester.</div>
      </div>
    </div>
    <a href="{{ route('accounts.students.index') }}" class="ax-explorer-all-link"><i class="fas fa-arrow-up-right-from-square"></i> Full Student Directory</a>
  </div>

  <div class="ax-program-list">
    @forelse($programExplorer as $program)
      @php $programPanelId = 'ax-program-panel-' . $program['id']; @endphp
      <div class="ax-program-item" data-program-item="{{ $program['id'] }}">
        <button type="button" class="ax-program-toggle" data-program-toggle="{{ $program['id'] }}" aria-expanded="false" aria-controls="{{ $programPanelId }}">
          <span class="ax-program-main">
            <span class="ax-program-icon"><i class="fas fa-graduation-cap"></i></span>
            <span style="min-width:0">
              <span class="ax-program-name">{{ $program['name'] }}</span>
              <span class="ax-program-meta">
                @if($program['code'])<span>{{ $program['code'] }}</span>@endif
                <span>{{ ucfirst($program['system_type'] ?: 'program') }}</span>
                @if(!$program['is_active'])<span style="color:var(--warning)">Inactive</span>@endif
              </span>
            </span>
          </span>
          <span class="ax-program-counts">
            <span class="ax-count-pill"><i class="fas fa-calendar-days"></i> {{ $program['session_count'] }} session{{ $program['session_count'] === 1 ? '' : 's' }}</span>
            <span class="ax-count-pill"><i class="fas fa-user-graduate"></i> {{ $program['student_count'] }} student{{ $program['student_count'] === 1 ? '' : 's' }}</span>
          </span>
          <span class="ax-program-chevron"><i class="fas fa-chevron-down"></i></span>
        </button>

        <div class="ax-program-body" id="{{ $programPanelId }}" hidden>
          @if($program['sessions']->isNotEmpty())
            <div class="ax-stage-heading">
              <span class="ax-stage-badge">1</span>
              <span>
                <strong>Sessions</strong>
                <small>Click a session to load all of its students</small>
              </span>
            </div>

            <div class="ax-session-stack">
              @foreach($program['sessions'] as $session)
                <div class="ax-session-item" data-session-item data-program-id="{{ $program['id'] }}" data-session-id="{{ $session['id'] }}">
                  <button type="button"
                          class="ax-session-toggle"
                          data-session-btn
                          data-program-id="{{ $program['id'] }}"
                          data-program-name="{{ $program['name'] }}"
                          data-session-id="{{ $session['id'] }}"
                          data-session-name="{{ $session['name'] }}">
                    <span class="ax-session-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span class="ax-session-copy">
                      <span class="ax-session-name">{{ $session['name'] }}</span>
                      <span class="ax-session-meta">
                        {{ $session['terms']->count() }} semester / part{{ $session['terms']->count() === 1 ? '' : 's' }}
                        @if($session['is_default'])<span class="ax-default-label"><i class="fas fa-circle-check"></i> Default</span>@endif
                      </span>
                    </span>
                    <span class="ax-session-students">{{ $session['student_count'] }} <span>students</span></span>
                    <span class="ax-session-chevron"><i class="fas fa-arrow-right"></i></span>
                  </button>
                  <script type="application/json" class="ax-session-terms">@json($session['terms'])</script>
                </div>
              @endforeach
            </div>
          @else
            <div class="ax-no-sessions"><i class="fas fa-circle-info"></i> No Sessions have been linked to this Program yet.</div>
          @endif
        </div>
      </div>
    @empty
      <div class="ax-no-sessions" style="padding:20px"><i class="fas fa-circle-info"></i> No Programs are available yet.</div>
    @endforelse
  </div>

  <div class="ax-detail-shell" id="axExplorerDetail" hidden>
    <div class="ax-detail-top">
      <div class="ax-selection-breadcrumb">
        <strong id="axSelectedProgram">Program</strong>
        <i class="fas fa-chevron-right"></i>
        <span id="axSelectedSession">Session</span>
      </div>
      <div class="ax-view-tabs" role="tablist" aria-label="Student information views">
        <button type="button" class="ax-view-tab is-active" data-view-mode="student" role="tab" aria-selected="true"><i class="fas fa-address-card"></i> Student Details</button>
        <button type="button" class="ax-view-tab" data-view-mode="enrollment" role="tab" aria-selected="false"><i class="fas fa-clipboard-list"></i> Enrollment Details</button>
        <button type="button" class="ax-view-tab" data-view-mode="fees" role="tab" aria-selected="false"><i class="fas fa-wallet"></i> Fees Details</button>
      </div>
    </div>

    <form class="ax-detail-tools" id="axExplorerSearchForm">
      <div class="ax-filter-control">
        <label for="axExplorerSemester"><i class="fas fa-layer-group"></i> Semester / Part</label>
        <select class="form-control" id="axExplorerSemester">
          <option value="">All Semesters / Parts</option>
        </select>
      </div>
      <div class="ax-search-wrap">
        <i class="fas fa-search"></i>
        <input type="search" class="form-control ax-search-input" id="axExplorerSearch" placeholder="Search name, student ID or phone..." autocomplete="off">
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
      <button type="button" class="btn btn-outline btn-sm ax-search-reset" id="axExplorerReset"><i class="fas fa-rotate-left"></i> Reset filters</button>
    </form>

    <div class="ax-selection-bar">
      <label class="ax-select-all-control">
        <input type="checkbox" id="axExplorerSelectAll">
        <span>Select all students shown</span>
      </label>
      <span class="ax-selected-count" id="axExplorerSelectedCount">0 selected</span>
      <button type="button" class="btn btn-outline btn-sm" id="axExplorerClearSelected" disabled><i class="fas fa-xmark"></i> Clear selection</button>
      <button type="button" class="btn btn-primary btn-sm" id="axExplorerPrintSelected" disabled><i class="fas fa-print"></i> Print selected list</button>
    </div>

    <div class="ax-table-zone" id="axExplorerTableZone">
      <div class="ax-loading"><span class="ax-loading-spinner"></span> Select a Session to load students.</div>
    </div>
  </div>

  <form id="axExplorerPrintForm" method="POST" action="{{ route('accounts.dashboard.student-explorer.print') }}" target="_blank" hidden>
    @csrf
    <div id="axExplorerPrintIds"></div>
  </form>
</div>

{{-- License Status --}}
@php
  $lsBadge = match($licenseStatus) {
      'active' => ['label'=>'Active','bg'=>'#0d9e6e','icon'=>'fa-check-circle'],
      'trial' => ['label'=>'Trial','bg'=>'#2563eb','icon'=>'fa-flask'],
      'expired' => ['label'=>'Expired','bg'=>'#dc2626','icon'=>'fa-times-circle'],
      'suspended' => ['label'=>'Suspended','bg'=>'#d97706','icon'=>'fa-pause-circle'],
      default => ['label'=>'Unknown','bg'=>'#64748b','icon'=>'fa-question-circle'],
  };
@endphp
<div class="db-card" style="margin-bottom:20px">
  <div class="db-card-header">
    <span class="db-card-title"><i class="fas fa-shield-alt" style="color:#8b0c22"></i>License Status</span>
    <span style="background:{{ $lsBadge['bg'] }}18;color:{{ $lsBadge['bg'] }};font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px">
      <i class="fas {{ $lsBadge['icon'] }}"></i> {{ $lsBadge['label'] }}{{ $licenseIsTrial ? ' (Trial)' : '' }}
    </span>
  </div>
  <div class="db-card-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
    @if($licensePlan)<div><strong>{{ $licensePlan }}</strong></div>@endif
    @if($licenseExpiresAt)<div style="font-size:12px;color:var(--text-muted)">{{ $licenseStatus === 'expired' ? 'Expired' : 'Expires' }}: {{ \Illuminate\Support\Carbon::parse($licenseExpiresAt)->format('M d, Y') }}</div>@endif
    @if($licenseDaysLeft !== null)<div style="font-size:12px;color:var(--text-muted)">{{ $licenseDaysLeft }} day(s) remaining</div>@endif
    @if(!$licensePlan && !$licenseExpiresAt && $licenseDaysLeft === null)
      <div style="font-size:12px;color:var(--text-muted)">License details will appear here once connected.</div>
    @endif
  </div>
</div>

{{-- Quick Actions --}}
<div class="db-card" style="margin-bottom:20px">
  <div class="db-card-header"><span class="db-card-title">Quick Actions</span></div>
  <div class="db-card-body">
    <div class="qa-grid">
      <a href="{{ route('payments.receive-form') }}" class="qa-btn">
        <div class="qa-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5.5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
        <div class="qa-label">Receive Payment</div>
      </a>
      <a href="{{ route('vouchers.create') }}" class="qa-btn">
        <div class="qa-icon qa-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
        <div class="qa-label">Generate Voucher</div>
      </a>
      <a href="{{ route('accounts.students.index', ['bulk_installments' => 1]) }}" class="qa-btn">
        <div class="qa-icon qa-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
        <div class="qa-label">Bulk Installment Vouchers</div>
      </a>
      <a href="{{ route('live-payments.index') }}" class="qa-btn">
        <div class="qa-icon qa-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="qa-label">Live Payments</div>
      </a>
      <a href="{{ route('daily-ledger.index') }}" class="qa-btn">
        <div class="qa-icon qa-teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="4" x2="8" y2="9"/></svg></div>
        <div class="qa-label">Daily Ledger</div>
      </a>
      <a href="{{ route('reports.index') }}" class="qa-btn">
        <div class="qa-icon qa-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
        <div class="qa-label">View Reports</div>
      </a>
    </div>
  </div>
</div>


<div class="db-bottom">
  <div class="db-card">
    <div class="db-card-header"><span class="db-card-title">Recent Payments</span><a href="{{ route('reports.collection') }}" style="font-size:12px;color:#8b0c22;text-decoration:none;font-weight:600">View all</a></div>
    <div class="db-card-body">
      <table class="db-table">
        <thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
        <tbody>
          @forelse($recentPayments as $p)
          @php $sname = $p->voucher->student->name ?? '—'; @endphp
          <tr>
            <td>
              <div class="pay-student">
                <div class="pay-avatar">{{ strtoupper(substr($sname,0,1)) }}</div>
                {{ $sname }}
              </div>
            </td>
            <td style="font-weight:700">Rs. {{ number_format($p->amount, 0) }}</td>
            <td>{{ ucfirst(str_replace('_',' ',$p->method)) }}</td>
            <td style="color:var(--text-muted)">{{ $p->paid_at->diffForHumans() }}</td>
          </tr>
          @empty
          <tr><td colspan="4">
            <div class="empty-illus">
              <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
              <p>No payments yet.</p>
            </div>
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="db-card">
    <div class="db-card-header"><span class="db-card-title">Recent Activity</span><a href="{{ route('audit-log.index') }}" style="font-size:12px;color:#8b0c22;text-decoration:none;font-weight:600">View all</a></div>
    <div class="db-card-body">
      <div class="timeline">
        @forelse($activities as $a)
        <div class="tl-item">
          <div class="tl-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg></div>
          <div>
            <div class="tl-desc">{{ $a->description }}</div>
            <div class="tl-meta">{{ $a->user->name ?? 'System' }} &middot; {{ $a->created_at->diffForHumans() }}</div>
          </div>
        </div>
        @empty
        <div class="empty-illus">
          <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
          <p>No recent activity.</p>
        </div>
        @endforelse
      </div>
    </div>
  </div>
</div>


@push('scripts')
<script>
(() => {
  const explorer = document.getElementById('student-accounts-explorer');
  if (!explorer) return;

  const endpoint = @json(route('accounts.dashboard.student-explorer'));
  const detail = document.getElementById('axExplorerDetail');
  const tableZone = document.getElementById('axExplorerTableZone');
  const searchForm = document.getElementById('axExplorerSearchForm');
  const searchInput = document.getElementById('axExplorerSearch');
  const resetBtn = document.getElementById('axExplorerReset');
  const semesterSelect = document.getElementById('axExplorerSemester');
  const selectedProgramLabel = document.getElementById('axSelectedProgram');
  const selectedSessionLabel = document.getElementById('axSelectedSession');
  const selectAllCheckbox = document.getElementById('axExplorerSelectAll');
  const selectedCount = document.getElementById('axExplorerSelectedCount');
  const clearSelectedBtn = document.getElementById('axExplorerClearSelected');
  const printSelectedBtn = document.getElementById('axExplorerPrintSelected');
  const printForm = document.getElementById('axExplorerPrintForm');
  const printIds = document.getElementById('axExplorerPrintIds');

  let selection = null;
  let viewMode = 'student';
  let requestToken = 0;
  const selectedStudentIds = new Set();

  function setLoading(message = 'Loading students...') {
    tableZone.innerHTML = `<div class="ax-loading"><span class="ax-loading-spinner"></span>${message}</div>`;
  }

  function updateSelectionUi() {
    const checkboxes = Array.from(tableZone.querySelectorAll('.ax-student-checkbox'));
    checkboxes.forEach(checkbox => {
      checkbox.checked = selectedStudentIds.has(String(checkbox.value));
    });

    const checkedVisible = checkboxes.filter(checkbox => checkbox.checked).length;
    selectAllCheckbox.checked = checkboxes.length > 0 && checkedVisible === checkboxes.length;
    selectAllCheckbox.indeterminate = checkedVisible > 0 && checkedVisible < checkboxes.length;
    selectAllCheckbox.disabled = checkboxes.length === 0;

    const count = selectedStudentIds.size;
    selectedCount.textContent = `${count} selected`;
    clearSelectedBtn.disabled = count === 0;
    printSelectedBtn.disabled = count === 0;
  }

  function clearSelectedStudents() {
    selectedStudentIds.clear();
    updateSelectionUi();
  }

  function hideDetail(clearSelection = true) {
    requestToken++;
    detail.hidden = true;
    searchInput.value = '';
    semesterSelect.innerHTML = '<option value="">All Semesters / Parts</option>';
    resetBtn.classList.remove('is-visible');
    clearSelectedStudents();
    if (clearSelection) selection = null;
  }

  function collapseSessions(programItem) {
    programItem.querySelectorAll('[data-session-item]').forEach(sessionItem => {
      sessionItem.classList.remove('is-selected');
    });
  }

  function openProgram(programId) {
    const targetItem = Array.from(explorer.querySelectorAll('[data-program-item]'))
      .find(item => String(item.dataset.programItem) === String(programId));
    if (!targetItem) return;

    const targetPanel = targetItem.querySelector('.ax-program-body');
    const willOpen = targetPanel.hidden;

    explorer.querySelectorAll('[data-program-item]').forEach(item => {
      const panel = item.querySelector('.ax-program-body');
      const toggle = item.querySelector('[data-program-toggle]');
      panel.hidden = true;
      item.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      collapseSessions(item);
    });

    hideDetail(true);

    if (willOpen) {
      targetPanel.hidden = false;
      targetItem.classList.add('is-open');
      targetItem.querySelector('[data-program-toggle]').setAttribute('aria-expanded', 'true');
    }
  }

  function populateSemesterFilter(sessionItem) {
    semesterSelect.innerHTML = '<option value="">All Semesters / Parts</option>';
    const dataNode = sessionItem.querySelector('.ax-session-terms');
    if (!dataNode) return;

    try {
      const terms = JSON.parse(dataNode.textContent || '[]');
      terms.forEach(term => {
        const option = document.createElement('option');
        option.value = term.id;
        option.textContent = `${term.name} (${term.student_count ?? 0})`;
        semesterSelect.appendChild(option);
      });
    } catch (error) {
      // Leave the "All" option available even if malformed metadata appears.
    }
  }

  function chooseSession(button) {
    const programItem = button.closest('[data-program-item]');
    const sessionItem = button.closest('[data-session-item]');
    if (!programItem || !sessionItem) return;

    programItem.querySelectorAll('[data-session-item]').forEach(item => item.classList.remove('is-selected'));
    sessionItem.classList.add('is-selected');

    selection = {
      programId: button.dataset.programId,
      sessionId: button.dataset.sessionId,
    };

    selectedProgramLabel.textContent = button.dataset.programName;
    selectedSessionLabel.textContent = button.dataset.sessionName;
    populateSemesterFilter(sessionItem);

    searchInput.value = '';
    resetBtn.classList.remove('is-visible');
    clearSelectedStudents();
    activateView('student');

    // Keep the student roster directly below the selected Program block.
    programItem.after(detail);
    detail.hidden = false;
    loadTable();
    detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function activateView(mode) {
    viewMode = mode;
    explorer.querySelectorAll('[data-view-mode]').forEach(btn => {
      const active = btn.dataset.viewMode === mode;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }

  function buildUrl() {
    const params = new URLSearchParams({
      program_id: selection.programId,
      academic_year_id: selection.sessionId,
      view: viewMode,
    });
    if (semesterSelect.value) params.set('school_class_id', semesterSelect.value);
    const search = searchInput.value.trim();
    if (search) params.set('search', search);
    return `${endpoint}?${params.toString()}`;
  }

  async function loadTable() {
    if (!selection) return;
    const token = ++requestToken;
    setLoading();
    resetBtn.classList.toggle('is-visible', searchInput.value.trim().length > 0 || semesterSelect.value !== '');
    selectAllCheckbox.disabled = true;

    try {
      const response = await fetch(buildUrl(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
        credentials: 'same-origin',
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const html = await response.text();
      if (token !== requestToken) return;
      tableZone.innerHTML = html;
      updateSelectionUi();
    } catch (error) {
      if (token !== requestToken) return;
      tableZone.innerHTML = `
        <div class="ax-loading" style="flex-direction:column;text-align:center">
          <i class="fas fa-triangle-exclamation" style="color:var(--danger);font-size:20px"></i>
          <strong style="color:var(--text)">Could not load students</strong>
          <span>Please try again. If the problem continues, open the full Student Directory.</span>
        </div>`;
      updateSelectionUi();
    }
  }

  explorer.addEventListener('click', (event) => {
    const programToggle = event.target.closest('[data-program-toggle]');
    if (programToggle) {
      openProgram(programToggle.dataset.programToggle);
      return;
    }

    const sessionBtn = event.target.closest('[data-session-btn]');
    if (sessionBtn) {
      chooseSession(sessionBtn);
      return;
    }

    const viewBtn = event.target.closest('[data-view-mode]');
    if (viewBtn && selection) {
      activateView(viewBtn.dataset.viewMode);
      loadTable();
    }
  });

  explorer.addEventListener('change', (event) => {
    const studentCheckbox = event.target.closest('.ax-student-checkbox');
    if (!studentCheckbox) return;

    const id = String(studentCheckbox.value);
    if (studentCheckbox.checked) selectedStudentIds.add(id);
    else selectedStudentIds.delete(id);
    updateSelectionUi();
  });

  semesterSelect.addEventListener('change', () => {
    resetBtn.classList.toggle('is-visible', searchInput.value.trim().length > 0 || semesterSelect.value !== '');
    if (selection) loadTable();
  });

  selectAllCheckbox.addEventListener('change', () => {
    const checkboxes = Array.from(tableZone.querySelectorAll('.ax-student-checkbox'));
    checkboxes.forEach(checkbox => {
      const id = String(checkbox.value);
      if (selectAllCheckbox.checked) selectedStudentIds.add(id);
      else selectedStudentIds.delete(id);
    });
    updateSelectionUi();
  });

  clearSelectedBtn.addEventListener('click', clearSelectedStudents);

  printSelectedBtn.addEventListener('click', () => {
    if (selectedStudentIds.size === 0) return;
    printIds.innerHTML = '';
    Array.from(selectedStudentIds).forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'student_ids[]';
      input.value = id;
      printIds.appendChild(input);
    });
    printForm.submit();
  });

  searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!selection) return;
    loadTable();
  });

  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    semesterSelect.value = '';
    resetBtn.classList.remove('is-visible');
    if (selection) loadTable();
  });

  updateSelectionUi();
})();
</script>
@endpush

@endsection
