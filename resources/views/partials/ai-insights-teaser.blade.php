{{-- Compact "Acadrix AI" teaser card — click to open the full Insights panel. --}}
<div class="ai-teaser-card" onclick="toggleAiInsights(true)">
  <div class="ai-teaser-left">
    <div class="ai-teaser-icon"><i class="fas fa-brain"></i></div>
    <div>
      <div class="ai-teaser-title">Acadrix AI</div>
      <div class="ai-teaser-sub">Intelligence Engine · Active</div>
    </div>
  </div>
  <div class="ai-teaser-stats" id="aiTeaserStats">
    <div><div class="ai-teaser-val">—</div><div class="ai-teaser-lbl">{{ ($teaserScopeLabel ?? 'Students') }}</div></div>
    <div><div class="ai-teaser-val">—</div><div class="ai-teaser-lbl">Attend.</div></div>
  </div>
  <div class="ai-teaser-cta">View AI Insights <i class="fas fa-arrow-right"></i></div>
</div>

<style>
  .ai-teaser-card{background:linear-gradient(150deg,#7a0a1f 0%,#4a0611 100%);border-radius:16px;padding:18px 20px;color:#fff;
    cursor:pointer;box-shadow:0 10px 26px rgba(90,10,25,.28);transition:transform .15s;margin-bottom:20px}
  .ai-teaser-card:hover{transform:translateY(-2px)}
  .ai-teaser-left{display:flex;align-items:center;gap:12px;margin-bottom:14px}
  .ai-teaser-icon{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
  .ai-teaser-title{font-weight:800;font-size:14.5px}
  .ai-teaser-sub{font-size:11px;opacity:.75}
  .ai-teaser-stats{display:flex;gap:24px;margin-bottom:12px}
  .ai-teaser-val{font-size:20px;font-weight:800}
  .ai-teaser-lbl{font-size:10px;opacity:.7;letter-spacing:.4px;text-transform:uppercase}
  .ai-teaser-cta{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:6px;opacity:.9}
</style>

<script>
(function(){
  fetch("{{ route('ai-insights.data') }}", { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
      const box = document.getElementById('aiTeaserStats');
      if (!box || !d.stats || !d.stats.length) return;
      const s = d.stats;
      box.innerHTML = `
        <div><div class="ai-teaser-val">${s[0]?.value ?? '—'}</div><div class="ai-teaser-lbl">${s[0]?.label ?? ''}</div></div>
        <div><div class="ai-teaser-val">${s[1]?.value ?? '—'}</div><div class="ai-teaser-lbl">${s[1]?.label ?? ''}</div></div>`;
    })
    .catch(() => {});
})();
</script>
