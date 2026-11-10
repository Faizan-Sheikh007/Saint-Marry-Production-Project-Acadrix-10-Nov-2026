<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Paid Fee Receipts — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root { --primary:#0d9e6e; --ink:#1a1a1a; --muted:#555; --line:#333; --hair:#ccc; }
  * { box-sizing:border-box; }
  body {
    font-family:Georgia,'Times New Roman',Times,serif; background:#e9edf2; margin:0; padding:24px; color:var(--ink);
  }
  .toolbar { max-width:640px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center; gap:10px; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar .info { font-size:13px; color:#333; }
  .toolbar .actions { display:flex; gap:10px; }
  .toolbar button, .toolbar a {
    padding:9px 18px; border-radius:7px; border:none; background:var(--primary); color:#fff;
    font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
  }
  .toolbar a.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }

  .sheet { max-width:640px; margin:0 auto 28px; background:#fff; padding:6px; box-shadow:0 1px 4px rgba(0,0,0,.12); page-break-after:always; }
  .sheet:last-child { page-break-after:auto; }
  .sheet-inner { border:2.5px solid var(--line); padding:22px 26px 24px; position:relative; }
  .sheet-inner::before { content:''; position:absolute; inset:5px; border:1px solid var(--hair); pointer-events:none; }

  .letterhead { display:flex; align-items:flex-start; gap:14px; }
  .letterhead .logo-wrap { width:64px; flex-shrink:0; display:flex; align-items:center; justify-content:center; padding-top:2px; }
  .letterhead .logo-wrap img { width:100%; height:auto; object-fit:contain; }
  .letterhead .logo-wrap .logo-fallback {
    width:60px; height:60px; border-radius:50%; border:2px solid var(--line);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px; color:var(--primary);
  }
  .letterhead .center { flex:1; text-align:center; }
  .letterhead .inst-name { font-size:21px; font-weight:700; letter-spacing:.6px; color:var(--primary); text-transform:uppercase; }
  .letterhead .inst-affil { font-size:11px; color:var(--muted); margin-top:2px; font-style:italic; }
  .letterhead .inst-address { font-size:12px; font-weight:600; margin-top:3px; }
  .letterhead .doc-name { font-size:13px; font-weight:700; letter-spacing:.4px; margin-top:8px; text-transform:uppercase; }
  .letterhead .doc-sub { font-size:11.5px; color:var(--muted); margin-top:2px; letter-spacing:.3px; }
  .letterhead .spacer { width:64px; flex-shrink:0; }

  .paid-stamp {
    position:absolute; top:26px; right:34px; border:3px solid #166534; color:#166534;
    font-weight:700; font-size:15px; text-transform:uppercase; letter-spacing:2px;
    padding:5px 14px; border-radius:6px; transform:rotate(-8deg); opacity:.85;
    font-family:'Segoe UI',Arial,sans-serif;
  }

  hr.rule { border:none; border-top:1.5px solid var(--line); margin:14px 0 12px; }

  .details-grid { display:grid; grid-template-columns:1fr 1fr; gap:5px 0; font-size:13px; margin-bottom:8px; }
  .details-grid .row { display:flex; gap:6px; padding:2px 0; }
  .details-grid .lbl { font-weight:700; white-space:nowrap; }
  .details-grid .lbl::after { content:' :'; }
  .details-grid .val { border-bottom:1px dotted var(--hair); flex:1; padding-bottom:1px; }
  .details-grid .row.full { grid-column:1 / -1; }

  table.amounts { width:100%; border-collapse:collapse; margin:14px 0 4px; font-size:13px; }
  table.amounts th, table.amounts td { border:1px solid var(--line); padding:7px 10px; }
  table.amounts th { background:#f0f0f0; font-size:10.5px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:left; }
  table.amounts td.num { text-align:right; }
  table.amounts tr.total-row td { font-weight:700; font-size:14px; background:#fafafa; }

  table.pay-history { width:100%; border-collapse:collapse; margin:6px 0 10px; font-size:11.5px; }
  table.pay-history th, table.pay-history td { border:1px solid var(--line); padding:6px 8px; }
  table.pay-history th { background:#f0f0f0; font-size:10px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:left; }
  table.pay-history td.num { text-align:right; }
  .section-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; margin:16px 0 6px; color:var(--primary); border-bottom:1px solid var(--hair); padding-bottom:3px; }

  .foot-row { display:flex; justify-content:space-between; align-items:flex-end; margin-top:30px; }
  .sign-block { text-align:center; min-width:170px; }
  .sign-line { border-top:1.3px solid var(--line); padding-top:4px; font-size:11.5px; font-weight:700; }
  .gen-date { font-size:11px; color:var(--muted); }
  .disclaimer { font-size:10px; color:var(--muted); text-align:center; margin-top:16px; border-top:1px solid var(--hair); padding-top:8px; }

  .empty-state { max-width:640px; margin:0 auto; background:#fff; padding:40px; text-align:center; color:var(--muted); font-family:'Segoe UI',Arial,sans-serif; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; margin:0; max-width:none; padding:0; }
  }
</style>
</head>
<body>
@php
  $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $campusName  = \App\Models\Setting::get('campus_name');
  $logoPath    = \App\Models\Setting::get('institution_logo');
@endphp

<div class="toolbar">
  <div class="info">
    <strong>{{ $vouchers->count() }}</strong> paid receipt{{ $vouchers->count() == 1 ? '' : 's' }}
    &middot; {{ $range === 'day' ? $from->format('d M, Y') : $from->format('d M') . ' – ' . $to->format('d M, Y') }}
  </div>
  <div class="actions">
    <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print All</button>
    <a href="javascript:history.back()" class="secondary">Back</a>
  </div>
</div>

@forelse($vouchers as $voucher)
<div class="sheet">
<div class="sheet-inner">

  @if($voucher->combinedBalance() <= 0)
    <div class="paid-stamp">Paid</div>
  @endif

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
      @if($campusName)<div class="inst-address">{{ $campusName }}</div>@endif
      <div class="doc-name">Payment Receipt</div>
      <div class="doc-sub">Accounts Department</div>
    </div>
    <div class="spacer"></div>
  </div>

  <hr class="rule">

  @php
    $lineItems     = $voucher->lineItems();
    $feeHeadLabel  = $lineItems->map(fn($v) => $v->feeHead->name ?? $v->title)->unique()->implode(', ');
    $combinedTotal = $voucher->combinedTotal();
    $combinedPaid  = $voucher->combinedPaidAmount();
  @endphp

  <div class="details-grid">
    <div class="row"><span class="lbl">Voucher No.</span><span class="val">{{ $voucher->voucher_no }}</span></div>
    <div class="row"><span class="lbl">Student</span><span class="val">{{ $voucher->student->name ?? '—' }} ({{ $voucher->student->student_id ?? '—' }})</span></div>

    <div class="row"><span class="lbl">Program</span><span class="val">{{ $voucher->student->program->name ?? '—' }}</span></div>
    <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $voucher->student->academicYear->name ?? '—' }}</span></div>

    <div class="row"><span class="lbl">Semester / Part</span><span class="val">{{ $voucher->student->schoolClass->name ?? '—' }}</span></div>
    <div class="row"><span class="lbl">Fee Head(s)</span><span class="val">{{ $feeHeadLabel }}</span></div>

    <div class="row full"><span class="lbl">Installment</span><span class="val">{{ $voucher->installment_no ? ($voucher->installment_total ? "{$voucher->installment_no} of {$voucher->installment_total}" : "Installment {$voucher->installment_no}") : '—' }}</span></div>
  </div>

  <table class="amounts">
    <thead><tr><th>Summary</th><th style="text-align:right">Amount (Rs.)</th></tr></thead>
    <tbody>
      @foreach($lineItems as $item)
      <tr><td>{{ $item->feeHead->name ?? $item->title }}</td><td class="num">{{ number_format($item->total(), 0) }}</td></tr>
      @endforeach
      <tr class="total-row"><td>Total Payable</td><td class="num">{{ number_format($combinedTotal, 0) }}</td></tr>
      <tr class="total-row"><td>Total Paid</td><td class="num">{{ number_format($combinedPaid, 0) }}</td></tr>
    </tbody>
  </table>

  <div class="section-title">Payment(s) in This Range</div>
  <table class="pay-history">
    <thead><tr><th>Date</th><th>Amount (Rs.)</th><th>Method</th><th>Reference</th></tr></thead>
    <tbody>
      @foreach($voucher->combinedPayments()->whereBetween('paid_at', [$from, $to]) as $p)
      <tr>
        <td>{{ $p->paid_at->format('M d, Y') }}</td>
        <td class="num">{{ number_format($p->amount, 0) }}</td>
        <td>{{ ucfirst(str_replace('_', ' ', $p->method)) }}</td>
        <td>{{ $p->reference_no ?? '—' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Received By (Accounts)</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Authorized Signatory</div></div>
  </div>

  <div class="disclaimer">
    Thank you. This is a computer-generated receipt issued by {{ $instName }} and does not require a signature.
  </div>

</div>
</div>
@empty
<div class="empty-state">
  No payments were recorded {{ $range === 'day' ? 'on ' . $from->format('d M, Y') : 'between ' . $from->format('d M') . ' and ' . $to->format('d M, Y') }}.
</div>
@endforelse

</body>
</html>
