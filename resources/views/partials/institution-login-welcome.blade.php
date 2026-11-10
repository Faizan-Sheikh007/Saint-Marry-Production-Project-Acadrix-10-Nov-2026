{{-- Second screen only, shown after the existing 5-second Acadrix splash and
     before the login form. Timing is controlled by auth/login.blade.php.
     The institution logo, name and tagline are inherited from the existing login settings. --}}
<style>
  #institution-login-welcome {
    position: fixed;
    inset: 0;
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    isolation: isolate;
    padding: 20px;
    box-sizing: border-box;
    background:
      radial-gradient(ellipse 56% 60% at 0% 0%, rgba(155, 8, 51, .60), transparent 74%),
      radial-gradient(ellipse 63% 64% at 100% 0%, rgba(5, 36, 112, .61), transparent 74%),
      radial-gradient(ellipse 46% 46% at 51% 111%, rgba(131, 66, 25, .30), transparent 78%),
      #0c0912;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .5s ease, visibility .5s ease;
  }
  #institution-login-welcome.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }

  /* Compact, ring-free brand lockup with the original divider. */
  .portal-welcome-center {
    position: relative;
    width: min(100%, 560px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transform: translateY(-6px);
  }
  .portal-welcome-logo-stage {
    position: relative;
    width: min(100%, 360px);
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
  }
  .portal-welcome-brands {
    position: relative;
    z-index: 1;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
  }
  .portal-welcome-institution {
    width: 90px;
    height: 90px;
    flex: 0 0 90px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .portal-welcome-institution img {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 6px 14px rgba(0, 0, 0, .25));
  }
  .portal-welcome-initial {
    width: 74px;
    height: 74px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, .08);
    border: 1.5px solid rgba(236, 198, 111, .52);
    color: #f5d884;
    font: 700 40px Georgia, serif;
  }
  .portal-welcome-separator {
    display: block;
    flex: 0 0 2px;
    width: 2px;
    height: 74px;
    border-radius: 3px;
    background: linear-gradient(180deg, #ffe26f, #efbd3b);
  }
  .portal-welcome-app-logo {
    display: block;
    width: 112px;
    max-width: 42%;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, .3));
  }

  /* Display the configured institution identity directly under the logos. */
  .portal-welcome-institution-details {
    width: 100%;
    max-width: 540px;
    margin: 10px auto 0;
    text-align: center;
    color: #fff;
    overflow-wrap: anywhere;
  }
  .portal-welcome-institution-name {
    margin: 0;
    font-family: 'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-size: clamp(20px, 2.3vw, 27px);
    font-weight: 750;
    line-height: 1.28;
    letter-spacing: .008em;
    text-wrap: balance;
    text-shadow: 0 3px 12px rgba(0,0,0,.25);
  }
  .portal-welcome-institution-tagline {
    max-width: 460px;
    margin: 13px auto 0;
    font-family: 'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.6;
    letter-spacing: .07em;
    color: rgba(238, 203, 132, .86);
    text-wrap: balance;
  }
  @media (max-width: 620px) {
    #institution-login-welcome { padding: 16px; }
    .portal-welcome-center { width: min(100%, 340px); }
    .portal-welcome-logo-stage { height: 124px; }
    .portal-welcome-brands { gap: 14px; }
    .portal-welcome-institution { width: 68px; height: 68px; flex-basis: 68px; }
    .portal-welcome-initial { width: 56px; height: 56px; font-size: 32px; }
    .portal-welcome-separator { height: 58px; }
    .portal-welcome-app-logo { width: 86px; max-width: 42%; }
    .portal-welcome-institution-details { margin-top: 9px; }
    .portal-welcome-institution-name { font-size: 19px; line-height: 1.32; }
    .portal-welcome-institution-tagline { margin-top: 11px; font-size: 12px; letter-spacing: .04em; }
  }
  @media (prefers-reduced-motion: reduce) {
    #institution-login-welcome { transition-duration: 0s; }
  }
</style>
<div id="institution-login-welcome" class="portal-login-welcome" aria-hidden="true" role="status" aria-label="Opening your sign-in page">
  <div class="portal-welcome-center">
    <div class="portal-welcome-logo-stage">
      <div class="portal-welcome-brands">
        <div class="portal-welcome-institution">
          @if($loginInstitutionLogo)
            <img src="{{ storage_asset($loginInstitutionLogo) }}"
                 alt="{{ $loginInstitutionName ?: 'Institution' }} logo"
                 fetchpriority="high">
          @else
            <span class="portal-welcome-initial" aria-label="{{ $loginInstitutionName ?: 'Institution' }}">
              {{ strtoupper(substr($loginInstitutionName ?: 'I', 0, 1)) }}
            </span>
          @endif
        </div>
        <span class="portal-welcome-separator" aria-hidden="true"></span>
        <img class="portal-welcome-app-logo" src="{{ asset('logo.png') }}" alt="Acadrix" fetchpriority="high">
      </div>
    </div>
    @if($loginInstitutionName || $loginInstitutionTagline)
      <div class="portal-welcome-institution-details" aria-label="Institution information">
        @if($loginInstitutionName)
          <div class="portal-welcome-institution-name">{{ $loginInstitutionName }}</div>
        @endif
        @if($loginInstitutionTagline)
          <div class="portal-welcome-institution-tagline">{{ $loginInstitutionTagline }}</div>
        @endif
      </div>
    @endif
  </div>
</div>
