@if(session('credentials'))
@php($c = session('credentials'))
<div id="axCredOverlay" class="ax-cred-overlay show">
  <div class="ax-cred-card">
    <div class="ax-cred-icon"><i class="fas fa-key"></i></div>
    <div class="ax-cred-title">{{ $c['heading'] ?? 'Login credentials' }}</div>
    <div class="ax-cred-sub">Share these with {{ $c['name'] ?? 'the user' }} — the password won't be shown in full again.</div>

    <div class="ax-cred-field">
      <div class="ax-cred-field-label">EMPLOYEE ID</div>
      <div class="ax-cred-field-row">
        <span id="axCredEmpId">{{ $c['employee_id'] ?? '—' }}</span>
        <button type="button" class="ax-cred-copy" onclick="axCopyCred('axCredEmpId', this)"><i class="fas fa-copy"></i></button>
      </div>
    </div>

    <div class="ax-cred-field">
      <div class="ax-cred-field-label">EMAIL</div>
      <div class="ax-cred-field-row">
        <span id="axCredEmail">{{ $c['email'] ?? '—' }}</span>
        <button type="button" class="ax-cred-copy" onclick="axCopyCred('axCredEmail', this)"><i class="fas fa-copy"></i></button>
      </div>
    </div>

    <div class="ax-cred-field">
      <div class="ax-cred-field-label">PASSWORD</div>
      <div class="ax-cred-field-row">
        <span id="axCredPassword">{{ $c['password'] ?? '—' }}</span>
        <button type="button" class="ax-cred-copy" onclick="axCopyCred('axCredPassword', this)"><i class="fas fa-copy"></i></button>
      </div>
    </div>

    <div class="ax-cred-actions">
      @if(!empty($c['card_url']))
        <a href="{{ $c['card_url'] }}" target="_blank" class="ax-cred-btn ax-cred-secondary"><i class="fas fa-id-card"></i> ID Card</a>
      @endif
      <button type="button" class="ax-cred-btn ax-cred-primary" onclick="document.getElementById('axCredOverlay').remove()">Done</button>
    </div>
  </div>
</div>

<style>
  .ax-cred-overlay{position:fixed;inset:0;z-index:3100;display:none;align-items:center;justify-content:center;padding:20px;
    background:rgba(20,6,10,.55);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
  .ax-cred-overlay.show{display:flex}
  .ax-cred-card{width:100%;max-width:400px;background:#fff;border-radius:20px;padding:30px 26px;
    box-shadow:0 40px 90px -20px rgba(20,6,10,.5);border:1px solid rgba(139,12,34,.08);text-align:center}
  .ax-cred-icon{width:58px;height:58px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;
    font-size:22px;color:#fff;background:linear-gradient(135deg,#a81030 0%,#890c25 100%);box-shadow:0 12px 26px rgba(139,12,34,.35)}
  .ax-cred-title{font-size:18px;font-weight:800;color:#1a0608;margin-bottom:6px}
  .ax-cred-sub{font-size:12.5px;color:#7a5057;line-height:1.5;margin-bottom:20px}
  .ax-cred-field{text-align:left;margin-bottom:12px}
  .ax-cred-field-label{font-size:10px;font-weight:700;color:#a0808a;letter-spacing:.5px;margin-bottom:4px}
  .ax-cred-field-row{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f7f0f1;
    border:1px solid #ecdfe1;border-radius:10px;padding:10px 12px;font-family:monospace;font-size:14px;font-weight:700;color:#1a0608}
  .ax-cred-copy{background:none;border:none;color:#890c25;cursor:pointer;font-size:13px;flex-shrink:0}
  .ax-cred-actions{display:flex;gap:10px;margin-top:18px}
  .ax-cred-btn{flex:1;padding:12px 14px;border-radius:11px;font-size:13.5px;font-weight:700;cursor:pointer;border:none;text-decoration:none;
    display:flex;align-items:center;justify-content:center;gap:6px}
  .ax-cred-secondary{background:#f3eced;color:#7a5057;border:1.5px solid #ecdfe1}
  .ax-cred-primary{color:#fff;background:linear-gradient(135deg,#a81030 0%,#890c25 100%)}
</style>
<script>
function axCopyCred(id, btn){
  const text = document.getElementById(id).textContent;
  navigator.clipboard?.writeText(text).then(() => {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => btn.innerHTML = original, 1200);
  });
}
</script>
@endif
