<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt {{ $voucher->voucher_no }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root { --primary:#0d7d58; --primary-dark:#075437; --primary-tint:#e9f7f1; --ink:#1c1c1c; --muted:#666; --line:#2b2b2b; --hair:#d7d7d7; }
  * { box-sizing:border-box; }
  html, body { height:100%; }
  body {
    font-family:'Segoe UI',Georgia,'Times New Roman',Times,serif; background:#dfe4ea; margin:0; padding:22px; color:var(--ink);
  }

  .toolbar { max-width:1400px; margin:0 auto 16px; display:flex; justify-content:flex-end; gap:10px; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar button, .toolbar a {
    padding:9px 18px; border-radius:7px; border:none; background:var(--primary); color:#fff;
    font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
  }
  .toolbar a.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }

  /* ── One physical page, two copies side by side ── */
  .page {
    max-width:1400px; margin:0 auto 26px; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.14);
    border-radius:12px; overflow:hidden; position:relative;
  }
  .page-accent { height:6px; background:linear-gradient(90deg, var(--primary), var(--primary-dark)); }
  .copies { display:grid; grid-template-columns:repeat(2, 1fr); position:relative; }
  .cut-line {
    position:absolute; top:0; bottom:0; left:50%; width:0; border-left:1.5px dashed #b9b9b9;
    display:flex; align-items:center; justify-content:center; pointer-events:none;
  }
  .cut-line i { position:absolute; top:14px; background:#fff; color:#9a9a9a; font-size:12px; padding:2px 4px; transform:translateX(-50%) rotate(90deg); }

  .copy { padding:20px 24px 18px; position:relative; }
  .copy + .copy { border-left:1.5px dashed #b9b9b9; }

  .copy-badge {
    position:absolute; top:16px; right:20px; font-size:9.5px; font-weight:700; text-transform:uppercase;
    letter-spacing:.6px; color:#fff; background:var(--primary); border-radius:4px; padding:4px 11px;
  }

  .paid-stamp {
    position:absolute; top:58px; right:24px; border:3px solid #166534; color:#166534;
    font-weight:700; font-size:14px; text-transform:uppercase; letter-spacing:2px;
    padding:4px 12px; border-radius:6px; transform:rotate(-8deg); opacity:.85;
    font-family:'Segoe UI',Arial,sans-serif;
  }

  /* ── Letterhead ── */
  .letterhead { display:flex; align-items:center; gap:12px; }
  .letterhead .logo-wrap {
    width:56px; height:56px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    border-radius:50%; border:2px solid var(--primary); padding:2px; background:#fff;
  }
  .letterhead .logo-wrap img { width:100%; height:100%; object-fit:contain; border-radius:50%; }
  .letterhead .logo-wrap .logo-fallback {
    width:100%; height:100%; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:16px; color:var(--primary);
  }
  .letterhead .center { flex:1; text-align:center; min-width:0; }
  .letterhead .inst-name { font-size:19px; font-weight:800; letter-spacing:.5px; color:var(--primary); text-transform:uppercase; line-height:1.15; }
  .letterhead .inst-affil { font-size:10px; color:var(--muted); margin-top:1px; font-style:italic; }
  .letterhead .inst-address { font-size:10.5px; font-weight:600; margin-top:2px; }
  .letterhead .doc-name { font-size:11.5px; font-weight:700; letter-spacing:1px; margin-top:6px; text-transform:uppercase; color:var(--ink); }
  .letterhead .doc-sub { font-size:10.5px; color:var(--muted); margin-top:1px; letter-spacing:.2px; }
  .letterhead .spacer { width:56px; flex-shrink:0; }

  hr.rule { border:none; border-top:2px solid var(--line); margin:12px 0 10px; }

  /* ── Voucher / student details ── */
  .details-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 14px; font-size:11.5px; margin-bottom:8px; }
  .details-grid .row { display:flex; gap:6px; padding:2px 0; }
  .details-grid .lbl { font-weight:700; white-space:nowrap; color:var(--muted); }
  .details-grid .lbl::after { content:' :'; }
  .details-grid .val { border-bottom:1px dotted var(--hair); flex:1; padding-bottom:1px; font-weight:600; overflow-wrap:anywhere; }
  .details-grid .row.full { grid-column:1 / -1; }

  .section-label {
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; margin:11px 0 5px; color:#fff;
    background:var(--primary); display:inline-block; padding:3px 9px; border-radius:3px 3px 0 0;
  }

  table.amounts, table.pay-history { width:100%; border-collapse:collapse; margin:0 0 4px; font-size:10.8px; }
  table.amounts th, table.amounts td, table.pay-history th, table.pay-history td { border:1px solid var(--hair); padding:5px 8px; }
  table.amounts th, table.pay-history th {
    background:#f4f4f6; font-size:9.5px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:left; color:var(--ink);
  }
  table.amounts td.num, table.pay-history td.num { text-align:right; }
  table.amounts tbody tr:nth-child(even), table.pay-history tbody tr:nth-child(even) { background:#fafafa; }

  table.amounts tr.total-row td { font-weight:800; font-size:11.5px; background:var(--primary-tint); color:var(--primary-dark); }
  table.amounts tr.paid-row td { color:#166534; font-weight:700; }
  table.amounts tr.balance-row td { color:#991b1b; font-weight:700; }

  .foot-row { display:flex; justify-content:space-between; align-items:flex-end; margin-top:20px; }
  .sign-block { text-align:center; min-width:140px; }
  .sign-line { border-top:1.3px solid var(--line); padding-top:3px; font-size:10px; font-weight:700; }
  .gen-date { font-size:9.5px; color:var(--muted); text-align:center; }

  .disclaimer { font-size:8.7px; color:var(--muted); text-align:center; margin-top:12px; border-top:1px solid var(--hair); padding-top:6px; }

  /* Phone screen layout only; never stack the two copies in print. */
  @media screen and (max-width: 900px) {
    .copies { grid-template-columns:1fr; }
    .cut-line { display:none; }
    .copy + .copy { border-left:0; border-top:1.5px dashed #b9b9b9; }
  }

  @page { size: A4 landscape; margin:6mm; }

  @media print {
    html, body { background:#fff; }
    body { padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .toolbar { display:none; }
    .page { box-shadow:none; margin:0; max-width:none; border-radius:0; }
    /* Keep Student and Office copies side by side in A4 landscape. */
    .copies { grid-template-columns:repeat(2, 1fr) !important; }
    .cut-line { display:flex !important; }
    .copy + .copy { border-left:1.5px dashed #b9b9b9 !important; border-top:none !important; }
    .copy { padding:10px 14px 8px; }
  }
</style>
</head>
<body>
@php
  $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $logoPath    = \App\Models\Setting::get('institution_logo');

  // Receipts and the Accounts history must agree on the TOTAL verified amount
  // applied to this voucher number (across every sibling fee-head row).
  // A flash value for just the latest payment is not the voucher total.
  $combinedBalance  = $voucher->combinedBalance();
  $voucherAmount    = $voucher->combinedTotal();
  $receiptPaidAmount = \App\Services\VoucherPaymentHistory::verifiedAmountForVoucher($voucher);

  // Clearly identify the installment this receipt belongs to without
  // reintroducing the underlying fee-head breakdown.
  $receiptInstallmentNo = (int) ($voucher->part_no ?: $voucher->installment_no ?: 0);
  $receiptInstallmentTotal = (int) ($voucher->feeAssignment?->parts_per_cycle ?: $voucher->installment_total ?: 0);
  $receiptInstallmentLabel = $receiptInstallmentNo > 0
      ? ($receiptInstallmentTotal > 0
          ? "{$receiptInstallmentNo} of {$receiptInstallmentTotal}"
          : "Installment {$receiptInstallmentNo}")
      : '—';
  $copyLabels = ['Student Copy', 'Office Copy'];

@endphp

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="page">
  <div class="page-accent"></div>
  <div class="copies">
    <div class="cut-line"><i class="fas fa-scissors"></i></div>
    @foreach($copyLabels as $copyLabel)
    <div class="copy">

      <div class="copy-badge">{{ $copyLabel }}</div>

      @if($combinedBalance <= 0)
        <div class="paid-stamp">Paid</div>
      @endif

      {{-- Letterhead --}}
      <div class="letterhead">
        <div class="logo-wrap">
          @if($logoPath)
            <img src="{{ storage_asset($logoPath) }}" alt="Logo">
          @else
            <div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $instName), 0, 2)) ?: 'IN' }}</div>
          @endif
        </div>
        <div class="center">
          <div class="inst-name">{{ $instName }}</div>
          @if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif
          <div class="doc-name">Payment Receipt</div>
          <div class="doc-sub">Accounts Department</div>
        </div>
        <div class="spacer"></div>
      </div>

      <hr class="rule">

      {{-- Voucher / student details --}}
      <div class="details-grid">
        <div class="row"><span class="lbl">Voucher No.</span><span class="val">{{ $voucher->voucher_no }}</span></div>
        <div class="row"><span class="lbl">Student</span><span class="val">{{ $voucher->student->name ?? '—' }} ({{ $voucher->student->student_id ?? '—' }})</span></div>
        <div class="row"><span class="lbl">Father Name</span><span class="val">{{ $voucher->student?->father_name ?: ($voucher->student?->guardian_name ?: '—') }}</span></div>

        <div class="row"><span class="lbl">Program</span><span class="val">{{ $voucher->student->program->name ?? '—' }}</span></div>
        <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $voucher->student->academicYear->name ?? '—' }}</span></div>

        <div class="row full"><span class="lbl">Semester / Part</span><span class="val">{{ $voucher->student->schoolClass->name ?? '—' }}</span></div>
        <div class="row full"><span class="lbl">Installment</span><span class="val">{{ $receiptInstallmentLabel }}</span></div>
      </div>

      {{-- The voucher total is a fee charge, not a receipt. Only verified
           payments and earlier credit allocations count towards the total paid. --}}
      <div class="section-label">Payment Received / Applied</div>
      <table class="amounts">
        <thead><tr><th>Particulars</th><th class="num">Amount (Rs.)</th></tr></thead>
        <tbody>
          <tr><td>Voucher Amount (Fee Payable)</td><td class="num">{{ number_format($voucherAmount, 2) }}</td></tr>
          <tr class="total-row"><td>Total Paid / Credited to Voucher</td><td class="num">{{ number_format($receiptPaidAmount, 2) }}</td></tr>
        </tbody>
      </table>

      <div style="font-size:9px;color:var(--muted);margin-top:5px">Total paid includes previously received balance credit applied to this voucher. This is a cumulative voucher receipt, not confirmation of a new cash payment.</div>

      <div class="foot-row">
        <div class="sign-block"><div class="sign-line">Received By (Accounts)</div></div>
        <div class="gen-date">Print Date<br>{{ now()->format('d M Y, h:i A') }}</div>
        <div class="sign-block"><div class="sign-line">Authorized Signatory</div></div>
      </div>

      <div class="disclaimer">
        Thank you. This is a computer-generated receipt issued by {{ $instName }} and does not require a signature.
      </div>

    </div>
    @endforeach
  </div>
</div>

</body>
</html>
