@php
  // Use exactly the same institution settings and public-media helper as the
  // existing login-page identity header. No institution branding is hardcoded.
  $portalWelcomeInstitutionLogo = \App\Models\Setting::get('institution_logo');
  $portalWelcomeInstitutionName = \App\Models\Setting::get('institution_name');
@endphp
<style>
  #boot-loader.portal-post-login-welcome {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    isolation: isolate;
    padding: 22px;
    box-sizing: border-box;
    background:
      radial-gradient(ellipse 56% 60% at 0% 0%, rgba(155, 8, 51, .60), transparent 74%),
      radial-gradient(ellipse 63% 64% at 100% 0%, rgba(5, 36, 112, .61), transparent 74%),
      radial-gradient(ellipse 46% 46% at 51% 111%, rgba(131, 66, 25, .30), transparent 78%),
      #0c0912;
    color: #fff;
    font-family: 'Outfit', 'Segoe UI', Arial, sans-serif;
    opacity: 1;
    visibility: visible;
    transition: opacity .5s ease, visibility .5s ease;
  }
  #boot-loader.portal-post-login-welcome.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
  }
  .portal-welcome-center {
    position: relative;
    z-index: 1;
    width: min(100%, 720px);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transform: translateY(-8px);
  }
  .portal-welcome-logo-stage {
    position: relative;
    width: min(100%, 630px);
    min-height: 245px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    margin: 0 auto 1px;
  }
  .portal-welcome-logo-stage::before {
    content: '';
    position: absolute;
    inset: 0;
    border: 1px solid rgba(255, 255, 255, .045);
    border-radius: 50%;
    pointer-events: none;
  }
  .portal-welcome-brands {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 43px;
    position: relative;
    z-index: 1;
  }
  .portal-welcome-institution {
    width: 198px;
    height: 205px;
    flex: 0 0 198px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .portal-welcome-institution img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 7px 18px rgba(0, 0, 0, .22));
  }
  .portal-welcome-initial {
    width: 152px;
    height: 152px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, .085);
    border: 2px solid rgba(236, 198, 111, .55);
    color: #f5d884;
    font-size: 80px;
    font-weight: 700;
    font-family: Georgia, serif;
  }
  .portal-welcome-separator {
    display: block;
    flex: 0 0 3px;
    width: 3px;
    height: 148px;
    border-radius: 3px;
    background: linear-gradient(180deg, #ffe26f, #efbd3b);
  }
  .portal-welcome-app-logo {
    width: 220px;
    max-width: 36%;
    height: auto;
    object-fit: contain;
    display: block;
    filter: drop-shadow(0 9px 20px rgba(0, 0, 0, .34));
  }
  .portal-welcome-heading {
    margin: -1px 0 0;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: clamp(32px, 4.3vw, 42px);
    font-weight: 400;
    letter-spacing: .02em;
    line-height: 1.22;
    text-shadow: 0 2px 10px rgba(0, 0, 0, .18);
  }
  .portal-welcome-description {
    margin: 14px 0 0;
    color: rgba(255, 255, 255, .75);
    font-size: clamp(12px, 1.6vw, 19px);
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: .25em;
    line-height: 1.6;
  }
  .portal-welcome-progress-track {
    margin-top: 34px;
    width: min(100%, 432px);
    height: 6px;
    overflow: hidden;
    border-radius: 40px;
    background: rgba(255, 255, 255, .17);
  }
  .portal-welcome-progress-fill {
    display: block;
    height: 100%;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #ffe26e, #f5ce68);
    animation: portal-welcome-fill 3s linear forwards paused;
  }
  #boot-loader.portal-post-login-welcome.started .portal-welcome-progress-fill {
    animation-play-state: running;
  }
  @keyframes portal-welcome-fill { from { width: 0; } to { width: 100%; } }
  @media (max-width: 620px) {
    #boot-loader.portal-post-login-welcome { padding: 20px 16px; }
    .portal-welcome-logo-stage { min-height: 168px; }
    .portal-welcome-brands { gap: clamp(12px, 4vw, 24px); }
    .portal-welcome-institution {
      flex: 0 0 clamp(94px, 32vw, 135px);
      width: clamp(94px, 32vw, 135px);
      height: 148px;
    }
    .portal-welcome-initial { width: 86px; height: 86px; font-size: 45px; }
    .portal-welcome-separator { height: 88px; flex-basis: 2px; width: 2px; }
    .portal-welcome-app-logo { width: clamp(124px, 36vw, 175px); max-width: 43%; }
    .portal-welcome-description { letter-spacing: .14em; }
    .portal-welcome-progress-track { width: min(100%, 310px); height: 5px; }
  }
  @media (prefers-reduced-motion: reduce) {
    #boot-loader.portal-post-login-welcome { transition-duration: 0s; }
    .portal-welcome-progress-fill { animation: none; width: 100%; }
  }
</style>

<div id="boot-loader" class="portal-post-login-welcome" role="status" aria-label="Opening your Parent and Student Portal">
  <div class="portal-welcome-center">
    <div class="portal-welcome-logo-stage">
      <div class="portal-welcome-brands">
        <div class="portal-welcome-institution">
          @if($portalWelcomeInstitutionLogo)
            <img src="{{ storage_asset($portalWelcomeInstitutionLogo) }}"
                 alt="{{ $portalWelcomeInstitutionName ?: 'Institution' }} logo"
                 fetchpriority="high">
          @else
            <span class="portal-welcome-initial" aria-label="{{ $portalWelcomeInstitutionName ?: 'Institution' }}">
              {{ strtoupper(substr($portalWelcomeInstitutionName ?: 'I', 0, 1)) }}
            </span>
          @endif
        </div>
        <span class="portal-welcome-separator" aria-hidden="true"></span>
        <img class="portal-welcome-app-logo" src="{{ asset('logo.png') }}" alt="Acadrix" fetchpriority="high">
      </div>
    </div>
    <div class="portal-welcome-heading">Acadrix</div>
    <p class="portal-welcome-description">Parent &amp; Student Portal</p>
    <div class="portal-welcome-progress-track" aria-hidden="true">
      <span class="portal-welcome-progress-fill"></span>
    </div>
  </div>
</div>

<script>
(function () {
  var overlay = document.getElementById('boot-loader');
  if (!overlay) return;

  var finished = false;
  function finishWelcome() {
    if (finished) return;
    finished = true;
    overlay.classList.add('hidden');
    window.setTimeout(function () { overlay.remove(); }, 550);
  }

  // Two paint frames ensure the 3-second pause starts AFTER the welcome has
  // appeared, not while HTML is parsing. Dashboard remains available behind it.
  function beginWelcome() {
    if (window.requestAnimationFrame) {
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          overlay.classList.add('started');
          window.setTimeout(finishWelcome, 3000);
        });
      });
    } else {
      overlay.classList.add('started');
      window.setTimeout(finishWelcome, 3000);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', beginWelcome, { once: true });
  } else {
    beginWelcome();
  }

  // A browser back/forward-cache restore should not replay the login welcome.
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) finishWelcome();
  });
})();
</script>
