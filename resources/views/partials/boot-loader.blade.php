<style>
  #boot-loader {
    position: fixed; inset: 0; z-index: 9999;
    background: radial-gradient(circle at 30% 20%, #a01030 0%, #6b0a1e 45%, #2a040d 100%);
    display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 4px;
    transition: opacity .5s ease, visibility .5s ease;
    overflow: hidden;
  }
  #boot-loader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
  #boot-loader::before {
    content:'';
    position:absolute; inset:0;
    background-image: radial-gradient(rgba(255,255,255,.10) 1px, transparent 1px);
    background-size: 26px 26px;
    opacity: .35;
    animation: boot-drift 12s linear infinite;
  }
  @keyframes boot-drift { from{ background-position:0 0; } to{ background-position: 260px 260px; } }

  .boot-scene {
    position: relative; width: 140px; height: 140px;
    display: flex; align-items: center; justify-content: center;
    perspective: 700px;
    margin-bottom: 8px;
  }
  .boot-ring {
    position: absolute; border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,.16);
  }
  .boot-ring.r1 { width: 108px; height: 108px; border-top-color: rgba(255,215,106,.65); animation: boot-ring-spin 2.6s linear infinite; }
  .boot-ring.r2 { width: 136px; height: 136px; border-style: dashed; border-color: rgba(255,255,255,.12); animation: boot-ring-spin 5.5s linear infinite reverse; }
  @keyframes boot-ring-spin { to { transform: rotate(360deg); } }

  .boot-logo {
    width: 96px; max-width: 78%; position: relative;
    filter: drop-shadow(0 8px 22px rgba(0,0,0,.4));
    animation: boot-logo-pulse 1.5s ease-in-out infinite;
  }
  @keyframes boot-logo-pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.06); } }

  .boot-shadow {
    position: absolute; bottom: 4px; width: 46px; height: 9px; border-radius: 50%;
    background: radial-gradient(ellipse, rgba(0,0,0,.45) 0%, transparent 72%);
    animation: boot-shadow-pulse 1.5s ease-in-out infinite;
  }
  @keyframes boot-shadow-pulse { 0%,100% { transform: scale(1); opacity: .55; } 50% { transform: scale(.72); opacity: .25; } }

  .boot-title {
    font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #fff;
    letter-spacing: .02em; margin-top: 6px;
  }
  .boot-sub {
    font-size: 12px; color: rgba(255,255,255,.65); letter-spacing: .03em; margin-top: 4px;
    min-height: 16px; transition: opacity .25s ease;
  }
  .boot-progress {
    width: 190px; height: 4px; border-radius: 4px; overflow: hidden;
    background: rgba(255,255,255,.15); margin-top: 16px;
  }
  .boot-progress-bar {
    height: 100%; width: 0%; border-radius: 4px;
    background: linear-gradient(90deg, #ffd76a, #fff);
    /* Was a fixed 5s animation regardless of actual load time — the loader always
       sat on screen for a full 5s even after the page was ready. Now it fills
       fast and the JS below hides it as soon as the page is actually ready
       (capped at 3s worst case), so perceived load time matches real load time. */
    animation: boot-fill 1.1s cubic-bezier(.2,.6,.3,1) forwards;
  }
  @keyframes boot-fill { from { width: 0%; } to { width: 100%; } }
</style>

<div id="boot-loader">
  <div class="boot-scene">
    <div class="boot-ring r2"></div>
    <div class="boot-ring r1"></div>
    <img class="boot-logo" src="{{ asset('logo.png') }}" alt="{{ $label ?? 'Acadrix' }}">
    <div class="boot-shadow"></div>
  </div>
  <div class="boot-sub" id="boot-sub-text">Preparing your workspace…</div>
  <div class="boot-progress"><div class="boot-progress-bar"></div></div>
</div>

<script>
(function () {
  var el = document.getElementById('boot-loader');
  var subEl = document.getElementById('boot-sub-text');
  var msgs = ['Preparing your workspace…', 'Fetching your data…', 'Almost there…'];
  var i = 0;
  var msgTimer = setInterval(function () {
    i = (i + 1) % msgs.length;
    if (subEl) {
      subEl.style.opacity = 0;
      setTimeout(function () { subEl.textContent = msgs[i]; subEl.style.opacity = 1; }, 200);
    }
  }, 900);

  var hidden = false;
  function hideLoader() {
    if (hidden) return;
    hidden = true;
    if (el) el.classList.add('hidden');
    clearInterval(msgTimer);
  }

  // Hide as soon as the page (including images/CSS) is actually ready, instead
  // of waiting a fixed amount of time regardless of real load speed.
  if (document.readyState === 'complete') {
    // Page already loaded by the time this script ran — hide almost immediately.
    setTimeout(hideLoader, 150);
  } else {
    window.addEventListener('load', function () { setTimeout(hideLoader, 150); });
  }

  // Hard safety cap: never show the loader for more than 3 seconds, even if
  // some slow third-party asset (e.g. a font/CDN request) is still pending.
  setTimeout(hideLoader, 3000);
})();
</script>
