<style>
  :root { --primary:#8b0c22; --ink:#1a1a1a; --muted:#555; --line:#333; --hair:#ccc; }
  * { box-sizing:border-box; }
  body {
    font-family:Georgia,'Times New Roman',Times,serif; background:#e9edf2; margin:0; padding:24px; color:var(--ink);
  }
  .toolbar { max-width:980px; margin:0 auto 16px; display:flex; justify-content:flex-end; gap:10px; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar button, .toolbar a {
    padding:9px 18px; border-radius:7px; border:none; background:var(--primary); color:#fff;
    font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
  }
  .toolbar a.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }

  .sheet { max-width:980px; margin:0 auto 28px; background:#fff; padding:6px; box-shadow:0 1px 4px rgba(0,0,0,.12); }
  .sheet-inner { border:2.5px solid var(--line); padding:22px 28px 26px; position:relative; }
  .sheet-inner::before { content:''; position:absolute; inset:5px; border:1px solid var(--hair); pointer-events:none; }

  /* ── Letterhead ── */
  .letterhead { display:flex; align-items:flex-start; gap:16px; }
  .letterhead .logo-wrap { width:70px; flex-shrink:0; display:flex; align-items:center; justify-content:center; padding-top:2px; }
  .letterhead .logo-wrap img { width:100%; height:auto; object-fit:contain; }
  .letterhead .logo-wrap .logo-fallback {
    width:66px; height:66px; border-radius:50%; border:2px solid var(--line);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:20px; color:var(--primary);
  }
  .letterhead .center { flex:1; text-align:center; }
  .letterhead .inst-name { font-size:22px; font-weight:700; letter-spacing:.6px; color:var(--primary); text-transform:uppercase; }
  .letterhead .inst-affil { font-size:11px; color:var(--muted); margin-top:2px; font-style:italic; }
  .letterhead .inst-address { font-size:12px; font-weight:600; margin-top:3px; }
  .letterhead .doc-name { font-size:14px; font-weight:700; letter-spacing:.4px; margin-top:9px; text-transform:uppercase; }
  .letterhead .doc-sub { font-size:11.5px; color:var(--muted); margin-top:2px; letter-spacing:.3px; }
  .letterhead .spacer { width:70px; flex-shrink:0; }

  hr.rule { border:none; border-top:1.5px solid var(--line); margin:14px 0 12px; }

  /* ── Meta / filter summary line ── */
  .meta-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:5px 18px; font-size:12.5px; margin-bottom:4px; }
  .meta-grid .row { display:flex; gap:6px; padding:2px 0; }
  .meta-grid .lbl { font-weight:700; white-space:nowrap; }
  .meta-grid .lbl::after { content:' :'; }
  .meta-grid .val { border-bottom:1px dotted var(--hair); flex:1; padding-bottom:1px; }

  /* ── Summary stat strip ── */
  .stat-strip { display:flex; gap:0; margin:14px 0; border:1px solid var(--line); }
  .stat-strip .stat { flex:1; text-align:center; padding:10px 6px; border-right:1px solid var(--hair); }
  .stat-strip .stat:last-child { border-right:none; }
  .stat-strip .stat .num { font-size:19px; font-weight:700; color:var(--primary); }
  .stat-strip .stat .lbl { font-size:9.5px; text-transform:uppercase; letter-spacing:.3px; color:var(--muted); margin-top:2px; }

  /* ── Data table ── */
  table.rpt { width:100%; border-collapse:collapse; margin:14px 0 10px; font-size:11.5px; }
  table.rpt th, table.rpt td { border:1px solid var(--line); padding:6px 7px; }
  table.rpt th { background:#f0f0f0; font-size:10px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:center; }
  table.rpt td.num { text-align:right; }
  table.rpt td.ctr { text-align:center; }
  table.rpt tfoot td { font-weight:700; background:#fafafa; }

  table.rpt tbody tr:nth-child(even) { background:#fbfbfb; }

  /* Report tables must render as document content, not browser scroll panes. */
  .rpt-table-shell { width:100%; max-width:100%; overflow:visible; }
  .rpt-table-shell::-webkit-scrollbar { display:none; width:0; height:0; }
  .rpt-table-shell { scrollbar-width:none; -ms-overflow-style:none; }

  .section-title { font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; margin:16px 0 6px; color:var(--primary); border-bottom:1px solid var(--hair); padding-bottom:3px; }

  .foot-row { display:flex; justify-content:space-between; align-items:flex-end; margin-top:34px; }
  .sign-block { text-align:center; min-width:200px; }
  .sign-line { border-top:1.3px solid var(--line); padding-top:4px; font-size:11.5px; font-weight:700; }
  .gen-date { font-size:11px; color:var(--muted); }

  .disclaimer { font-size:9.5px; color:var(--muted); text-align:center; margin-top:18px; border-top:1px solid var(--hair); padding-top:8px; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; margin:0; max-width:none; padding:0; }
    .rpt-table-shell { overflow:visible !important; width:100% !important; max-width:none !important; }
    .rpt-table-shell::-webkit-scrollbar { display:none !important; width:0 !important; height:0 !important; }
    .rpt-table-shell { scrollbar-width:none !important; -ms-overflow-style:none !important; }
    table.rpt { page-break-inside:auto; max-width:100%; }
    table.rpt tr { page-break-inside:avoid; }
    thead { display:table-header-group; }
  }
</style>
