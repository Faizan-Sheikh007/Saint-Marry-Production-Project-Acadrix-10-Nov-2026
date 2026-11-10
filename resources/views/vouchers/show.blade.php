@extends('layouts.app')
@section('title', $voucher->voucher_no)
@section('page_title', 'Voucher Details')

@push('styles')
<style>
  .vh-actions{display:flex;align-items:center;gap:10px;margin-bottom:22px;flex-wrap:wrap}
  .vh-actions .btn{border-radius:10px;font-weight:600;transition:transform .15s ease,box-shadow .15s ease}
  .vh-actions .btn:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(0,0,0,.08)}

.vh-grid{display:grid;grid-template-columns:1.15fr 1fr;gap:24px;align-items:start}
  @media (max-width:960px){.vh-grid{grid-template-columns:1fr}}

  .vh-hero{
    position:relative;overflow:hidden;border-radius:18px;
    background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 55%, #2b0713 100%);
    color:#fff;padding:26px 26px 22px;box-shadow:0 12px 30px rgba(137,12,37,.28);
  }
  .vh-hero::before{
    content:"";position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;
    background:radial-gradient(circle, rgba(255,255,255,.14), transparent 70%);
  }
  .vh-hero::after{
    content:"";position:absolute;bottom:-80px;left:-40px;width:180px;height:180px;border-radius:50%;
    background:radial-gradient(circle, rgba(255,255,255,.08), transparent 70%);
  }
  .vh-hero-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;position:relative;z-index:1}
  .vh-hero-no{font-size:22px;font-weight:800;letter-spacing:.5px;display:flex;align-items:center;gap:10px}
  .vh-hero-no i{opacity:.85;font-size:18px}
  .vh-status-pill{
    display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;
    background:rgba(255,255,255,.18);backdrop-filter:blur(4px);font-weight:700;font-size:12.5px;
    border:1px solid rgba(255,255,255,.25);white-space:nowrap;
  }

  .vh-info-row{
    display:grid;grid-template-columns:repeat(2,1fr);gap:14px 22px;margin-top:20px;position:relative;z-index:1;
  }
  @media (max-width:520px){.vh-info-row{grid-template-columns:1fr}}
  .vh-info-item{display:flex;flex-direction:column;gap:2px}
  .vh-info-label{font-size:11px;text-transform:uppercase;letter-spacing:.6px;opacity:.7;font-weight:600}
  .vh-info-value{font-size:14.5px;font-weight:600}
  .vh-info-value a{text-decoration:underline;text-underline-offset:2px}

  .vh-amount-card{
    background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;margin-top:20px;
    box-shadow:var(--shadow);
  }
  .vh-amount-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;font-size:14px}
  .vh-amount-row.fine{color:var(--danger)}
  .vh-amount-row.discount{color:var(--success)}
  .vh-amount-row .lbl{display:flex;align-items:center;gap:8px}
  .vh-amount-row .lbl i{width:16px;text-align:center;opacity:.75}
  .vh-divider{height:1px;background:var(--border);margin:6px 0}
  .vh-total-row{display:flex;justify-content:space-between;align-items:center;padding-top:10px;font-size:15px;font-weight:700}
  .vh-paid-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;color:var(--success);font-weight:700}

  .vh-progress-wrap{margin-top:14px}
  .vh-progress-track{height:10px;border-radius:999px;background:var(--bg);overflow:hidden;border:1px solid var(--border)}
  .vh-progress-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--success),#34d399);transition:width .4s ease}
  .vh-progress-labels{display:flex;justify-content:space-between;margin-top:6px;font-size:12px;color:var(--text-muted);font-weight:600}

  .vh-balance-card{
    margin-top:16px;border-radius:14px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;
  }
  .vh-balance-label{font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
  .vh-balance-value{font-size:22px;font-weight:800}

  .vh-panel{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
  .vh-panel + .vh-panel{margin-top:20px}
  .vh-panel-head{
    display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid var(--border);
    background:linear-gradient(180deg, rgba(137,12,37,.04), transparent);
  }
  .vh-panel-head i{
    width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,var(--primary-light),var(--primary-dark));color:#fff;font-size:14px;
  }
  .vh-panel-title{font-size:15px;font-weight:700}
  .vh-panel-body{padding:20px}

  .vh-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  @media (max-width:520px){.vh-form-grid{grid-template-columns:1fr}}
  .vh-form-group label{font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;display:block}
  .vh-form-group .form-control{border-radius:10px;padding:11px 12px;border:1.5px solid var(--border);font-size:14px}
  .vh-form-group .form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(137,12,37,.1)}

  .vh-submit-btn{
    width:100%;justify-content:center;margin-top:6px;padding:13px;border-radius:11px;font-weight:700;font-size:14.5px;
    background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);border:none;color:#fff;
    box-shadow:0 6px 16px rgba(137,12,37,.3);transition:transform .15s ease,box-shadow .15s ease;
    display:flex;align-items:center;gap:8px;cursor:pointer;
  }
  .vh-submit-btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(137,12,37,.4)}

  .vh-history-table{width:100%;border-collapse:collapse}
  .vh-history-table thead th{
    text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);
    padding:0 20px 12px;font-weight:700;
  }
  .vh-history-table tbody tr{border-top:1px solid var(--border)}
  .vh-history-table tbody tr:hover{background:var(--bg)}
  .vh-history-table td{padding:13px 20px;font-size:13.5px}
  .vh-pay-badge{display:inline-flex;align-items:center;gap:6px;font-weight:700;color:var(--success)}
  .vh-method-chip{
    display:inline-block;padding:3px 10px;border-radius:999px;background:var(--bg);border:1px solid var(--border);
    font-size:12px;font-weight:600;
  }
  .vh-empty-state{padding:40px 20px;text-align:center;color:var(--text-muted)}
  .vh-empty-state i{font-size:32px;opacity:.35;margin-bottom:10px;display:block}

  .proof-upload { border:1.5px dashed var(--border); border-radius:10px; background:var(--bg); transition:border-color .15s ease; }
  .proof-upload:hover { border-color:var(--primary); }
  .proof-upload-inner { display:flex; align-items:center; gap:10px; padding:12px 14px; cursor:pointer; font-size:12.5px; color:var(--text-muted); }
  .proof-upload-inner i { font-size:17px; color:var(--primary); flex-shrink:0; }
  .proof-upload-inner small { display:block; color:#94a3b8; font-size:11px; margin-top:2px; }
  .proof-preview { display:none; align-items:center; gap:12px; padding:9px 12px; }
  .proof-preview img { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border); flex-shrink:0; }
  .proof-preview .proof-file-icon { width:40px; height:40px; border-radius:8px; background:#fee2e2; color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
  .proof-preview .proof-name { font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:240px; }
  .proof-preview .proof-remove { margin-left:auto; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:4px; }
  .proof-preview .proof-remove:hover { color:var(--danger); }

  .vh-proof-thumb { width:32px; height:32px; border-radius:7px; object-fit:cover; border:1px solid var(--border); cursor:pointer; }
  .vh-proof-file { width:32px; height:32px; border-radius:7px; background:rgba(239,68,68,.1); color:var(--danger); display:inline-flex; align-items:center; justify-content:center; font-size:14px; }
</style>
@endpush

@section('content')
@php
  $statusMap = [
    'paid'      => ['icon' => 'fa-circle-check', 'label' => 'Paid'],
    'partial'   => ['icon' => 'fa-hourglass-half', 'label' => 'Partially Paid'],
    'overdue'   => ['icon' => 'fa-triangle-exclamation', 'label' => 'Overdue'],
    'unpaid'    => ['icon' => 'fa-clock', 'label' => 'Unpaid'],
    'cancelled' => ['icon' => 'fa-ban', 'label' => 'Cancelled'],
  ];
  // A voucher number can bundle more than one fee head (e.g. Tuition Fee +
  // Examination Fee on the same installment) as separate rows behind the
  // scenes — everything on this page uses the combined figures across the
  // whole group so the amount shown here always matches the browse list.
  $total = $voucher->combinedTotal();
  $combinedPaid = $voucher->combinedPaidAmount();
  $combinedBalance = $voucher->combinedBalance();
  $carryForwardBalance = $voucher->carryForwardBalance();
  $collectNowBalance = max(0, round($combinedBalance + $carryForwardBalance, 0));
  $combinedStatusLabel = $voucher->combinedStatusLabel();
  if ($carryForwardBalance > 0 && $combinedBalance <= 0) {
    $combinedStatusLabel = $voucher->due_date && $voucher->due_date->lt(now()->startOfDay()) ? 'Overdue' : 'Due';
  }
  $combinedStatusKey = match ($combinedStatusLabel) {
    'Paid' => 'paid',
    'Partially Paid' => 'partial',
    'Overdue' => 'overdue',
    'Cancelled' => 'cancelled',
    default => 'unpaid',
  };
  $st = $statusMap[$combinedStatusKey];
  $groupCancelled = $combinedStatusKey === 'cancelled';
  $paidPct = $total > 0 ? min(100, round(($combinedPaid / $total) * 100)) : 0;
  $balanceDue = $collectNowBalance > 0;
  $feeHeadLabel = $lineItems->map(fn($v) => $v->feeHead->name ?? $v->title)->unique()->implode(', ');
@endphp

<div class="vh-actions">
  <a href="{{ route('vouchers.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  @if(auth()->user()->isAccountant() && !$groupCancelled)
  <a href="{{ route('vouchers.edit', $voucher) }}" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
  @endif
  <a href="{{ route('vouchers.print', $voucher) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</a>
  @if($combinedPayments->count() > 0)
  <a href="{{ route('vouchers.receipt', $voucher) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-receipt"></i> Receipt</a>
  @endif
  @if(auth()->user()->isAccountant() && !$groupCancelled && $combinedBalance > 0)
  <form action="{{ route('vouchers.cancel', $voucher) }}" method="POST" onsubmit="return confirm('Cancel this voucher?')" style="margin-left:auto">
    @csrf
    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-ban"></i> Cancel Voucher</button>
  </form>
  @endif
</div>

<div class="vh-grid">
  <div>
    <div class="vh-hero">
      <div class="vh-hero-top">
        <div class="vh-hero-no"><i class="fas fa-file-invoice"></i> {{ $voucher->voucher_no }}</div>
        <span class="vh-status-pill"><i class="fas {{ $st['icon'] }}"></i> {{ $st['label'] }}</span>
      </div>

      <div class="vh-info-row">
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-user"></i> Student</span>
          <span class="vh-info-value"><a href="{{ route(auth()->user()?->isAdmin() ? 'students.show' : 'accounts.students.show', $voucher->student) }}">{{ $voucher->student->name }}</a> · {{ $voucher->student->student_id }} · Father: {{ $voucher->student->father_name ?: ($voucher->student->guardian_name ?: '—') }}</span>
        </div>
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-graduation-cap"></i> Part / Section</span>
          <span class="vh-info-value">{{ $voucher->student->schoolClass->name ?? '—' }}</span>
        </div>
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-building-columns"></i> Program</span>
          <span class="vh-info-value">{{ $voucher->student->program->name ?? '—' }}</span>
        </div>
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-calendar-days"></i> Academic Year</span>
          <span class="vh-info-value">{{ $voucher->student->academicYear->name ?? '—' }}</span>
        </div>
        @if($voucher->feeAssignment)
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-layer-group"></i> Semester / Part Running</span>
          <span class="vh-info-value">{{ $voucher->feeAssignment->termLabelFor($voucher->cycle_no) }}{{ $voucher->part_no ? ' · Part '.$voucher->part_no.' of '.$voucher->feeAssignment->parts_per_cycle : '' }}</span>
        </div>
        @endif
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-tag"></i> Installment</span>
          <span class="vh-info-value">{{ $voucher->installment_no ? "{$voucher->installment_no} of {$voucher->installment_total}" : '—' }}</span>
        </div>
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-layer-group"></i> Fee Head(s)</span>
          <span class="vh-info-value">{{ $feeHeadLabel }}</span>
        </div>
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-calendar"></i> Due Date</span>
          <span class="vh-info-value">{{ $voucher->due_date->format('M d, Y') }}</span>
        </div>
        @if($voucher->notes)
        <div class="vh-info-item">
          <span class="vh-info-label"><i class="fas fa-sticky-note"></i> Notes</span>
          <span class="vh-info-value">{{ $voucher->notes }}</span>
        </div>
        @endif
      </div>
    </div>

    <div class="vh-amount-card">
      @foreach($lineItems as $item)
      <div class="vh-amount-row">
        <span class="lbl"><i class="fas fa-coins"></i> {{ $item->feeHead->name ?? $item->title }}</span>
        <strong>Rs. {{ number_format($item->amount, 0) }}</strong>
      </div>
      @if($item->discount_amount > 0)
      <div class="vh-amount-row discount">
        <span class="lbl"><i class="fas fa-tags"></i> Discount — {{ $item->feeHead->name ?? $item->title }}</span>
        <strong>- Rs. {{ number_format($item->discount_amount, 0) }}</strong>
      </div>
      @endif
      @endforeach
      <div class="vh-divider"></div>
      <div class="vh-total-row">
        <span>Total Payable</span>
        <span>Rs. {{ number_format($total, 0) }}</span>
      </div>
      <div class="vh-paid-row">
        <span><i class="fas fa-check-circle"></i> Paid</span>
        <span>Rs. {{ number_format($combinedPaid, 0) }}</span>
      </div>

      <div class="vh-progress-wrap">
        <div class="vh-progress-track"><div class="vh-progress-fill" style="width:{{ $paidPct }}%"></div></div>
        <div class="vh-progress-labels"><span>{{ $paidPct }}% collected</span><span>{{ 100 - $paidPct }}% remaining</span></div>
      </div>

      @if($carryForwardBalance > 0)
      <div class="vh-amount-row fine" style="margin-top:10px">
        <span class="lbl"><i class="fas fa-forward"></i> Previous Balance Carried Forward</span>
        <strong>+ Rs. {{ number_format($carryForwardBalance, 0) }}</strong>
      </div>
      @endif

      <div class="vh-balance-card" style="background:{{ $balanceDue ? 'rgba(239,68,68,.08)' : 'rgba(16,185,129,.08)' }};border:1px solid {{ $balanceDue ? 'rgba(239,68,68,.25)' : 'rgba(16,185,129,.25)' }}">
        <span class="vh-balance-label">{{ $carryForwardBalance > 0 ? 'Amount to Collect Now' : 'Balance Due' }}</span>
        <span class="vh-balance-value" style="color:{{ $balanceDue ? 'var(--danger)' : 'var(--success)' }}">Rs. {{ number_format($collectNowBalance, 0) }}</span>
      </div>
    </div>
  </div>

  <div>
    @if($collectNowBalance > 0 && !$groupCancelled && auth()->user()->isAccountant())
    <div class="vh-panel">
      <div class="vh-panel-head">
        <i class="fas fa-money-bill-wave"></i>
        <span class="vh-panel-title">Record Payment</span>
      </div>
      <div class="vh-panel-body">
        <form action="{{ route('payments.store', $voucher) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="vh-form-grid">
            <div class="vh-form-group">
              <label>Amount *</label>
              <input type="number" step="1" min="1" max="{{ (int) $collectNowBalance }}" name="amount" class="form-control" required value="{{ (int) $collectNowBalance }}">
            </div>
            <div class="vh-form-group">
              <label>Method *</label>
              <select name="method" id="vhMethod" class="form-control" required>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="online">Online</option>
              </select>
            </div>
          </div>
          <div class="vh-form-group" style="margin-top:14px">
            <label>Reference No.</label>
            <input type="text" name="reference_no" class="form-control" placeholder="Optional">
          </div>
          <div class="vh-form-group" id="vhProofGroup" style="display:none;margin-top:14px">
            <label>Payment Proof <span style="text-transform:none;font-weight:400;color:var(--text-muted)">(optional)</span></label>
            <div class="proof-upload" id="vhProofDrop">
              <input type="file" name="proof" id="vhProofInput" accept="image/*,.pdf" style="display:none">
              <div class="proof-upload-inner" id="vhProofInner">
                <i class="fas fa-cloud-arrow-up"></i>
                <span>Click to attach a screenshot / receipt <small>(JPG, PNG or PDF, max 5MB)</small></span>
              </div>
              <div class="proof-preview" id="vhProofPreview">
                <img id="vhProofImg" style="display:none">
                <div class="proof-file-icon" id="vhProofFileIcon" style="display:none"><i class="fas fa-file-pdf"></i></div>
                <span class="proof-name" id="vhProofName"></span>
                <button type="button" class="proof-remove" id="vhProofRemove" title="Remove"><i class="fas fa-xmark"></i></button>
              </div>
            </div>
          </div>
          <button type="submit" class="vh-submit-btn"><i class="fas fa-check"></i> Record Payment</button>
        </form>
      </div>
    </div>
    @elseif($collectNowBalance > 0 && !$groupCancelled)
    <div class="vh-panel">
      <div class="vh-panel-body" style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px">
        <i class="fas fa-circle-info" style="font-size:20px;display:block;margin-bottom:8px;color:var(--primary)"></i>
        Payments are recorded by the Accounts department. Print this challan and send it to Accounts to collect payment.
      </div>
    </div>
    @endif

    <div class="vh-panel">
      <div class="vh-panel-head">
        <i class="fas fa-clock-rotate-left"></i>
        <span class="vh-panel-title">Payment History</span>
      </div>
      <div class="table-wrap">
        <table class="vh-history-table">
          <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Ref.</th><th>Proof</th><th></th></tr></thead>
          <tbody>
            @forelse($combinedPayments as $p)
            <tr>
              <td>{{ $p->paid_at->format('M d, Y') }}</td>
              <td><span class="vh-pay-badge"><i class="fas fa-arrow-trend-up"></i> Rs. {{ number_format($p->amount, 0) }}</span></td>
              <td><span class="vh-method-chip">{{ ucfirst(str_replace('_',' ', $p->method)) }}</span></td>
              <td>{{ $p->reference_no ?? '—' }}</td>
              <td>
                @if($p->proof_path)
                  <a href="{{ asset('storage/'.$p->proof_path) }}" target="_blank" title="View payment proof">
                    @if(Str::endsWith($p->proof_path, ['.pdf','.PDF']))
                      <span class="vh-proof-file"><i class="fas fa-file-pdf"></i></span>
                    @else
                      <img src="{{ asset('storage/'.$p->proof_path) }}" class="vh-proof-thumb">
                    @endif
                  </a>
                @else
                  <span style="color:var(--text-muted)">—</span>
                @endif
              </td>
              <td>
                @if(auth()->user()->isAccountant())
                <form action="{{ route('payments.destroy', $p) }}" method="POST" onsubmit="return confirm('Reverse this payment?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-undo"></i></button>
                </form>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="6">
              <div class="vh-empty-state">
                <i class="fas fa-receipt"></i>
                No payments recorded yet.
              </div>
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@if(auth()->user()->isAccountant() && $combinedBalance > 0 && !$groupCancelled)
<script>
(function () {
  const methodSelect = document.getElementById('vhMethod');
  const group    = document.getElementById('vhProofGroup');
  const drop     = document.getElementById('vhProofDrop');
  const input    = document.getElementById('vhProofInput');
  const inner    = document.getElementById('vhProofInner');
  const preview  = document.getElementById('vhProofPreview');
  const img      = document.getElementById('vhProofImg');
  const fileIcon = document.getElementById('vhProofFileIcon');
  const nameEl   = document.getElementById('vhProofName');
  const removeBtn = document.getElementById('vhProofRemove');
  if (!methodSelect) return;

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
})();
</script>
@endif
@endsection
