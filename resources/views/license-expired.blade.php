<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>License Required — Acardix Accounts</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Outfit',sans-serif;background:#0d0d0d;color:#fff;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
.logo{font-size:1.6rem;font-weight:700;color:#890C25;letter-spacing:-0.5px;margin-bottom:8px;text-align:center}
.logo span{color:#fff}
.tagline{font-size:13px;color:#666;text-align:center;margin-bottom:40px}
.card{background:#161616;border:1px solid #2a2a2a;border-radius:16px;padding:36px;max-width:520px;width:100%;margin-bottom:32px}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;margin-bottom:20px}
.badge-expired{background:rgba(137,12,37,.15);color:#e05470;border:1px solid rgba(137,12,37,.3)}
.badge-suspended{background:rgba(186,117,23,.15);color:#f0a940;border:1px solid rgba(186,117,23,.3)}
.badge-trial{background:rgba(83,74,183,.15);color:#9b94f0;border:1px solid rgba(83,74,183,.3)}
.badge-inactive{background:rgba(100,100,100,.15);color:#aaa;border:1px solid rgba(100,100,100,.3)}
h1{font-size:1.5rem;font-weight:600;margin-bottom:10px;line-height:1.3}
.sub{font-size:14px;color:#888;line-height:1.6;margin-bottom:24px}
.info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #222;font-size:13px}
.info-row:last-child{border-bottom:none}
.info-label{color:#666}
.info-value{color:#ccc;font-weight:500}
.divider{height:1px;background:#222;margin:24px 0}
.form-group{margin-bottom:16px}
.form-label{font-size:13px;color:#888;margin-bottom:6px;display:block}
.form-input{width:100%;background:#0d0d0d;border:1px solid #333;border-radius:8px;padding:12px 14px;color:#fff;font-family:'Outfit',sans-serif;font-size:14px;transition:border-color .2s;letter-spacing:1px}
.form-input:focus{outline:none;border-color:#890C25}
.form-input::placeholder{color:#444;letter-spacing:0}
.btn{width:100%;padding:13px;border-radius:8px;font-family:'Outfit',sans-serif;font-size:15px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:#890C25;color:#fff}
.btn-primary:hover{background:#a01029}
.btn-primary:disabled{background:#5a0819;cursor:not-allowed;opacity:.6}
.msg{display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:12px}
.msg-error{background:rgba(137,12,37,.15);color:#e05470;border:1px solid rgba(137,12,37,.3)}
.msg-success{background:rgba(13,158,110,.15);color:#0D9E6E;border:1px solid rgba(13,158,110,.3)}
.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-top:0}
.plan-card{background:#161616;border:1px solid #2a2a2a;border-radius:12px;padding:16px;text-align:center;text-decoration:none;color:#fff;transition:border-color .2s;display:block}
.plan-card:hover{border-color:#890C25}
.plan-price{font-size:1.4rem;font-weight:700;color:#890C25}
.plan-name{font-size:13px;color:#888;margin-top:4px}
.plan-badge{font-size:10px;background:#890C25;color:#fff;padding:2px 6px;border-radius:10px;display:inline-block;margin-top:6px}
.section-label{font-size:11px;color:#555;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;font-weight:500}
.support-links{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px}
.support-link{font-size:13px;color:#555;text-decoration:none;transition:color .2s}
.support-link:hover{color:#890C25}
</style>
</head>
<body>

<div class="logo">Acardix<span> Accounts</span></div>
<div class="tagline">School Management System</div>

<div class="card">
    @if($license_status === 'suspended')
        <span class="status-badge badge-suspended">⚠ Account Suspended</span>
        <h1>Your account has been suspended</h1>
        <p class="sub">{{ $message ?? 'Access to this Acardix Accounts installation has been suspended by the administrator. Please contact support to restore access.' }}</p>
    @elseif($is_trial)
        <span class="status-badge badge-trial">⏱ Trial Ended</span>
        <h1>Your free trial has ended</h1>
        <p class="sub">Your 14-day trial of Acardix Accounts has expired. Purchase a plan below to restore access to all your school data.</p>
    @elseif($license_status === 'inactive')
        <span class="status-badge badge-inactive">🔑 License Required</span>
        <h1>Enter your license key</h1>
        <p class="sub">This Acardix Accounts installation is not yet activated. Enter the license key from your Acardix dashboard to get started.</p>
    @else
        <span class="status-badge badge-expired">✕ License Expired</span>
        <h1>Your license has expired</h1>
        <p class="sub">{{ $message ?? 'Your Acardix Accounts license expired and access has been restricted. Renew your license to restore access.' }}</p>
    @endif

    @if($expired_at || $days_overdue > 0)
    <div style="margin-bottom:20px">
        @if($expired_at)
        <div class="info-row"><span class="info-label">Expired on</span><span class="info-value">{{ $expired_at }}</span></div>
        @endif
        @if($days_overdue > 0)
        <div class="info-row"><span class="info-label">Days overdue</span><span class="info-value" style="color:#e05470">{{ $days_overdue }} days</span></div>
        @endif
        <div class="info-row"><span class="info-label">Plan</span><span class="info-value">{{ $plan_name }}</span></div>
    </div>
    @endif

    <div class="divider"></div>

    {{-- License key activation form --}}
    <div class="section-label">Have a license key?</div>
    <div class="form-group">
        <label class="form-label" for="license_key">Enter your license key</label>
        <input type="text" id="license_key" class="form-input" placeholder="ACX-XXXX-XXXX-XXXX-XXXX" maxlength="24" autocomplete="off">
    </div>
    <button class="btn btn-primary" id="activate-btn" onclick="activateLicense()">Activate License</button>
    <div class="msg msg-error" id="error-msg"></div>
    <div class="msg msg-success" id="success-msg"></div>
</div>

@if(!empty($plans))
<div style="max-width:520px;width:100%;margin-bottom:32px">
    <div class="section-label" style="text-align:center">Or purchase a plan</div>
    <div class="plans-grid">
        @foreach($plans as $plan)
        <a href="https://acadrix.cloud/pricing" class="plan-card" target="_blank">
            <div class="plan-price">
                @if(isset($plan['price']) && $plan['price'] > 0)
                    PKR {{ number_format($plan['price']) }}
                @else
                    Free
                @endif
            </div>
            <div class="plan-name">{{ $plan['name'] ?? 'Plan' }}</div>
            @if(isset($plan['is_featured']) && $plan['is_featured'])
                <div class="plan-badge">Popular</div>
            @endif
        </a>
        @endforeach
    </div>
</div>
@else
<div style="text-align:center;margin-bottom:32px">
    <a href="https://acadrix.cloud/pricing" style="display:inline-block;background:#890C25;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">View Plans &amp; Pricing →</a>
</div>
@endif

<div class="support-links">
    <a href="https://acadrix.cloud/support" class="support-link">Contact Support</a>
    <span style="color:#333">·</span>
    <a href="https://wa.me/923160380470" class="support-link">WhatsApp</a>
    <span style="color:#333">·</span>
    <a href="https://acadrix.cloud/pricing" class="support-link">Pricing</a>
</div>

<script>
async function activateLicense() {
    const key = document.getElementById('license_key').value.trim().toUpperCase();
    const btn = document.getElementById('activate-btn');
    const errEl = document.getElementById('error-msg');
    const okEl  = document.getElementById('success-msg');

    errEl.style.display = 'none';
    okEl.style.display  = 'none';

    if (!key) {
        errEl.textContent = 'Please enter a license key.';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Validating...';

    try {
        const res = await fetch('{{ route("oribyte.activate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ license_key: key }),
        });

        const data = await res.json();

        if (data.success) {
            okEl.textContent = data.message ?? 'License activated! Redirecting...';
            okEl.style.display = 'block';
            setTimeout(() => { window.location.href = data.redirect ?? '/'; }, 1500);
        } else {
            errEl.textContent = data.message ?? 'Activation failed. Please try again.';
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Activate License';
        }
    } catch (e) {
        // Show a more helpful error — the real issue is usually SSL on the server side
        errEl.innerHTML = 'Could not connect to Acardix server.<br><small style="opacity:.7">If you are on <strong>localhost</strong>, set <code>APP_ENV=local</code> in your .env — SSL verification is automatically disabled in local mode.</small>';
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Activate License';
    }
}

document.getElementById('license_key').addEventListener('keydown', e => {
    if (e.key === 'Enter') activateLicense();
});
</script>
</body>
</html>
