<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment Receiving Receipt {{ $receipt->receipt_number }} — 2 Copies</title>
  <style>
    :root { color-scheme:light; }
    * { box-sizing:border-box; }
    body { margin:0; padding:22px 16px; font:12px/1.4 Arial, Helvetica, sans-serif; color:#1e293b; background:#eef2f6; }
    .actions { display:flex; gap:10px; max-width:1200px; margin:0 auto 16px; justify-content:flex-end; }
    .actions a,.actions button { cursor:pointer; font:inherit; font-weight:700; border-radius:8px; padding:9px 16px; text-decoration:none; background:#6b1c74; color:#fff; border:0; }
    .actions a { background:#fff; color:#6b1c74; }
    .print-hint { max-width:1200px; margin:0 auto 12px; font-size:12px; color:#64748b; }
    .paper-sheet { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); max-width:1200px; margin:0 auto; background:#fff; border-radius:9px; box-shadow:0 12px 35px rgba(15,23,42,.08); }
    .paper { min-width:0; padding:20px 22px; break-inside:avoid; page-break-inside:avoid; }
    .paper + .paper { border-left:1.5px dashed #aeb8c5; }
    .copy-label { font-size:11px; font-weight:800; color:#6b1c74; text-transform:uppercase; text-align:center; letter-spacing:.08em; margin-bottom:10px; }
    .head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; border-bottom:2px solid #6b1c74; padding-bottom:10px; }
    .school { font-size:15px; font-weight:800; color:#581565; overflow-wrap:anywhere; }
    .eyebrow { color:#64748b; font-size:9px; text-transform:uppercase; letter-spacing:.06em; font-weight:700; }
    .number { font-size:12px; font-weight:800; overflow-wrap:anywhere; text-align:right; }
    .receipt-number { flex:0 0 120px; }
    .paid-status { margin:9px 0 0; text-align:center; padding:4px 6px; color:#155e40; border:1px solid #9cd3b6; background:#edf9f1; border-radius:5px; font-weight:800; font-size:10px; }
    .amount { margin:12px 0; padding:10px 12px; border-radius:7px; background:#f5f0f7; }
    .amount strong { font-size:22px; display:block; color:#581565; }
    .amount div:last-child { font-size:10px; }
    .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 15px; margin:12px 0 15px; }
    .field { min-width:0; overflow-wrap:anywhere; }
    .field small { display:block; font-size:9px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:2px; }
    .field strong { font-size:11px; }
    h2 { font-size:12px; margin:12px 0 6px; }
    table { border-collapse:collapse; width:100%; font-size:10px; table-layout:fixed; }
    th { background:#f1f5f9; text-align:left; font-size:9px; text-transform:uppercase; color:#475569; }
    td,th { padding:6px 7px; border-bottom:1px solid #e2e8f0; overflow-wrap:anywhere; }
    .money { text-align:right; white-space:nowrap; }
    .total { font-weight:800; background:#faf7fb; }
    .note { margin-top:13px; padding-top:9px; border-top:1px solid #e2e8f0; font-size:9px; color:#64748b; }
    .printed-at { font-size:9px; text-align:right; margin-top:8px; color:#64748b; }
    @media screen and (max-width:760px) {
      .paper-sheet { grid-template-columns:1fr; }
      .paper + .paper { border-left:0; border-top:1.5px dashed #aeb8c5; }
      .head { flex-wrap:wrap; }
    }
    @page { size:A4 landscape; margin:6mm; }
    @media print {
      html, body { width:100%; height:auto; overflow:visible; }
      body { padding:0; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .actions, .print-hint { display:none; }
      .paper-sheet { display:grid !important; grid-template-columns:repeat(2,minmax(0,1fr)) !important; width:100%; max-width:none; margin:0; border-radius:0; box-shadow:none; }
      .paper { padding:8px 12px; }
      .paper + .paper { border-left:1.5px dashed #aeb8c5; border-top:0; }
    }
  </style>
</head>
<body>
  <div class="actions">
    <a href="{{ route('accounts.students.show', $student) }}#received-payments">Back to Student Payments</a>
    <button type="button" onclick="window.print()">Print 2 Copies / Save as PDF</button>
  </div>
  <p class="print-hint">Paid payment-receiving receipts print as two copies: Student Copy and Office Copy. Use A4 landscape with browser headers/footers disabled.</p>
  @php
    // One recorded collection can have several voucher allocations. Reprint
    // the original amount on EACH copy; never treat allocations as new money.
    $voucherBreakdown = $receipt->allocations
        ->groupBy(fn ($portion) => $portion->voucher?->voucher_no ?: '—')
        ->map(fn ($rows) => round((float) $rows->sum('amount'), 2));
    $printedAt = now()->format('d M Y, h:i A');
  @endphp
  <main class="paper-sheet" data-document-audience="student-office" aria-label="Paid payment-receiving receipt — two copies">
    @foreach(['Student Copy', 'Office Copy'] as $copyLabel)
    <section class="paper" data-copy="{{ $copyLabel }}">
      <div class="copy-label">{{ $copyLabel }}</div>
      <header class="head">
        <div>
          <div class="school">{{ $institutionName }}</div>
          <div class="eyebrow">Payment Receiving Receipt</div>
        </div>
        <div class="receipt-number">
          <div class="eyebrow">Receipt Number</div>
          <div class="number">{{ $receipt->receipt_number }}</div>
        </div>
      </header>
      <div class="paid-status">PAID — Payment Received. Do not pay this amount again.</div>
      <section class="amount">
        <div class="eyebrow">Amount received in this collection</div>
        <strong>Rs. {{ number_format($receipt->amount, 2) }}</strong>
        <div>This is one payment received, not the cumulative amount paid on a voucher.</div>
      </section>
      <section class="grid">
        <div class="field"><small>Student</small><strong>{{ $student->name }} ({{ $student->student_id }})</strong></div>
        <div class="field"><small>Program / Semester</small><strong>{{ $student->program?->name ?? '—' }} / {{ $student->schoolClass?->section_label ?? '—' }}</strong></div>
        <div class="field"><small>Date Received</small><strong>{{ $receipt->paid_at?->format('d M Y') ?? 'Not recorded' }}</strong></div>
        <div class="field"><small>Recorded On</small><strong>{{ $receipt->recorded_at?->format('d M Y h:i A') ?? 'Not recorded' }}</strong></div>
        <div class="field"><small>Payment Method</small><strong>{{ ucwords(str_replace('_', ' ', $receipt->method ?? 'Not recorded')) }}</strong></div>
        <div class="field"><small>Reference Number</small><strong>{{ $receipt->reference_no ?: '—' }}</strong></div>
        <div class="field"><small>Received By</small><strong>{{ $receipt->received_by ?: '—' }}</strong></div>
        <div class="field"><small>Notes</small><strong>{{ $receipt->notes ?: '—' }}</strong></div>
      </section>
      <h2>Applied to vouchers</h2>
      <table>
        <thead><tr><th>Voucher Number</th><th class="money">Portion Applied</th></tr></thead>
        <tbody>
          @foreach($voucherBreakdown as $voucherNumber => $portion)
            <tr><td>{{ $voucherNumber }}</td><td class="money">Rs. {{ number_format($portion, 2) }}</td></tr>
          @endforeach
          <tr class="total"><td>Total Money Received</td><td class="money">Rs. {{ number_format($receipt->amount, 2) }}</td></tr>
        </tbody>
      </table>
      <div class="note">Read-only reprint of a recorded payment. Split voucher allocations are for reconciliation only, not additional receipts or charges. This document does not certify that all student dues are cleared. Historical payments without a collection identifier have individual allocation receipts.</div>
      <div class="printed-at">Print Date: {{ $printedAt }}</div>
    </section>
    @endforeach
  </main>
</body>
</html>
