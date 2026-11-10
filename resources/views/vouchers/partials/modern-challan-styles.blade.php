<style>
  @page { size: A4 landscape; margin: 6mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; }
  body {
    font-family: 'Outfit', 'Segoe UI', Arial, sans-serif;
    background: #f1f5f9;
    padding: 18px;
    color: var(--text, #1e293b);
  }

  .voucher-toolbar {
    max-width: 1500px;
    margin: 0 auto 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }
  .voucher-toolbar .btn {
    border: 0;
    border-radius: 8px;
    padding: 9px 18px;
    font: 700 13px/1 'Outfit', 'Segoe UI', Arial, sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
  }
  .voucher-toolbar .btn-primary { background: var(--primary, #890c25); color: #fff; }
  .voucher-toolbar .btn-outline { background: #fff; color: var(--primary, #890c25); border: 1px solid var(--primary, #890c25); }

  .installment-block {
    max-width: 1500px;
    margin: 0 auto 28px;
    page-break-after: always;
    break-after: page;
  }
  .installment-block:last-child { page-break-after: auto; break-after: auto; }
  .installment-label {
    font-size: 13px;
    font-weight: 800;
    color: var(--text-muted, #64748b);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .03em;
  }

  .receipts-row {
    display: flex;
    gap: 0;
    align-items: stretch;
    background: #fff;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    overflow: hidden;
  }
  .receipt {
    flex: 1 1 0;
    min-width: 0;
    padding: 12px 11px;
    position: relative;
  }
  .receipt + .receipt { border-left: 1px dashed var(--border, #e2e8f0); }

  .copy-tag {
    text-align: center;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--primary, #890c25);
    margin-bottom: 7px;
  }

  .r-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
    border-bottom: 2px solid var(--primary, #890c25);
    padding-bottom: 7px;
    margin-bottom: 7px;
  }
  .r-brand { display: flex; gap: 7px; align-items: center; min-width: 0; }
  .r-brand img {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border-radius: 50%;
    object-fit: contain;
  }
  .r-brand > div { min-width: 0; }
  .r-brand-name {
    font-size: 10.5px;
    font-weight: 800;
    line-height: 1.15;
    overflow-wrap: anywhere;
  }
  .r-brand-tagline {
    font-size: 8px;
    color: var(--text-muted, #64748b);
    overflow-wrap: anywhere;
  }
  .r-brand-voucher-no {
    font-size: 9px;
    font-weight: 700;
    color: var(--primary, #890c25);
    margin-top: 2px;
    overflow-wrap: anywhere;
  }
  .r-receipt-no {
    flex: 0 0 auto;
    min-width: 44px;
    text-align: right;
    font-size: 8.5px;
    color: var(--text-muted, #64748b);
  }
  .r-qr { width: 40px; height: 40px; margin-left: auto; margin-top: 4px; }
  .r-qr img, .r-qr canvas { width: 40px !important; height: 40px !important; display: block; }

  .r-dates {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 9px;
    font-weight: 700;
    border-top: 1px solid var(--border, #e2e8f0);
    border-bottom: 1px solid var(--border, #e2e8f0);
    padding: 4px 1px;
    margin-bottom: 7px;
  }
  .r-dates div { display: flex; gap: 5px; align-items: baseline; }
  .r-dates span { color: var(--text-muted, #64748b); font-weight: 600; }
  .r-dates strong { color: var(--primary, #890c25); }

  /* Static, high-visibility policy reminder; no effect on amounts or logins. */
  .r-late-fine-notice {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 2px;
    margin: 7px 0;
    padding: 6px 7px;
    background: #fff0f1;
    border: 1.5px solid #a5142b;
    border-radius: 6px;
    color: #7e1022;
    font-size: 9px;
    line-height: 1.25;
    page-break-inside: avoid;
  }
  .r-late-fine-notice strong {
    font-size: 9.5px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }
  .r-late-fine-notice span { font-weight: 700; }

  .r-fee-note {
    font-size: 9px;
    font-weight: 800;
    text-align: center;
    color: var(--primary, #890c25);
    background: rgba(139, 12, 34, .06);
    border: 1px solid rgba(139, 12, 34, .25);
    border-radius: 6px;
    padding: 4px 6px;
    margin: 7px 0;
    letter-spacing: .1px;
  }

  .r-info { font-size: 9px; margin-bottom: 7px; }
  .r-info div {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    padding: 1.5px 0;
  }
  .r-info span:first-child {
    color: var(--text-muted, #64748b);
    flex: 0 0 38%;
  }
  .r-info strong {
    flex: 1;
    min-width: 0;
    text-align: right;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }

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

  .r-total-box {
    background: var(--bg, #f0f2f5);
    border-radius: 6px;
    padding: 5px 7px;
    margin-top: 4px;
    font-size: 9.5px;
    font-weight: 800;
    display: flex;
    justify-content: space-between;
    gap: 8px;
  }
  .r-total-box span:last-child { white-space: nowrap; }

  .r-bank {
    font-size: 8.7px;
    margin-top: 7px;
    background: var(--bg, #f0f2f5);
    border-radius: 6px;
    padding: 5px 7px;
  }
  .r-bank div {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
    padding: 1px 0;
  }
  .r-bank span, .r-bank strong { min-width: 0; overflow-wrap: anywhere; }
  .r-bank strong { text-align: right; }
  .r-bank.r-dashed-primary { border: 1px dashed var(--primary, #890c25); }
  .r-bank.r-dashed-warning { border: 1px dashed var(--warning, #f59e0b); }
  .r-box-title {
    font-size: 9px;
    font-weight: 800;
    color: var(--primary, #890c25);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 2px;
  }
  .r-box-title.warning { color: var(--warning, #f59e0b); }
  .r-long-text {
    display: block !important;
    font-size: 9.5px;
    line-height: 1.18;
    color: var(--text-muted, #64748b);
    white-space: pre-line;
  }

  .r-stamps {
    display: flex;
    justify-content: center;
    margin-top: 18px;
    font-size: 8.5px;
    color: var(--text-muted, #64748b);
  }
  .r-stamps div {
    border-top: 1px solid var(--text, #1e293b);
    width: 50%;
    text-align: center;
    padding-top: 3px;
  }
  /* Print all voucher content in legible black, including labels and declaration. */
  .receipts-row, .receipt, .receipt span, .receipt strong,
  .receipt .r-long-text, .receipt .r-box-title,
  .receipt .r-brand-tagline, .receipt .r-receipt-no,
  .receipt .r-fee-breakdown-head, .receipt .r-fee-breakdown-row > span {
    color: #000 !important;
  }

  .r-footer {
    text-align: center;
    font-size: 7.5px;
    color: var(--text-muted, #64748b);
    margin-top: 7px;
  }

  @media (max-width: 980px) {
    .receipts-row { overflow-x: auto; }
    .receipt { flex: 0 0 360px; }
  }

  @media print {
    html, body { width: 100%; }
    body {
      background: #fff;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .voucher-toolbar { display: none !important; }
    .installment-block { max-width: none; width: 100%; margin: 0; }
    .installment-label { font-size: 10px; margin-bottom: 4px; }
    .receipts-row { width: 100%; border-radius: 0; overflow: visible; }
    .receipt { flex: 1 1 33.333%; padding: 7px 8px; }
    .copy-tag { margin-bottom: 5px; }
    .r-header { padding-bottom: 5px; margin-bottom: 5px; }
    .r-brand img { width: 28px; height: 28px; flex-basis: 28px; }
    .r-brand-name { font-size: 9.5px; }
    .r-brand-tagline { font-size: 7.2px; }
    .r-brand-voucher-no { font-size: 8.2px; }
    .r-receipt-no { font-size: 7.8px; }
    .r-qr, .r-qr img, .r-qr canvas { width: 36px !important; height: 36px !important; }
    .r-info { font-size: 8px; }
    .r-dates { font-size: 8px; padding: 3px 1px; margin-bottom: 5px; }
    .r-total-box { font-size: 8.5px; }
    .r-bank { font-size: 7.7px; margin-top: 5px; padding: 4px 5px; }
    .r-fee-note { font-size: 8px; margin: 5px 0; padding: 3px 5px; }
    .r-late-fine-notice { font-size: 8px; margin: 5px 0; padding: 4px 5px; }
    .r-late-fine-notice strong { font-size: 8.5px; }
    .r-long-text { font-size: 8px; }
    .r-stamps { margin-top: 14px; font-size: 7.5px; }
    .r-footer { font-size: 6.8px; margin-top: 5px; }
  }
</style>
