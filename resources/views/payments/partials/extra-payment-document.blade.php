@php
  // Match the existing challan's structure and stylesheet, but never construct a
  // synthetic fee voucher or require a fee assignment for an extra payment.
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $tagline = \App\Models\Setting::get('institution_tagline', '');
  $logo = \App\Models\Setting::get('institution_logo');
  $logoUrl = $logo ? storage_asset($logo) : asset('logo.png');
  $currency = \App\Models\Setting::get('currency', 'PKR');
  // All extra/advance payment print URLs render the same three-copy sheet.
  $documentTitle = 'Payment Receipt & Voucher';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $documentTitle }} - {{ $receipt['number'] }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@include('vouchers.partials.modern-challan-styles')
<style>
  /* Scoped to extra-payment documents. Regular fee challans are unchanged. */
  @page { size: A4 landscape; margin: 6mm; }
  .paid-document-page {
    max-width: 1120px; margin: 0 auto 18px; width: 100%;
    break-after: auto; page-break-after: auto;
    break-inside: avoid; page-break-inside: avoid;
  }
  .paid-document-page .installment-label { font-size: 10px; margin-bottom: 4px; }
  .paid-document-page .receipts-row {
    display: flex; flex-wrap: nowrap; width: 100%; align-items: stretch;
    overflow: visible; border-radius: 6px;
    break-inside: avoid; page-break-inside: avoid;
  }
  .paid-document-page .receipt {
    flex: 1 1 33.333%; width: 33.333%; min-width: 0; padding: 7px 6px;
    break-inside: avoid; page-break-inside: avoid;
  }
  .paid-document-page .copy-tag { font-size: 9px; margin-bottom: 4px; }
  .paid-document-page .r-document-title {
    font-size: 9px; font-weight: 800; text-align: center; margin-bottom: 4px;
  }
  .paid-document-page .r-header { padding-bottom: 5px; margin-bottom: 5px; }
  .paid-document-page .r-brand img { width: 28px; height: 28px; flex-basis: 28px; }
  .paid-document-page .r-brand-name { font-size: 9px; }
  .paid-document-page .r-brand-tagline { font-size: 7.5px; }
  .paid-document-page .r-brand-voucher-no { font-size: 8px; }
  .paid-document-page .r-receipt-no { font-size: 8px; }
  .paid-document-page .r-dates { font-size: 7.7px; padding: 3px 1px; margin-bottom: 5px; flex-wrap: wrap; }
  .paid-document-page .r-dates div { flex-wrap: wrap; min-width: 0; }
  .paid-document-page .r-dates strong,
  .paid-document-page .r-long-text { overflow-wrap: anywhere; }
  .paid-document-page .r-fee-note { font-size: 7.7px; margin: 4px 0; padding: 3px 5px; }
  .paid-document-page .r-info { font-size: 7.8px; margin-bottom: 5px; }
  .paid-document-page .r-info div { padding: 1px 0; }
  .paid-document-page .r-fee-breakdown { margin-top: 5px; }
  .paid-document-page .r-total-box { font-size: 9px; }
  .paid-document-page .r-bank { font-size: 7.6px; margin-top: 5px; padding: 4px 5px; }
  .paid-document-page .r-box-title { font-size: 8.5px; }
  .paid-document-page .r-long-text { font-size: 7.6px; line-height: 1.18; }
  .paid-document-page .r-stamps { margin-top: 14px; font-size: 7.5px; }
  .paid-document-page .r-footer { font-size: 7px; margin-top: 5px; }
  .paid-document-page .r-legal-note { font-size: 7.5px; text-align: center; border-top: 1px solid #ccc; padding: 4px 0; }
  .paid-document-message { max-width: 1120px; margin: 0 auto 12px; padding: 10px; border: 1px solid #b8d9cc; border-radius: 6px; background: #f2faf6; font-size: 12px; }
  .paid-document-hint { max-width: 1120px; margin: 0 auto 12px; font-size: 12px; color: #64748b; }
  .paid-document-fit-warning { color: #92400e; }
  .voucher-toolbar { flex-wrap: wrap; justify-content: center; }
  /* This off-screen clone has the exact print width/typography, even on a phone.
     It is removed synchronously before printing and never contains extra data. */
  .paid-document-page.print-measure {
    position: fixed !important; left: 0 !important; top: 0 !important;
    width: 285mm !important; max-width: none !important; height: auto !important;
    margin: 0 !important; zoom: 1 !important; visibility: hidden !important;
    pointer-events: none; z-index: -1;
  }
  @media screen and (max-width: 680px) {
    body { padding: 12px; }
    .paid-document-page:not(.print-measure) .receipts-row { flex-direction: column; }
    .paid-document-page:not(.print-measure) .receipt { width: 100%; flex-basis: auto; }
    .paid-document-page:not(.print-measure) .receipt + .receipt { border-left: 0; border-top: 1px dashed var(--border, #e2e8f0); }
    .voucher-toolbar .btn { padding: 9px 12px; font-size: 12px; }
  }
  @media print {
    html, body { min-height: 0 !important; height: auto !important; overflow: visible !important; }
    .paid-document-message, .paid-document-hint { display: none !important; }
    .paid-document-page {
      width: 285mm; max-width: none; margin: 0 auto !important;
      zoom: var(--extra-print-scale, 1);
    }
    .paid-document-page .receipts-row { flex-direction: row; border-radius: 0; }
    .paid-document-page .receipt + .receipt { border-left: 1px dashed var(--border, #e2e8f0); border-top: 0; }
  }
</style>
</head>
<body>
<div class="voucher-toolbar">
  <button type="button" id="print-payment-sheet" class="btn btn-primary">Print All 3 Copies / Save as PDF</button>
  <a href="{{ route('payments.extra-receipts.index', $student) }}" class="btn btn-outline">Receipt History</a>
</div>
<p class="paid-document-hint">Student, bank and office copies print together on one A4 landscape page. Turn off browser headers and footers; review the scale and readability in print preview.</p>
<p class="paid-document-hint paid-document-fit-warning" id="print-fit-warning" role="status" hidden>Long content has been reduced to fit one page. Check text readability in print preview before printing.</p>
@if(session('success'))<div class="paid-document-message">{{ session('success') }}</div>@endif
@if($downloadNotice ?? null)<div class="paid-document-message">{{ $downloadNotice }}</div>@endif
<div class="installment-block paid-document-page" id="extra-payment-sheet" data-document-audience="student-bank-office">
  <div class="installment-label">{{ $documentTitle }} &middot; Extra / Advance Payment</div>
  <div class="receipts-row">
    @foreach(['student', 'bank', 'office'] as $documentAudience)
    @php
      $officeOnly = $documentAudience === 'office';
      $bankOnly = $documentAudience === 'bank';
      $studentOnly = $documentAudience === 'student';
      $copyLabel = $officeOnly ? 'Office Copy — Office Use Only' : ($bankOnly ? 'Bank Copy' : 'Student Copy');
      $copyTitle = $studentOnly ? 'Payment Receiving Receipt' : 'Paid Payment Voucher';
    @endphp
    <section class="receipt" data-copy="{{ $documentAudience }}" aria-label="{{ $copyLabel }}">
      <div class="copy-tag">{{ $copyLabel }}</div>
      <div class="r-document-title">{{ $copyTitle }}</div>
      <div class="r-header">
        <div class="r-brand">
          <img src="{{ $logoUrl }}" alt="{{ $institution }} logo">
          <div>
            <div class="r-brand-name">{{ $institution }}</div>
            @if($tagline)<div class="r-brand-tagline">{{ $tagline }}</div>@endif
            <div class="r-brand-voucher-no">{{ $receipt['number'] }}</div>
          </div>
        </div>
        <div class="r-receipt-no"><strong>PAID</strong></div>
      </div>
      <div class="r-dates">
        <div><span>Payment Date</span><strong>{{ $receipt['paid_at'] }}</strong></div>
        <div><span>Print Date</span><strong>{{ $generatedAt }}</strong></div>
      </div>
      <div class="r-fee-note">PAID &mdash; Do not pay this amount again.</div>
      <div class="r-info">
        <div><span>Student Name</span><strong dir="auto">{{ $receipt['student_name'] }}</strong></div>
        <div><span>Father Name*</span><strong dir="auto">{{ $student?->father_name ?: ($student?->guardian_name ?: '—') }}</strong></div>
        <div><span>G.R. No #</span><strong>{{ $receipt['student_id'] }}</strong></div>
        <div><span>Ledger No.*</span><strong>{{ $student?->ledger_number ?: '—' }}</strong></div>
        <div><span>Academic Year*</span><strong>{{ $student?->academicYear?->name ?? '—' }}</strong></div>
        <div><span>Program*</span><strong dir="auto">{{ $profile['program'] }}</strong></div>
        <div><span>Session*</span><strong>{{ $profile['session'] }}</strong></div>
        <div><span>Semester/Part*</span><strong dir="auto">{{ $profile['semester'] }}</strong></div>
        <div><span>Payment For</span><strong>Extra / advance payment</strong></div>
      </div>
      <div class="r-fee-breakdown">
        <div class="r-fee-breakdown-title">Fee Details</div>
        <div class="r-fee-breakdown-head"><span>Fee Head / Description</span><span>Amount</span></div>
        <div class="r-fee-breakdown-row">
          <strong>Extra / advance payment received without a fee voucher</strong>
          <strong>{{ $currency }} {{ number_format((float) $receipt['amount'], 2) }}</strong>
        </div>
      </div>
      <div class="r-total-box"><span>Amount Received</span><span>{{ $currency }} {{ number_format((float) $receipt['amount'], 2) }}</span></div>
      <div class="r-bank"><div class="r-box-title">Amount in Words</div><div class="r-long-text">{{ $receipt['words'] }}</div></div>
      <div class="r-bank">
        <div class="r-box-title">Payment Details</div>
        <div><span>Method</span><strong>{{ $receipt['method'] }}</strong></div>
        <div><span>Reference</span><strong dir="auto">{{ $receipt['reference_no'] }}</strong></div>
        <div><span>Recorded By</span><strong dir="auto">{{ $receipt['received_by'] }}</strong></div>
        <div><span>Entry Recorded</span><strong>{{ $receipt['recorded_at'] }}</strong></div>
      </div>
      @if($bankAccounts->isNotEmpty())
        <div class="r-bank institution-bank-details">
          <div class="r-box-title">Institution Bank Details &mdash; Reference Only</div>
          @foreach($bankAccounts as $bank)
            <div><span>{{ $bank->bank_name }}</span><strong>{{ $bank->account_title }} &mdash; {{ $bank->account_number }}</strong></div>
          @endforeach
        </div>
      @endif
      @if($studentOnly && filled($studentLogin ?? null))
        <div class="r-bank r-dashed-primary student-login">
          <div class="r-box-title">LMS Login (keep safe)</div>
          <div><span>Login ID</span><strong>{{ $studentLogin }}</strong></div>
        </div>
      @endif
      @if(filled(trim((string) ($declaration ?? ''))))
        <div class="r-bank r-dashed-warning institution-declaration">
          <div class="r-box-title warning">Declaration</div>
          <div class="r-long-text">{{ $declaration }}</div>
        </div>
      @endif
      <div class="r-bank r-dashed-primary">
        <div class="r-box-title">{{ $officeOnly ? 'Office Payment Record' : 'Payment Acknowledgement' }}</div>
        <div class="r-long-text">This document acknowledges only the recorded payment shown above. It does not certify that all student dues are cleared. Credit may already have been applied to fees. Reprinting does not collect money or change balances.</div>
      </div>
      @if($receipt['legacy'])
        <div class="r-bank r-dashed-warning legacy-note"><div class="r-box-title warning">Historical Payment</div><div class="r-long-text">Reproduced from the original audit entry. The payment date and method were not recorded. Entry recorded above is not a confirmed payment date.</div></div>
      @endif
      @if($receipt['notes'])
        <div class="r-bank"><div class="r-box-title">Notes</div><div class="r-long-text payment-notes" dir="auto">{{ $receipt['notes'] }}</div></div>
      @endif
      <div class="r-footer">*Current student profile; not a historical semester allocation.</div>
      <div class="r-legal-note">This is a computer-generated document and is not valid for legal proceedings.</div>
      @if($officeOnly)<div class="r-stamps"><div>Accounts Stamp &mdash; Office Use Only</div></div>@endif
      <div class="r-footer">Generated by {{ $institution }} &middot; Print Date: {{ $generatedAt }}</div>
    </section>
    @endforeach
  </div>
</div>
<script>
(function () {
  'use strict';
  const sheet = document.getElementById('extra-payment-sheet');
  const printButton = document.getElementById('print-payment-sheet');
  const warning = document.getElementById('print-fit-warning');
  if (!sheet || !printButton) return;

  function fitPaymentSheet() {
    // Use the full content, never clipping or removing notes/declarations.
    // 196mm leaves a 2mm rounding allowance within the 198mm printable height.
    const probe = sheet.cloneNode(true);
    probe.removeAttribute('id');
    probe.setAttribute('aria-hidden', 'true');
    probe.classList.add('print-measure');
    document.body.appendChild(probe);
    let scale = 1;
    try {
      const size = probe.getBoundingClientRect();
      if (size.width > 0 && size.height > 0) {
        scale = Math.min(1, (size.width / 285 * 196) / size.height);
        scale = Math.floor(scale * 10000) / 10000;
      }
    } finally {
      probe.remove();
    }
    sheet.style.setProperty('--extra-print-scale', String(scale));
    if (warning) warning.hidden = scale >= 0.8;
  }

  printButton.addEventListener('click', function () {
    fitPaymentSheet();
    window.print();
  });
  window.addEventListener('beforeprint', fitPaymentSheet);
  window.addEventListener('load', fitPaymentSheet);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitPaymentSheet);
  fitPaymentSheet();
})();
</script>
</body>
</html>
