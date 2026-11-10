{{-- ─────────────────────────────────────────────────────────────────────
  "Add to Home Screen" prompt.

  Shown once, on mobile only, the first time someone opens the site (or
  again after they've ignored it for a while) — so parents/students on a
  phone get a nudge to install Acadrix like an app instead of retyping
  the URL every time. Handles both install paths:
    • Android/Chrome/Edge: native `beforeinstallprompt` → real install button.
    • iOS Safari: no such event exists, so we show manual
      Share ▸ Add to Home Screen instructions instead.
  Silently does nothing on desktop, or once the site is already
  installed/running standalone.
───────────────────────────────────────────────────────────────────── --}}
<div id="pwaInstallBanner" class="pwa-install-banner" role="dialog" aria-label="Install Acadrix app">
  <button type="button" class="pwa-install-close" id="pwaInstallClose" aria-label="Dismiss">
    <i class="fas fa-times"></i>
  </button>
  <div class="pwa-install-icon">
    <img src="{{ asset('icons/icon-192.png') }}" alt="Acadrix">
  </div>
  <div class="pwa-install-body">
    <div class="pwa-install-title">Install Acadrix</div>
    <div class="pwa-install-text" id="pwaInstallText">Add it to your home screen for quick, app-like access.</div>
  </div>
  <button type="button" class="pwa-install-cta" id="pwaInstallCta">Install</button>
</div>

<style>
.pwa-install-banner{
  position:fixed; left:12px; right:12px; bottom:12px;
  bottom:calc(12px + env(safe-area-inset-bottom));
  z-index:2000;
  display:none;
  align-items:center; gap:12px;
  background:var(--surface,#fff);
  color:var(--text,#1e293b);
  border:1px solid var(--border,#e2e8f0);
  border-radius:16px;
  box-shadow:0 12px 40px rgba(0,0,0,.22);
  padding:14px 44px 14px 14px;
  font-family:inherit;
  transform:translateY(120%);
  transition:transform .35s ease;
}
.pwa-install-banner.show{ display:flex; }
.pwa-install-banner.open{ transform:translateY(0); }
@media (min-width:769px){ .pwa-install-banner{ display:none !important; } }

.pwa-install-icon img{
  width:44px; height:44px; border-radius:12px; flex-shrink:0; display:block;
  box-shadow:0 2px 8px rgba(0,0,0,.18);
}
.pwa-install-body{ flex:1; min-width:0; }
.pwa-install-title{ font-weight:700; font-size:14px; line-height:1.3; }
.pwa-install-text{ font-size:12px; color:var(--text-muted,#64748b); margin-top:2px; line-height:1.4; }
.pwa-install-text b{ color:var(--text,#1e293b); }
.pwa-install-cta{
  flex-shrink:0; border:none; cursor:pointer;
  background:var(--primary,#890c25); color:#fff;
  font-size:13px; font-weight:600;
  padding:9px 16px; border-radius:10px;
  white-space:nowrap;
}
.pwa-install-cta:active{ opacity:.85; }
.pwa-install-close{
  position:absolute; top:8px; right:8px;
  width:26px; height:26px; border-radius:50%;
  border:none; background:var(--bg,#f0f2f5); color:var(--text-muted,#64748b);
  display:flex; align-items:center; justify-content:center;
  font-size:11px; cursor:pointer;
}
</style>

<script>
(function(){
  var STORAGE_KEY = 'acadrixPwaPrompt';
  var SNOOZE_DAYS = 14;

  function getState(){
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch(e){ return {}; }
  }
  function setState(patch){
    try {
      var s = getState();
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.assign(s, patch)));
    } catch(e){}
  }

  var isStandalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;

  var ua = window.navigator.userAgent || '';
  var isIos = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && 'ontouchend' in document);
  var isAndroid = /android/i.test(ua);
  var isMobile = isAndroid || isIos || (window.innerWidth <= 768 && 'ontouchstart' in window);

  if (isStandalone || !isMobile) return;

  var state = getState();
  if (state.installed) return;
  if (state.dismissedAt){
    var daysSince = (Date.now() - state.dismissedAt) / 86400000;
    if (daysSince < SNOOZE_DAYS) return;
  }

  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function(e){
    e.preventDefault();
    deferredPrompt = e;
    showBanner();
  });
  window.addEventListener('appinstalled', function(){
    setState({ installed: true });
    hideBanner();
  });

  function showBanner(){
    var el = document.getElementById('pwaInstallBanner');
    if (!el) return;
    var textEl = document.getElementById('pwaInstallText');
    var ctaEl = document.getElementById('pwaInstallCta');

    if (isIos){
      textEl.innerHTML = 'Tap <i class="fas fa-arrow-up-from-bracket"></i> Share, then <b>Add to Home Screen</b>.';
      ctaEl.style.display = 'none';
    } else if (!deferredPrompt){
      // Android without a captured beforeinstallprompt yet (e.g. already
      // dismissed by the browser this session) — nothing useful to do.
      return;
    }

    el.classList.add('show');
    requestAnimationFrame(function(){ el.classList.add('open'); });
  }

  function hideBanner(){
    var el = document.getElementById('pwaInstallBanner');
    if (!el) return;
    el.classList.remove('open');
    setTimeout(function(){ el.classList.remove('show'); }, 350);
  }

  document.addEventListener('DOMContentLoaded', function(){
    var closeBtn = document.getElementById('pwaInstallClose');
    var ctaBtn = document.getElementById('pwaInstallCta');

    if (closeBtn) closeBtn.addEventListener('click', function(){
      setState({ dismissedAt: Date.now() });
      hideBanner();
    });

    if (ctaBtn) ctaBtn.addEventListener('click', function(){
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.finally(function(){
        deferredPrompt = null;
        hideBanner();
      });
    });

    // iOS: show on a short delay since there's no install-readiness event.
    if (isIos){
      setTimeout(showBanner, 1500);
    }
  });
})();
</script>
