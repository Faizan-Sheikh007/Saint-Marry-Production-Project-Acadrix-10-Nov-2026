<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Installment Plan - {{ $feeAssignment->student->name }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@php
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $tagline     = \App\Models\Setting::get('institution_tagline', '');
  $logo        = \App\Models\Setting::get('institution_logo');
  $logoUrl     = $logo ? storage_asset($logo) : asset('logo.png');
  $currency    = \App\Models\Setting::get('currency', 'Rs.');
  $taxPercent  = (float) \App\Models\Setting::get('tax_percent', 0);
  // The same printable view is used by Accounts, Student, and Guardian routes.
  // Use configured defaults even when a controller does not supply these variables.
  $feeNote     = $feeNote ?? \App\Models\Setting::get('fee_summary_note');
  $declaration = $declaration ?? \App\Models\Setting::get('fee_summary_declaration');
  $student     = $feeAssignment->student;
  $copies      = ['Student Copy', 'Bank Copy', 'Office Copy'];

  // Only dues carried over from EARLIER semesters/years (a lower cycle_end
  // than this plan's own cycle_start) are shown here — not the rest of the
  // student's whole fee plan for semesters/years that haven't happened yet.
  // A brand-new student with nothing overdue simply sees no line at all.
  $otherDues = $student->vouchers()
      ->where('fee_assignment_id', '!=', $feeAssignment->id)
      ->whereIn('status', ['unpaid', 'partial', 'overdue'])
      ->whereHas('feeAssignment', function ($q) use ($feeAssignment) {
          $q->where('cycle_end', '<', $feeAssignment->cycle_start);
      })
      ->get()->sum(fn ($v) => $v->balance());
@endphp
<style>
  @page { size: A4 landscape; margin: 6mm; }
  * { box-sizing: border-box; }
  body { font-family: 'Outfit', 'Segoe UI', Arial, sans-serif; background:#f1f5f9; padding:18px; color:var(--text); }
  .toolbar { max-width:1500px; margin:0 auto 16px; display:flex; justify-content:flex-end; gap:10px; }
  .installment-block { max-width:1500px; margin:0 auto 28px; page-break-after:always; break-after:page; }
  .installment-block:last-child { page-break-after:auto; break-after:auto; }
  .installment-label { font-size:13px; font-weight:800; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:.03em; }
  .receipts-row { display:flex; gap:0; align-items:stretch; background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
  .receipt { flex:1 1 0; min-width:0; padding:12px 11px; position:relative; }
  .receipt + .receipt { border-left:1px dashed var(--border); }
  .copy-tag { text-align:center; font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--primary); margin-bottom:7px; }
  .r-header { display:flex; justify-content:space-between; align-items:flex-start; gap:6px; border-bottom:2px solid var(--primary); padding-bottom:7px; margin-bottom:7px; }
  .r-brand { display:flex; gap:7px; align-items:center; min-width:0; }
  .r-brand img { width:32px; height:32px; flex:0 0 32px; border-radius:50%; object-fit:cover; }
  .r-brand > div { min-width:0; }
  .r-brand-name { font-size:10.5px; font-weight:800; line-height:1.15; overflow-wrap:anywhere; }
  .r-brand-tagline { font-size:8px; color:var(--text-muted); overflow-wrap:anywhere; }
  .r-brand-voucher-no { font-size:9px; font-weight:700; color:var(--primary); margin-top:2px; overflow-wrap:anywhere; }
  .r-receipt-no { flex:0 0 auto; text-align:right; font-size:8.5px; }
  .r-receipt-no strong { font-size:10px; color:var(--primary); display:block; }
  .r-qr { width:40px; height:40px; margin-left:auto; margin-top:4px; }
  .r-info { font-size:9px; margin-bottom:7px; }
  .r-dates {
    display:flex; justify-content:space-between; gap:10px; font-size:9px; font-weight:700;
    border-top:1px solid var(--border); border-bottom:1px solid var(--border);
    padding:4px 1px; margin-bottom:7px;
  }
  .r-dates div { display:flex; gap:5px; align-items:baseline; }
  .r-dates span { color:var(--text-muted); font-weight:600; }
  .r-dates strong { color:var(--primary); }
  .r-info div { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; padding:1.5px 0; }
  .r-info span:first-child { color:var(--text-muted); flex:0 0 38%; }
  .r-info strong { flex:1; min-width:0; text-align:right; line-height:1.2; overflow-wrap:anywhere; }
  .r-fee-breakdown {
    margin-top: 7px;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
  }
  .r-fee-breakdown-title {
    padding: 4px 6px;
    background: var(--bg, #f0f2f5);
    color: var(--primary, #890c25);
    font-size: 8.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
  }
  .r-fee-breakdown-head, .r-fee-breakdown-row {
    display: grid;
    grid-template-columns: 1fr .7fr;
    gap: 5px;
    align-items: start;
    padding: 3px 6px;
  }
  .r-fee-breakdown-head {
    background: rgba(139,12,34,.045);
    border-top: 1px solid var(--border, #e2e8f0);
    border-bottom: 1px solid var(--border, #e2e8f0);
    color: var(--text-muted, #64748b);
    font-size: 7.6px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
  }
  .r-fee-breakdown-row {
    font-size: 8px;
    line-height: 1.18;
    border-bottom: 1px solid var(--border, #e2e8f0);
  }
  .r-fee-breakdown-row:last-child { border-bottom: 0; }
  .r-fee-breakdown-row > * { min-width: 0; overflow-wrap: anywhere; }
  .r-fee-breakdown-row > span { color: var(--text-muted, #64748b); }
  .r-fee-breakdown-row > :last-child, .r-fee-breakdown-head > :last-child { text-align: right; white-space: nowrap; }

  .r-total-box { background:var(--bg); border-radius:6px; padding:5px 7px; margin-top:4px; font-size:9.5px; font-weight:800; display:flex; justify-content:space-between; gap:8px; }
  .r-total-box span:last-child { white-space:nowrap; }
  .r-bank { font-size:8.7px; margin-top:7px; background:var(--bg); border-radius:6px; padding:5px 7px; }
  .r-bank div { display:flex; justify-content:space-between; align-items:flex-start; gap:6px; padding:1px 0; }
  .r-bank span, .r-bank strong { min-width:0; overflow-wrap:anywhere; }
  .r-bank strong { text-align:right; }
  .r-fee-note {
    font-size:9px; font-weight:800; text-align:center; color:var(--primary);
    background:rgba(139,12,34,.06); border:1px solid rgba(139,12,34,.25); border-radius:6px;
    padding:4px 6px; margin-top:7px; letter-spacing:.1px;
  }
  .r-stamps { display:flex; justify-content:center; margin-top:18px; font-size:8.5px; color:var(--text-muted); }
  .r-stamps div { border-top:1px solid var(--text); width:50%; text-align:center; padding-top:3px; }
  .r-footer { text-align:center; font-size:7.5px; color:var(--text-muted); margin-top:7px; }

  @media (max-width: 980px) {
    .receipts-row { overflow-x:auto; }
    .receipt { flex:0 0 360px; }
  }

  @media print {
    html, body { width:100%; }
    body { background:#fff; padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .toolbar { display:none !important; }
    .installment-block { max-width:none; width:100%; margin:0; }
    .installment-label { font-size:10px; margin-bottom:4px; }
    .receipts-row { width:100%; border-radius:0; overflow:visible; }
    .receipt { flex:1 1 33.333%; padding:7px 8px; }
    .copy-tag { margin-bottom:5px; }
    .r-header { padding-bottom:5px; margin-bottom:5px; }
    .r-brand img { width:28px; height:28px; flex-basis:28px; }
    .r-brand-name { font-size:9.5px; }
    .r-brand-tagline { font-size:7.2px; }
    .r-brand-voucher-no { font-size:8.2px; }
    .r-receipt-no { font-size:7.8px; }
    .r-qr { width:36px; height:36px; }
    .r-info { font-size:8px; }
    .r-dates { font-size:8px; padding:3px 1px; margin-bottom:5px; }
    .r-fee-breakdown { margin-top:5px; }
    .r-fee-breakdown-title { font-size:7.5px; padding:3px 5px; }
    .r-fee-breakdown-head, .r-fee-breakdown-row { padding:2.5px 5px; gap:4px; }
    .r-fee-breakdown-head { font-size:6.7px; }
    .r-fee-breakdown-row { font-size:7.2px; }
    .r-total-box { font-size:8.5px; }
    .r-bank { font-size:7.7px; margin-top:5px; padding:4px 5px; }
    .r-fee-note { font-size:8px; margin-top:5px; padding:3px 5px; }
    .r-stamps { margin-top:14px; font-size:7.5px; }
    .r-footer { font-size:6.8px; margin-top:5px; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Download</button>
</div>

@foreach($groups as $no => $vouchers)
@php
  $due          = $vouchers->first()->due_date;
  $issued       = $vouchers->first()->issued_at;
  $month        = $vouchers->first()->month;
  $partNo       = $vouchers->first()->part_no ?? $vouchers->first()->installment_no;
  $splitSequence = $vouchers->first()->split_sequence;
  $qrKey        = \Illuminate\Support\Str::slug($vouchers->first()->voucher_no);

  // Authoritative figures — the exact same per-voucher amount and discount fields used everywhere else in the app
  // (Vouchers & Installments page, single voucher print, balances, etc.),
  // instead of re-deriving discount/financial-aid from the plan's generic
  // percentages. Re-splitting a semester or editing a voucher's discount
  // directly used to make this challan disagree with those other pages;
  // pulling from the same stored fields keeps every view in sync.
  $currentDues    = $vouchers->sum('amount');
  $discount       = $vouchers->sum('discount_amount');
  $tax            = round(($currentDues - $discount) * $taxPercent / 100, 2);
  $payByDue       = round($vouchers->sum(fn ($v) => $v->total()) + $tax, 2); // amount - discount + tax
  $validityDate = $due->copy()->subDays(2);
@endphp
<div class="installment-block">
  <div class="installment-label">
    {{ $feeAssignment->installmentLabelFor($vouchers->first()->cycle_no ?? $feeAssignment->cycle_start, $partNo) }} &middot; {{ $month }}
    @if($splitSequence)
      &middot; Split Challan {{ $splitSequence }}
    @endif
  </div>
  <div class="receipts-row">
    @foreach($copies as $copy)
    <div class="receipt">
      <div class="copy-tag">{{ $copy }}</div>
      <div class="r-header">
        <div class="r-brand">
          <img src="{{ $logoUrl }}" alt="">
          <div>
            <div class="r-brand-name">{{ $institution }}</div>
            @if($tagline)<div class="r-brand-tagline">{{ $tagline }}</div>@endif
            <div class="r-brand-voucher-no">{{ $vouchers->first()->voucher_no }}</div>
          </div>
        </div>
        <div class="r-receipt-no">
          {{ $issued?->format('M Y') }}
          <div id="qr-{{ $feeAssignment->id }}-{{ $qrKey }}-{{ \Illuminate\Support\Str::slug($copy) }}" class="r-qr"></div>
        </div>
      </div>

      <div class="r-dates">
        <div><span>Issue Date</span><strong>{{ $issued?->format('d/m/Y') }}</strong></div>
        <div><span>Due Date</span><strong>{{ $due->format('d/m/Y') }}</strong></div>
        <div><span>Print Date</span><strong>{{ now()->format('d/m/Y h:i A') }}</strong></div>
      </div>

      @if($feeNote)
      <div class="r-fee-note">{{ $feeNote }}</div>
      @endif

      <div class="r-info">
        <div><span>Student Name</span><strong>{{ $student->name }}</strong></div>
        <div><span>Father Name</span><strong>{{ $student->father_name ?: ($student->guardian_name ?: '—') }}</strong></div>
        <div><span>G.R. No #</span><strong>{{ $student->student_id }}</strong></div>
        <div><span>Academic Year</span><strong>{{ $student->academicYear->name ?? '—' }}</strong></div>
        <div><span>Program</span><strong>{{ $student->program->name ?? '—' }}</strong></div>
        <div><span>Semester/Part</span><strong>{{ $student->schoolClass->name ?? '—' }}{{ $student->section ? ' ('.$student->section.')' : '' }}</strong></div>
        <div><span>This Installment Is For</span><strong>{{ $feeAssignment->installmentLabelFor($vouchers->first()->cycle_no ?? $feeAssignment->cycle_start, $partNo) }}{{ $splitSequence ? ' · Split Challan '.$splitSequence : '' }}</strong></div>
        <div><span>Validity Date</span><strong>{{ $validityDate->format('d/m/Y') }}</strong></div>
      </div>

      <div class="r-fee-breakdown">
        <div class="r-fee-breakdown-title">Fee Details</div>
        <div class="r-fee-breakdown-head">
          <span>Fee Head</span>
          <span>Amount</span>
        </div>
        @foreach($vouchers as $feeVoucher)
          <div class="r-fee-breakdown-row">
            <strong>{{ $feeVoucher->feeHead->name ?? $feeVoucher->title ?? '—' }}</strong>
            <strong>{{ $currency }} {{ number_format((float) $feeVoucher->total(), 0) }}</strong>
          </div>
        @endforeach
      </div>

      <div class="r-total-box"><span>Payable Amount</span><span>{{ $currency }} {{ number_format($payByDue, 0) }}</span></div>

      @if($bankAccounts->isNotEmpty())
      <div class="r-bank">
        @foreach($bankAccounts as $bank)
        <div><span>{{ $bank->bank_name }}</span><strong>{{ $bank->account_title }} — {{ $bank->account_number }}</strong></div>
        @if($bank->branch_name)<div><span></span><span style="color:var(--text-muted)">{{ $bank->branch_name }}</span></div>@endif
        @endforeach
      </div>
      @endif

      @if($feeAssignment->lms_login_email)
      <div class="r-bank" style="border:1px dashed var(--primary)">
        <div style="font-size:9px;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">LMS Login (keep safe)</div>
        <div><span>Login ID</span><strong>{{ $feeAssignment->lms_login_email }}</strong></div>
        @if($feeAssignment->lms_login_password_plain)
        <div><span>Password</span><strong>{{ $feeAssignment->lms_login_password_plain }}</strong></div>
        @endif
      </div>
      @endif

      @php $programDisclaimer = $feeAssignment->student->program->effective_disclaimer ?? null; @endphp
      @if($programDisclaimer)
      <div class="r-bank" style="border:1px dashed var(--warning)">
        <div style="font-size:9px;font-weight:800;color:var(--warning);text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">
          Disclaimer — {{ $feeAssignment->student->program->institution->name ?? $feeAssignment->student->program->name }}
        </div>
        <div style="font-size:9.5px;color:var(--text-muted);white-space:pre-line">{{ $programDisclaimer }}</div>
      </div>
      @endif

      <div style="font-size:10px;text-align:center;padding:8px;border-top:1px solid #ddd">This is computer generated documents does not use in legally in court.</div>
      @if($declaration)
      <div class="r-bank" style="border:1px dashed var(--primary)">
        <div style="font-size:9px;font-weight:800;color:var(--primary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">Declaration</div>
        <div style="font-size:9.5px;color:var(--text-muted);white-space:pre-line">{{ $declaration }}</div>
      </div>
      @endif

      <div class="r-stamps">
        <div>Bank Stamp</div>
      </div>

      <div class="r-footer">Scan the QR to verify this installment &middot; Generated by {{ $institution }} &middot; Print Date: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
    @endforeach
  </div>
</div>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
@foreach($groups as $no => $vouchers)
  @php $qrKey = \Illuminate\Support\Str::slug($vouchers->first()->voucher_no); @endphp
  @foreach($copies as $copy)
  new QRCode(document.getElementById('qr-{{ $feeAssignment->id }}-{{ $qrKey }}-{{ \Illuminate\Support\Str::slug($copy) }}'), {
    text: @json($qrUrl),
    width: 44, height: 44,
    colorDark: '#111111', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
  @endforeach
@endforeach
</script>
</body>
</html>
