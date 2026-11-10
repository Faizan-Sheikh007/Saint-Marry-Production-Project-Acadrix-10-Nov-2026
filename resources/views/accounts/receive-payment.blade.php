@extends('layouts.app')

@section('title', 'Receive Payment')
@section('page_title', 'Receive Payment')

@section('content')

@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<style>
  @keyframes fadeSlideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
  .mode-tab { position:relative; overflow:hidden; transition:transform .15s ease, box-shadow .15s ease; }
  .mode-tab:hover { transform:translateY(-1px); }
  .mode-tab.btn-primary { box-shadow:0 8px 18px rgba(137,12,37,.28); }
  #voucherSearchBtn:hover { box-shadow:0 6px 16px rgba(137,12,37,.3); }
  #voucherSearchBtn:active { transform:translateY(1px); }
  #voucherSearch.is-invalid { border-color:#ef4444 !important; box-shadow:0 0 0 3px rgba(239,68,68,.12); }

  .proof-upload { border:1.5px dashed var(--border); border-radius:10px; background:#f8fafc; transition:border-color .15s ease, background .15s ease; }
  .proof-upload:hover { border-color:var(--primary); background:#fdf6f7; }
  .proof-upload-inner { display:flex; align-items:center; gap:10px; padding:14px 16px; cursor:pointer; font-size:12.5px; color:var(--text-muted); }
  .proof-upload-inner i { font-size:18px; color:var(--primary); flex-shrink:0; }
  .proof-upload-inner small { display:block; color:#94a3b8; font-size:11px; margin-top:2px; }
  .proof-preview { display:none; align-items:center; gap:12px; padding:10px 14px; }
  .proof-preview img { width:44px; height:44px; border-radius:8px; object-fit:cover; border:1px solid var(--border); flex-shrink:0; }
  .proof-preview .proof-file-icon { width:44px; height:44px; border-radius:8px; background:#fee2e2; color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
  .proof-preview .proof-name { font-size:12.5px; font-weight:600; color:#1f2937; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:280px; }
  .proof-preview .proof-remove { margin-left:auto; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:4px; }
  .proof-preview .proof-remove:hover { color:var(--danger); }
</style>

<!-- Mode toggle -->
<div class="card" style="margin-bottom:20px;overflow:hidden">
  <div style="height:4px;background:linear-gradient(90deg,var(--primary),#c2185b)"></div>
  <div class="card-body" style="display:flex;gap:10px">
    <button type="button" id="tabVoucherBtn" class="btn btn-primary mode-tab" style="flex:1" onclick="switchMode('voucher')">
      <i class="fas fa-file-invoice"></i> Pay Against a Voucher
    </button>
    <button type="button" id="tabExtraBtn" class="btn btn-outline mode-tab" style="flex:1" onclick="switchMode('extra')">
      <i class="fas fa-piggy-bank"></i> Extra Payment / Apply to Dues
    </button>
  </div>
</div>

<!-- ─────────────────────────────  MODE 1: VOUCHER  ───────────────────────────── -->
<div id="voucherMode" class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice" style="color:var(--primary)"></i> Pay Against a Voucher</div></div>
  <div class="card-body">

    <div class="form-group" style="position:relative;max-width:520px">
      <label class="form-label">Voucher No. *</label>
      <div style="display:flex;gap:10px;align-items:stretch">
        <div style="position:relative;flex:1">
          <i class="fas fa-file-invoice" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none"></i>
          <input type="text" id="voucherSearch" class="form-control" style="padding-left:36px" placeholder="e.g. VCH-260726-45A4B" autocomplete="off">
        </div>
        <button type="button" id="voucherSearchBtn" class="btn btn-primary" style="flex-shrink:0;padding:0 20px;display:flex;align-items:center;gap:8px;white-space:nowrap">
          <i class="fas fa-magnifying-glass"></i> Search
        </button>
      </div>
      <div id="voucherResults" style="display:none;position:absolute;top:100%;left:0;right:74px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);z-index:20;max-height:280px;overflow-y:auto;margin-top:4px"></div>
    </div>

    <!-- Not-found / invalid voucher state -->
    <div id="voucherNotFound" style="display:none;background:linear-gradient(135deg,#fef2f2,#fee2e2);border:1.5px solid #fca5a5;border-radius:12px;padding:16px 18px;margin:16px 0;max-width:520px;animation:fadeSlideIn .25s ease">
      <div style="display:flex;gap:12px;align-items:flex-start">
        <div style="width:34px;height:34px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(239,68,68,.35)">
          <i class="fas fa-triangle-exclamation" style="color:#fff;font-size:14px"></i>
        </div>
        <div>
          <div style="font-weight:800;font-size:14px;color:#991b1b">Voucher not found</div>
          <div style="font-size:12.5px;color:#b91c1c;margin-top:3px">Please add correct voucher number, or this voucher does not exist.</div>
        </div>
      </div>
    </div>

    <!-- Found, but nothing left to collect -->
    <div id="voucherAlreadySettled" style="display:none;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:12px;padding:16px 18px;margin:16px 0;max-width:520px;animation:fadeSlideIn .25s ease">
      <div style="display:flex;gap:12px;align-items:flex-start">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--success);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(16,185,129,.35)">
          <i class="fas fa-check" style="color:#fff;font-size:14px"></i>
        </div>
        <div>
          <div id="vsTitle" style="font-weight:800;font-size:14px;color:#166534">Voucher already fully paid</div>
          <div id="vsSubtitle" style="font-size:12.5px;color:#15803d;margin-top:3px"></div>
        </div>
      </div>
    </div>

    <div id="voucherCard" style="display:none;background:linear-gradient(135deg,#eef2ff,#f5f3ff);border:1.5px solid var(--primary);border-radius:14px;padding:18px 20px;margin:16px 0;max-width:520px;box-shadow:0 10px 26px rgba(137,12,37,.1);animation:fadeSlideIn .25s ease">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="width:38px;height:38px;border-radius:10px;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 10px rgba(137,12,37,.3)">
            <i class="fas fa-circle-check" style="color:#fff;font-size:15px"></i>
          </div>
          <div>
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:var(--primary);font-weight:700;margin-bottom:1px">Voucher found</div>
            <div id="vcStudentName" style="font-weight:800;font-size:15px"></div>
            <div id="vcVoucherTitle" style="font-size:12.5px;color:var(--text-muted);margin-top:2px"></div>
          </div>
        </div>
        <button type="button" id="clearVoucher" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-xmark"></i></button>
      </div>
      <div style="display:flex;gap:20px;margin-top:14px;flex-wrap:wrap;padding-top:14px;border-top:1px dashed rgba(137,12,37,.2)">
        <div>
          <div style="font-size:10.5px;text-transform:uppercase;color:var(--text-muted);font-weight:600">Total</div>
          <div id="vcTotal" style="font-weight:700;font-size:14px"></div>
        </div>
        <div>
          <div style="font-size:10.5px;text-transform:uppercase;color:var(--text-muted);font-weight:600">Already Paid</div>
          <div id="vcPaid" style="font-weight:700;font-size:14px"></div>
        </div>
        <div>
          <div id="vcBalanceLabel" style="font-size:10.5px;text-transform:uppercase;color:var(--danger);font-weight:600">Balance Due</div>
          <div id="vcBalance" style="font-weight:800;font-size:16px;color:var(--danger)"></div>
        </div>
      </div>
      <div id="vcCarryForward" style="display:none;margin-top:10px;padding:9px 11px;border-radius:9px;background:#fff1f2;color:#9f1239;font-size:12px;font-weight:700"></div>
    </div>

    <form id="voucherPayForm" action="" method="POST" enctype="multipart/form-data" style="display:none;max-width:520px">
      @csrf
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Amount *</label>
          <input type="number" step="1" min="1" name="amount" id="voucherAmount" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Method *</label>
          <select name="method" id="voucherMethod" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Date Received</label>
          <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Reference No.</label>
          <input type="text" name="reference_no" class="form-control" placeholder="Optional">
        </div>
      </div>

      <div id="voucherProofGroup" class="form-group" style="display:none;margin-top:14px;margin-bottom:0">
        <label class="form-label"><i class="fas fa-paperclip" style="color:var(--primary)"></i> Payment Proof <span style="font-weight:400;color:var(--text-muted)">(optional — screenshot or photo)</span></label>
        <div class="proof-upload" id="voucherProofDrop">
          <input type="file" name="proof" id="voucherProofInput" accept="image/*,.pdf" style="display:none">
          <div class="proof-upload-inner" id="voucherProofInner">
            <i class="fas fa-cloud-arrow-up"></i>
            <span>Click to attach a payment screenshot / receipt <small>(JPG, PNG or PDF, max 5MB)</small></span>
          </div>
          <div class="proof-preview" id="voucherProofPreview">
            <img id="voucherProofImg" style="display:none">
            <div class="proof-file-icon" id="voucherProofFileIcon" style="display:none"><i class="fas fa-file-pdf"></i></div>
            <span class="proof-name" id="voucherProofName"></span>
            <button type="button" class="proof-remove" id="voucherProofRemove" title="Remove"><i class="fas fa-xmark"></i></button>
          </div>
        </div>
      </div>

      <div class="form-group" style="margin-top:14px;margin-bottom:0">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:16px">
        <i class="fas fa-check"></i> Receive Payment
      </button>
    </form>

    <div id="voucherEmptyHint" style="background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#1e40af;max-width:520px;display:flex;gap:8px;align-items:center">
      <i class="fas fa-circle-info" style="color:#3b82f6"></i> Type a voucher number and hit <strong style="margin:0 2px">Search</strong>, or pick one from the suggestions as you type.
    </div>
  </div>
</div>

<!-- ─────────────────────────────  MODE 2: EXTRA / BALANCE  ───────────────────────────── -->
<div id="extraMode" class="card" style="display:none">
  <div class="card-header"><div class="card-title"><i class="fas fa-piggy-bank" style="color:var(--primary)"></i> Extra Payment — Settle Dues &amp; Keep Surplus</div></div>
  <div class="card-body">
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
      No voucher number is needed. The payment settles outstanding vouchers first; only surplus remains as student credit.
      A payment receiving receipt opens after saving. Use Previous Receipts to reprint money already received.
    </p>

    <div class="form-group" style="position:relative;max-width:420px">
      <label class="form-label">Find Student (Student ID / Name) *</label>
      <input type="text" id="studentSearch" class="form-control" placeholder="Start typing to search..." autocomplete="off">
      <div id="studentResults" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);z-index:20;max-height:280px;overflow-y:auto;margin-top:4px"></div>
    </div>

    <div id="studentCard" style="display:none;background:#eef2ff;border:1.5px solid var(--primary);border-radius:12px;padding:14px 16px;margin:16px 0;gap:12px;align-items:center;max-width:520px">
      <div id="studentAvatar" style="width:42px;height:42px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0"></div>
      <div style="min-width:0;flex:1">
        <div id="studentCardName" style="font-weight:700;font-size:14px"></div>
        <div id="studentCardMeta" style="font-size:11.5px;color:var(--text-muted)"></div>
        <div id="studentCardBalance" style="font-size:11.5px;color:var(--text-muted);margin-top:2px"></div>
      </div>
      <button type="button" id="clearStudent" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-xmark"></i></button>
    </div>

    <form id="extraPayForm" action="" method="POST" style="display:none;max-width:520px">
      @csrf
      <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Amount *</label>
          <input type="number" step="1" min="1" name="amount" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Method *</label>
          <select name="method" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Date Received</label>
          <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Reference No.</label>
          <input type="text" name="reference_no" class="form-control" placeholder="Optional">
        </div>
      </div>
      <div class="form-group" style="margin-top:14px;margin-bottom:0">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional">
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:16px">
        <i class="fas fa-receipt"></i> Receive, Apply &amp; Print Receipt
      </button>
    </form>

    <a id="extraReceiptsLink" href="#" class="btn btn-outline" style="display:none;margin-top:16px">View / Print Previous Receipts</a>
    <form id="applyExistingCreditForm" method="POST" style="display:none;margin-top:16px">
      @csrf
      <button type="submit" class="btn btn-outline"><i class="fas fa-check"></i> Apply Existing Credit to Vouchers</button>
      <p class="text-sm text-muted">Uses money already received. This does not record a new payment.</p>
    </form>
    <div id="studentEmptyHint" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#1e40af;max-width:520px">
      <i class="fas fa-circle-info"></i> Search and select a student above — no voucher number is needed for an extra payment.
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const voucherLookupUrl = "{{ route('vouchers.lookup') }}";
  const studentLookupUrl = "{{ route('accounts.students.lookup') }}";

  // ── Mode toggle ──
  window.switchMode = function (mode) {
    const isVoucher = mode === 'voucher';
    document.getElementById('voucherMode').style.display = isVoucher ? 'block' : 'none';
    document.getElementById('extraMode').style.display = isVoucher ? 'none' : 'block';
    document.getElementById('tabVoucherBtn').className = 'btn ' + (isVoucher ? 'btn-primary' : 'btn-outline');
    document.getElementById('tabExtraBtn').className = 'btn ' + (isVoucher ? 'btn-outline' : 'btn-primary');
  };

  // ── Voucher mode ──
  const voucherSearch     = document.getElementById('voucherSearch');
  const voucherSearchBtn  = document.getElementById('voucherSearchBtn');
  const voucherResults    = document.getElementById('voucherResults');
  const voucherCard       = document.getElementById('voucherCard');
  const voucherNotFound   = document.getElementById('voucherNotFound');
  const voucherSettled    = document.getElementById('voucherAlreadySettled');
  const voucherPayForm    = document.getElementById('voucherPayForm');
  const voucherAmount     = document.getElementById('voucherAmount');
  const voucherHint       = document.getElementById('voucherEmptyHint');
  let vDebounce = null;

  // Hides every result/feedback panel so only one state shows at a time.
  function resetVoucherPanels() {
    voucherCard.style.display     = 'none';
    voucherNotFound.style.display = 'none';
    voucherSettled.style.display  = 'none';
    voucherPayForm.style.display  = 'none';
    voucherSearch.classList.remove('is-invalid');
  }

  voucherSearch.addEventListener('input', function () {
    const q = this.value.trim();
    clearTimeout(vDebounce);
    resetVoucherPanels();
    voucherHint.style.display = 'block';
    if (q.length < 2) { voucherResults.style.display = 'none'; voucherResults.innerHTML = ''; return; }
    vDebounce = setTimeout(() => {
      fetch(voucherLookupUrl + '?voucher_no=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(vouchers => {
          voucherResults.innerHTML = '';
          if (!vouchers.length) {
            voucherResults.innerHTML = '<div style="padding:14px;font-size:12.5px;color:#94a3b8;text-align:center">No matching unpaid vouchers found.</div>';
            voucherResults.style.display = 'block';
            return;
          }
          vouchers.forEach(v => {
            const row = document.createElement('div');
            row.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9';
            row.onmouseenter = () => row.style.background = '#f8fafc';
            row.onmouseleave = () => row.style.background = '#fff';
            row.innerHTML = `
              <div style="font-weight:700;font-size:13px">${v.voucher_no} <span style="font-weight:500;color:#64748b">— ${v.student_name}</span></div>
              <div style="font-size:11px;color:#64748b">${v.title} · Balance Rs.${Math.round(Number(v.balance)).toLocaleString('en-US', { maximumFractionDigits: 0 })}</div>`;
            row.addEventListener('click', () => selectVoucher(v));
            voucherResults.appendChild(row);
          });
          voucherResults.style.display = 'block';
        });
    }, 300);
  });

  // Explicit "Search" button — resolves the exact voucher number typed in,
  // across every status, and shows a clear found / not-found / already-paid
  // result instead of relying on the autocomplete dropdown.
  function runVoucherSearch() {
    const q = voucherSearch.value.trim();
    voucherResults.style.display = 'none';
    resetVoucherPanels();

    if (q.length < 1) {
      voucherSearch.classList.add('is-invalid');
      voucherSearch.focus();
      return;
    }

    voucherSearchBtn.disabled = true;
    const originalLabel = voucherSearchBtn.innerHTML;
    voucherSearchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

    fetch(voucherLookupUrl + '?exact=1&voucher_no=' + encodeURIComponent(q))
      .then(res => res.json())
      .then(vouchers => {
        const v = vouchers.find(x => x.voucher_no.toLowerCase() === q.toLowerCase()) || vouchers[0];

        if (!v) {
          voucherHint.style.display = 'none';
          voucherNotFound.style.display = 'block';
          voucherSearch.classList.add('is-invalid');
          return;
        }

        if (v.balance <= 0 || v.status === 'paid' || v.status === 'cancelled') {
          voucherHint.style.display = 'none';
          document.getElementById('vsTitle').textContent = v.status === 'cancelled'
            ? 'This voucher has been cancelled'
            : 'Voucher already fully paid';
          document.getElementById('vsSubtitle').textContent =
            v.voucher_no + ' — ' + v.student_name + (v.status === 'cancelled' ? '' : ' has no remaining balance to collect.');
          voucherSettled.style.display = 'block';
          return;
        }

        selectVoucher(v);
      })
      .finally(() => {
        voucherSearchBtn.disabled = false;
        voucherSearchBtn.innerHTML = originalLabel;
      });
  }

  voucherSearchBtn.addEventListener('click', runVoucherSearch);
  voucherSearch.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); runVoucherSearch(); }
  });

  document.addEventListener('click', function (e) {
    if (!voucherResults.contains(e.target) && e.target !== voucherSearch) voucherResults.style.display = 'none';
    if (!studentResults.contains(e.target) && e.target !== studentSearch) studentResults.style.display = 'none';
  });

  function selectVoucher(v) {
    voucherSearch.value = v.voucher_no;
    voucherSearch.classList.remove('is-invalid');
    voucherResults.style.display = 'none';
    voucherNotFound.style.display = 'none';
    voucherSettled.style.display = 'none';

    document.getElementById('vcStudentName').textContent = v.student_name + ' (' + v.student_code + ')';
    document.getElementById('vcVoucherTitle').textContent = v.title + (v.due_date ? ' · Due ' + v.due_date : '');
    document.getElementById('vcTotal').textContent   = 'Rs.' + Math.round(Number(v.total)).toLocaleString('en-US', { maximumFractionDigits: 0 });
    document.getElementById('vcPaid').textContent    = 'Rs.' + Math.round(Number(v.paid_amount)).toLocaleString('en-US', { maximumFractionDigits: 0 });
    document.getElementById('vcBalance').textContent = 'Rs.' + Math.round(Number(v.balance)).toLocaleString('en-US', { maximumFractionDigits: 0 });
    const carryForward = Math.round(Number(v.carry_forward || 0));
    const carryEl = document.getElementById('vcCarryForward');
    document.getElementById('vcBalanceLabel').textContent = carryForward > 0 ? 'Amount to Collect Now' : 'Balance Due';
    if (carryForward > 0) {
      carryEl.textContent = '+ Rs.' + carryForward.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' previous balance carried forward';
      carryEl.style.display = 'block';
    } else {
      carryEl.style.display = 'none';
      carryEl.textContent = '';
    }
    voucherCard.style.display = 'block';

    voucherAmount.max = Math.round(Number(v.balance));
    voucherAmount.value = Math.round(Number(v.balance));
    voucherPayForm.action = '{{ url("/accounts/vouchers") }}/' + v.id + '/payments';
    voucherPayForm.style.display = 'block';
    voucherHint.style.display = 'none';
  }

  document.getElementById('clearVoucher').addEventListener('click', function () {
    voucherSearch.value = '';
    resetVoucherPanels();
    voucherHint.style.display = 'block';
    voucherPayForm.reset();
    document.getElementById('voucherMethod').dispatchEvent(new Event('change'));
  });

  // ── Payment proof upload (shared helper) ──
  // Cash payments don't need a proof; online/bank transfer/cheque get an
  // optional upload so Accounts can attach a screenshot or scanned receipt.
  function wireProofUpload(prefix) {
    const methodSelect = document.getElementById(prefix + 'Method');
    const group   = document.getElementById(prefix + 'ProofGroup');
    const drop     = document.getElementById(prefix + 'ProofDrop');
    const input    = document.getElementById(prefix + 'ProofInput');
    const inner    = document.getElementById(prefix + 'ProofInner');
    const preview  = document.getElementById(prefix + 'ProofPreview');
    const img      = document.getElementById(prefix + 'ProofImg');
    const fileIcon = document.getElementById(prefix + 'ProofFileIcon');
    const nameEl   = document.getElementById(prefix + 'ProofName');
    const removeBtn = document.getElementById(prefix + 'ProofRemove');
    if (!methodSelect || !group) return;

    function toggleGroup() {
      const needsProof = methodSelect.value !== 'cash';
      group.style.display = needsProof ? 'block' : 'none';
      if (!needsProof) clearFile();
    }

    function clearFile() {
      input.value = '';
      inner.style.display = 'flex';
      preview.style.display = 'none';
      img.style.display = 'none';
      fileIcon.style.display = 'none';
    }

    drop.addEventListener('click', function (e) {
      if (e.target !== removeBtn && !removeBtn.contains(e.target)) input.click();
    });

    input.addEventListener('change', function () {
      const file = input.files[0];
      if (!file) { clearFile(); return; }

      if (file.size > 5 * 1024 * 1024) {
        alert('File is larger than 5MB — please choose a smaller screenshot or PDF.');
        clearFile();
        return;
      }

      inner.style.display = 'none';
      preview.style.display = 'flex';
      nameEl.textContent = file.name;

      if (file.type.startsWith('image/')) {
        img.style.display = 'block';
        fileIcon.style.display = 'none';
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; };
        reader.readAsDataURL(file);
      } else {
        img.style.display = 'none';
        fileIcon.style.display = 'flex';
      }
    });

    removeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      clearFile();
    });

    methodSelect.addEventListener('change', toggleGroup);
    toggleGroup();
  }
  wireProofUpload('voucher');

  // ── Extra / balance mode ──
  const studentSearch  = document.getElementById('studentSearch');
  const studentResults = document.getElementById('studentResults');
  const studentCard    = document.getElementById('studentCard');
  const extraPayForm   = document.getElementById('extraPayForm');
  const studentHint    = document.getElementById('studentEmptyHint');
  let sDebounce = null;

  studentSearch.addEventListener('input', function () {
    const q = this.value.trim();
    clearTimeout(sDebounce);
    if (q.length < 2) { studentResults.style.display = 'none'; studentResults.innerHTML = ''; return; }
    sDebounce = setTimeout(() => {
      fetch(studentLookupUrl + '?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(students => {
          studentResults.innerHTML = '';
          if (!students.length) {
            studentResults.innerHTML = '<div style="padding:14px;font-size:12.5px;color:#94a3b8;text-align:center">No matching students found.</div>';
            studentResults.style.display = 'block';
            return;
          }
          students.forEach(s => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9';
            row.onmouseenter = () => row.style.background = '#f8fafc';
            row.onmouseleave = () => row.style.background = '#fff';
            row.innerHTML = `
              <div style="width:34px;height:34px;border-radius:50%;background:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">${(s.name || '?').substring(0,2).toUpperCase()}</div>
              <div style="min-width:0">
                <div style="font-weight:600;font-size:13px">${s.name}</div>
                <div style="font-size:11px;color:#64748b">${s.student_id || ''}${s.class_name ? ' · ' + s.class_name : ''}</div>
              </div>`;
            row.addEventListener('click', () => selectStudent(s));
            studentResults.appendChild(row);
          });
          studentResults.style.display = 'block';
        });
    }, 300);
  });

  function selectStudent(s) {
    studentSearch.value = s.name;
    studentResults.style.display = 'none';

    document.getElementById('studentAvatar').textContent = (s.name || '?').substring(0,2).toUpperCase();
    document.getElementById('studentCardName').textContent = s.name + ' (' + (s.student_id || '') + ')';
    document.getElementById('studentCardMeta').textContent = [s.class_name, s.program_name].filter(Boolean).join(' · ');
    document.getElementById('studentCardBalance').textContent = 'Existing credit: Rs.' + Number(s.credit_balance || 0).toLocaleString() + '. New payments settle outstanding vouchers first; only surplus stays as credit.';
    studentCard.style.display = 'flex';

    document.getElementById('applyExistingCreditForm').action = '{{ url("/accounts/students") }}/' + s.id + '/apply-credit';
    document.getElementById('applyExistingCreditForm').style.display = Number(s.credit_balance || 0) > 0 ? 'block' : 'none';
    document.getElementById('extraReceiptsLink').href = '{{ url("/accounts/students") }}/' + encodeURIComponent(s.id) + '/extra-payment-receipts';
    document.getElementById('extraReceiptsLink').style.display = 'inline-flex';
    extraPayForm.action = '{{ url("/accounts/students") }}/' + s.id + '/balance-credit';
    extraPayForm.style.display = 'block';
    studentHint.style.display = 'none';
  }

  document.getElementById('clearStudent').addEventListener('click', function () {
    studentSearch.value = '';
    studentCard.style.display = 'none';
    extraPayForm.style.display = 'none';
    document.getElementById('extraReceiptsLink').style.display = 'none';
    document.getElementById('applyExistingCreditForm').style.display = 'none';
    studentHint.style.display = 'block';
  });
})();
</script>
@endpush

@endsection
