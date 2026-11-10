@extends('layouts.app')
@section('title', 'Settings')
@section('page_title', 'Settings')
@section('content')
<div style="font-size:22px;font-weight:800;margin-bottom:20px">System Settings</div>

@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:14px 16px;border-radius:8px;margin-bottom:18px">
    <strong><i class="fas fa-exclamation-circle"></i> Settings were NOT saved — please fix the following:</strong>
    <ul style="margin:8px 0 0 20px;padding:0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
  @csrf
  <div class="grid grid-2" style="gap:20px">

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-building" style="color:var(--primary);margin-right:8px"></i>Institution / Campus Setup</div></div>
      <div class="card-body">
        <div class="form-group"><label class="form-label">Institution Name</label>
          <input type="text" name="institution_name" class="form-control" value="{{ old('institution_name', $settings['institution_name'] ?? 'Acardix Institute') }}"></div>
        <div class="form-group"><label class="form-label">Tagline</label>
          <input type="text" name="institution_tagline" class="form-control" value="{{ old('institution_tagline', $settings['institution_tagline'] ?? '') }}" placeholder="e.g. Excellence in Education"></div>
        <div class="form-group"><label class="form-label">Campus Name</label>
          <input type="text" name="campus_name" class="form-control" value="{{ old('campus_name', $settings['campus_name'] ?? '') }}"></div>
        <div class="form-group"><label class="form-label">Login Domain <span style="font-weight:400;color:var(--text-muted)">(used for auto-generated Student/Parent logins, e.g. cyberorion.pk)</span></label>
          <input type="text" name="institution_domain" class="form-control" value="{{ old('institution_domain', $settings['institution_domain'] ?? '') }}" placeholder="e.g. cyberorion.pk — leave blank to use <slug>.cloud"></div>
        <div class="grid grid-2" style="gap:12px">
          <div class="form-group"><label class="form-label">Academic Session</label>
            <input type="text" name="academic_session" class="form-control" value="{{ old('academic_session', $settings['academic_session'] ?? now()->year.'-'.(now()->year+1)) }}"></div>
          <div class="form-group"><label class="form-label">Currency</label>
            <input type="text" name="currency" class="form-control" value="{{ old('currency', $settings['currency'] ?? 'PKR') }}"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Institution Logo</label>
          <div style="display:flex;align-items:center;gap:14px">
            @if($settings['institution_logo'] ?? null)
              <img src="{{ storage_asset($settings['institution_logo']) }}" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
            @endif
            <input type="file" name="institution_logo" accept="image/*" class="form-control">
          </div>
          <small style="color:var(--text-muted)">Shown on login pages, ID cards, vouchers, marksheets, timetables, and installment plans. JPG/PNG, up to 5MB.</small>
          @error('institution_logo')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-sliders" style="color:var(--primary);margin-right:8px"></i>Fee Policy Configuration</div></div>
      <div class="card-body">
        <div class="form-group"><label class="form-label">Max Installments Allowed</label>
          <input type="number" min="1" max="12" name="installment_max" class="form-control" value="{{ old('installment_max', $settings['installment_max'] ?? 5) }}"></div>
        <div style="padding:10px 12px;border-radius:8px;background:rgba(34,197,94,.08);color:var(--text-muted);font-size:12px;margin-bottom:12px"><i class="fas fa-circle-check" style="color:var(--success);margin-right:5px"></i>Overdue monetary fines are disabled. Past-due vouchers may still be marked Overdue for status tracking, but no extra amount is added.</div>
        <div class="form-group"><label class="form-label">Tax % on Vouchers (optional)</label>
          <input type="number" step="0.01" min="0" name="tax_percent" class="form-control" value="{{ old('tax_percent', $settings['tax_percent'] ?? 0) }}"></div>
        <div class="form-group"><label class="form-label">Fee Summary Slip — Note (e.g. "Fee is neither refundable nor adjustable")</label>
          <textarea name="fee_summary_note" class="form-control" rows="2">{{ old('fee_summary_note', $settings['fee_summary_note'] ?? '') }}</textarea></div>
        <div class="form-group"><label class="form-label">Fee Summary Slip — Declaration text</label>
          <small style="display:block;color:var(--text-muted);margin-bottom:6px">This single declaration appears on vouchers and installment plans. Clear it and save settings to hide it everywhere.</small>
          <textarea name="fee_summary_declaration" class="form-control" rows="3">{{ old('fee_summary_declaration', $settings['fee_summary_declaration'] ?? '') }}</textarea></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px"></i>Attendance-linked Fee Hold</div></div>
      <div class="card-body">
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px;cursor:pointer">
          <input type="checkbox" name="attendance_hold_enabled" value="1" {{ old('attendance_hold_enabled', $settings['attendance_hold_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
          Block exam-fee vouchers when a student's attendance is below the threshold
        </label>
        <div class="form-group"><label class="form-label">Attendance Threshold (%)</label>
          <input type="number" step="0.01" min="0" max="100" name="attendance_threshold" class="form-control" value="{{ old('attendance_threshold', $settings['attendance_threshold'] ?? 75) }}"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-id-card" style="color:var(--primary);margin-right:8px"></i>ID Card Settings</div></div>
      <div class="card-body">
        <div class="form-group"><label class="form-label">ID Card Reissue Fee (Rs.)</label>
          <input type="number" step="0.01" min="0" name="id_card_reissue_fee" class="form-control" value="{{ old('id_card_reissue_fee', $settings['id_card_reissue_fee'] ?? 300) }}"></div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px">Manage payment bank accounts in the "Bank Accounts" section below — they're shown on vouchers and downloaded installment plans.</p>

        <div style="border-top:1px solid var(--border);padding-top:16px">
          <label class="form-label" style="display:flex;align-items:center;gap:7px;margin-bottom:10px">
            <i class="fas fa-signature" style="color:var(--primary)"></i> Authorized Signatory
          </label>
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
            This name and signature appear as "Authorized Signatory" on every staff &amp; student ID card.
          </p>

          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Name</label>
              <input type="text" name="authorized_signatory_name" class="form-control" placeholder="e.g. Dr. Ahmed Raza"
                     value="{{ old('authorized_signatory_name', $settings['authorized_signatory_name'] ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Designation</label>
              <input type="text" name="authorized_signatory_title" class="form-control" placeholder="e.g. Principal / Registrar"
                     value="{{ old('authorized_signatory_title', $settings['authorized_signatory_title'] ?? '') }}"></div>
          </div>

          @if($settings['authorized_signatory_signature'] ?? null)
            <div id="asig-existing" style="display:flex;align-items:center;gap:14px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:14px;background:var(--bg)">
              <img src="{{ storage_asset($settings['authorized_signatory_signature']) }}" alt="Authorized signature"
                   style="height:44px;max-width:160px;object-fit:contain;background:#fff;border-radius:4px;padding:4px">
              <div style="flex:1;font-size:12px;color:var(--text-muted)">Signature on file — used on every printed ID card.</div>
              <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--danger);cursor:pointer;white-space:nowrap">
                <input type="checkbox" name="remove_authorized_signature" value="1"> Remove
              </label>
            </div>
          @endif

          <div class="sig-tabs" style="display:flex;gap:8px;margin-bottom:10px">
            <button type="button" class="btn btn-outline btn-sm asig-tab-btn active" data-tab="draw" onclick="switchAsigTab('draw')"><i class="fas fa-pen-nib"></i> Draw</button>
            <button type="button" class="btn btn-outline btn-sm asig-tab-btn" data-tab="upload" onclick="switchAsigTab('upload')"><i class="fas fa-upload"></i> Upload Image</button>
          </div>

          <div id="asig-tab-draw">
            <div style="border:2px dashed var(--border);border-radius:8px;overflow:hidden;background:#fff">
              <canvas id="asig-pad" width="500" height="140" style="width:100%;height:140px;cursor:crosshair;touch-action:none;display:block"></canvas>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
              <span style="font-size:11px;color:var(--text-muted)">Sign above with mouse / touch / stylus.</span>
              <button type="button" class="btn btn-outline btn-sm" onclick="clearAsigPad()"><i class="fas fa-eraser"></i> Clear</button>
            </div>
            <input type="hidden" name="authorized_signatory_signature_data" id="authorized_signatory_signature_data">
          </div>

          <div id="asig-tab-upload" style="display:none">
            <input type="file" name="authorized_signatory_signature" class="form-control" accept="image/png,image/jpeg" onchange="previewAsigUpload(this)">
            <img id="asig-upload-preview" style="display:none;height:50px;margin-top:10px;background:#fff;border:1px solid var(--border);border-radius:4px;padding:4px">
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px">PNG with a transparent background works best.</div>
          </div>
          @error('authorized_signatory_signature')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>

  </div>
  <div style="margin-top:20px;display:flex;gap:12px;justify-content:flex-end">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
  </div>
</form>

<div class="card" style="margin-top:20px">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
    <div class="card-title"><i class="fas fa-building-columns" style="color:var(--primary);margin-right:8px"></i>Bank Accounts (shown on vouchers &amp; installment plans)</div>
    @if(auth()->user()->isAccountant())
    <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('new-bank-form').style.display='flex'"><i class="fas fa-plus"></i> Add Account</button>
    @endif
  </div>
  <div class="card-body">

    @if(auth()->user()->isAccountant())
    <form id="new-bank-form" action="{{ route('bank-accounts.store') }}" method="POST" style="display:none;flex-wrap:wrap;gap:10px;margin-bottom:18px;padding:14px;background:var(--bg);border-radius:10px">
      @csrf
      <input type="text" name="bank_name" class="form-control" placeholder="Bank Name *" required style="flex:1;min-width:150px">
      <input type="text" name="account_title" class="form-control" placeholder="Account Title *" required style="flex:1;min-width:150px">
      <input type="text" name="account_number" class="form-control" placeholder="Account No. *" required style="flex:1;min-width:150px">
      <input type="text" name="branch_name" class="form-control" placeholder="Branch Name" style="flex:1;min-width:150px">
      <input type="text" name="iban" class="form-control" placeholder="IBAN (optional)" style="flex:1;min-width:150px">
      <label style="display:flex;align-items:center;gap:6px;font-size:13px"><input type="checkbox" name="is_default" value="1"> Default</label>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Account</button>
    </form>
    @else
    <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:14px"><i class="fas fa-circle-info"></i> Managed by the Accounts department — read-only here.</div>
    @endif

    <div class="table-wrap">
      <table>
        <thead><tr><th>Bank</th><th>Title</th><th>Account No.</th><th>Branch</th><th>Default</th><th>Active</th>@if(auth()->user()->isAccountant())<th></th>@endif</tr></thead>
        <tbody>
          @forelse($bankAccounts as $bank)
          @php $fid = 'bank-form-'.$bank->id; @endphp
          <tr>
            @if(auth()->user()->isAccountant())
            <td><input form="{{ $fid }}" type="text" name="bank_name" value="{{ $bank->bank_name }}" class="form-control" style="min-width:120px"></td>
            <td><input form="{{ $fid }}" type="text" name="account_title" value="{{ $bank->account_title }}" class="form-control" style="min-width:120px"></td>
            <td><input form="{{ $fid }}" type="text" name="account_number" value="{{ $bank->account_number }}" class="form-control" style="min-width:120px"></td>
            <td><input form="{{ $fid }}" type="text" name="branch_name" value="{{ $bank->branch_name }}" class="form-control" style="min-width:110px"></td>
            <td style="text-align:center"><input form="{{ $fid }}" type="checkbox" name="is_default" value="1" {{ $bank->is_default ? 'checked' : '' }}></td>
            <td style="text-align:center"><input form="{{ $fid }}" type="checkbox" name="is_active" value="1" {{ $bank->is_active ? 'checked' : '' }}></td>
            <td style="display:flex;gap:6px">
              <button form="{{ $fid }}" type="submit" class="btn btn-outline btn-sm" title="Save"><i class="fas fa-check"></i></button>
              <button type="submit" form="del-{{ $fid }}" class="btn btn-outline btn-sm" title="Delete" onclick="return confirm('Remove this bank account?')"><i class="fas fa-trash"></i></button>
            </td>
            @else
            <td>{{ $bank->bank_name }}</td>
            <td>{{ $bank->account_title }}</td>
            <td>{{ $bank->account_number }}</td>
            <td>{{ $bank->branch_name }}</td>
            <td style="text-align:center">{{ $bank->is_default ? '✓' : '' }}</td>
            <td style="text-align:center">{{ $bank->is_active ? '✓' : '' }}</td>
            @endif
          </tr>
          @empty
          <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">No bank accounts added yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Standalone forms associated to each row above via the HTML `form` attribute (forms can't nest inside <tr>) --}}
    @if(auth()->user()->isAccountant())
    @foreach($bankAccounts as $bank)
      <form id="bank-form-{{ $bank->id }}" action="{{ route('bank-accounts.update', $bank) }}" method="POST" style="display:none">
        @csrf @method('PUT')
      </form>
      <form id="del-bank-form-{{ $bank->id }}" action="{{ route('bank-accounts.destroy', $bank) }}" method="POST" style="display:none">
        @csrf @method('DELETE')
      </form>
    @endforeach
    @endif
  </div>
</div>

@push('scripts')
<script>
function previewAsigUpload(input) {
    const preview = document.getElementById('asig-upload-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'inline-block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function switchAsigTab(tab) {
    document.getElementById('asig-tab-draw').style.display   = tab === 'draw'   ? 'block' : 'none';
    document.getElementById('asig-tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.querySelectorAll('.asig-tab-btn').forEach(b => {
        const active = b.dataset.tab === tab;
        b.classList.toggle('active', active);
        b.style.background = active ? 'var(--primary)' : '';
        b.style.color      = active ? '#fff' : '';
        b.style.borderColor = active ? 'var(--primary)' : '';
    });
}

// ── Authorized-signatory pad (draw with mouse / touch / stylus) ──
(function () {
    const canvas = document.getElementById('asig-pad');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let drawing = false, hasDrawn = false, ratio = 1;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        ratio = window.devicePixelRatio || 1;
        canvas.width  = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1a0608';
    }
    resize();
    window.addEventListener('resize', resize);

    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }
    function start(e) { drawing = true; hasDrawn = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e)  { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end()     { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    window.clearAsigPad = function () {
        ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        hasDrawn = false;
        document.getElementById('authorized_signatory_signature_data').value = '';
    };

    const form = canvas.closest('form');
    form.addEventListener('submit', function () {
        if (hasDrawn) {
            document.getElementById('authorized_signatory_signature_data').value = canvas.toDataURL('image/png');
        }
    });
})();
</script>
@endpush
@endsection
