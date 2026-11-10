<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Installment Plan - {{ $student->name }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root { --primary:#7a0e24; --primary-dark:#500817; --primary-tint:#fbeef1; --ink:#1c1c1c; --muted:#666; --line:#2b2b2b; --hair:#d7d7d7; }
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

  /* ── One physical page = one .page, containing Student, Bank and Office copies side by side ── */
  .page {
    max-width:1400px; margin:0 auto 26px; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.14);
    border-radius:12px; overflow:hidden; position:relative;
  }
  .page-accent { height:6px; background:linear-gradient(90deg, var(--primary), var(--primary-dark)); }
  .copies { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); position:relative; }
  .cut-line {
    position:absolute; top:0; bottom:0; left:33.333%; width:0; border-left:1.5px dashed #b9b9b9;
    display:flex; align-items:center; justify-content:center; pointer-events:none;
  }
  .cut-line i { position:absolute; top:14px; background:#fff; color:#9a9a9a; font-size:12px; padding:2px 4px; transform:translateX(-50%) rotate(90deg); }

  .copy { padding:14px 14px 12px; position:relative; min-width:0; }
  .copy + .copy { border-left:1.5px dashed #b9b9b9; }

  .copy-badge {
    position:relative; display:table; margin:0 0 6px auto;
    font-size:9px; font-weight:700; text-transform:uppercase;
    letter-spacing:.6px; color:#fff; background:var(--primary); border-radius:4px; padding:4px 11px;
  }

  /* ── Letterhead ── */
  .letterhead { display:flex; align-items:center; gap:7px; }
  .letterhead .logo-wrap {
    width:38px; height:38px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    border-radius:50%; border:2px solid var(--primary); padding:2px; background:#fff;
  }
  .letterhead .logo-wrap img { width:100%; height:100%; object-fit:contain; border-radius:50%; }
  .letterhead .logo-wrap .logo-fallback {
    width:100%; height:100%; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:16px; color:var(--primary);
  }
  .letterhead .center { flex:1; text-align:center; min-width:0; }
  .letterhead .inst-name { font-size:13px; font-weight:800; letter-spacing:.5px; color:var(--primary); text-transform:uppercase; line-height:1.15; }
  .letterhead .inst-affil { font-size:10px; color:var(--muted); margin-top:1px; font-style:italic; }
  .letterhead .doc-name { font-size:11.5px; font-weight:700; letter-spacing:1px; margin-top:6px; text-transform:uppercase; color:var(--ink); }
  .letterhead .doc-sub { font-size:10.5px; color:var(--muted); margin-top:1px; letter-spacing:.2px; }
  .letterhead .photo-wrap { width:52px; flex-shrink:0; display:flex; justify-content:flex-end; }
  .letterhead .photo-wrap img { width:52px; height:52px; border-radius:6px; object-fit:cover; border:1px solid var(--hair); }

  hr.rule { border:none; border-top:2px solid var(--line); margin:12px 0 10px; }

  .fee-note.top-banner {
    text-transform:uppercase; font-size:10.5px; font-weight:700; text-align:center; color:var(--primary);
    padding:6px 10px; border:1px solid var(--primary); border-radius:5px; background:var(--primary-tint);
    margin-bottom:10px; letter-spacing:.2px;
  }

  /* ── Student / plan details ── */
  .details-grid { display:grid; grid-template-columns:1fr; gap:2px 0; font-size:9px; margin-bottom:7px; }
  .details-grid .row { display:flex; gap:6px; padding:2px 0; }
  .details-grid .lbl { font-weight:700; white-space:nowrap; color:var(--muted); }
  .details-grid .lbl::after { content:' :'; }
  .details-grid .val { border-bottom:1px dotted var(--hair); flex:1; padding-bottom:1px; font-weight:600; overflow-wrap:anywhere; }
  .details-grid .val.highlight { color:var(--primary); font-weight:800; }

  /* ── Tables ── */
  .section-label {
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; margin:11px 0 5px; color:#fff;
    background:var(--primary); display:inline-block; padding:3px 9px; border-radius:3px 3px 0 0;
  }

  table.ledger, table.breakdown { width:100%; border-collapse:collapse; margin:0 0 4px; font-size:8px; table-layout:fixed; overflow-wrap:anywhere; }
  table.ledger th, table.ledger td, table.breakdown th, table.breakdown td { border:1px solid var(--hair); padding:3px 4px; }
  table.ledger th, table.breakdown th {
    background:#f4f4f6; font-size:9.5px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:left; color:var(--ink);
  }
  table.ledger td.num, table.ledger th.num, table.breakdown td.num, table.breakdown th.num { text-align:right; }
  table.ledger td.center, table.ledger th.center { text-align:center; }
  table.ledger tbody tr:nth-child(even), table.breakdown tbody tr:nth-child(even) { background:#fafafa; }

  table.breakdown tr.total-row td { font-weight:800; background:var(--primary-tint); color:var(--primary-dark); font-size:11.5px; }
  table.breakdown tr.deduction-row td { color:#166534; font-weight:700; }

  .notes-box {
    font-size:8.6px; line-height:1.45; margin:12px 0 10px; padding:9px 11px;
    border:2px solid var(--primary); background:var(--primary-tint); border-radius:5px; color:var(--ink);
    font-weight:600;
  }
  .notes-box strong {
    display:block; font-size:10.5px; text-transform:uppercase; letter-spacing:.55px;
    margin-bottom:4px; color:var(--primary-dark); font-weight:900;
  }

  .foot-row { display:flex; justify-content:space-between; align-items:flex-end; gap:5px; margin-top:16px; }
  .sign-block { text-align:center; min-width:0; flex:1; }
  .sign-line { border-top:1.3px solid var(--line); padding-top:3px; font-size:10px; font-weight:700; }
  .gen-date { font-size:9.5px; color:var(--muted); text-align:center; }

  .disclaimer { font-size:8.7px; color:var(--muted); text-align:center; margin-top:12px; border-top:1px solid var(--hair); padding-top:6px; }

  /* This stacked fallback is for narrow PHONE SCREENS only — it must
     never fire during printing. It's scoped to `screen`, not just
     max-width, because an unscoped max-width query also matches a
     printer's page box: most print dialogs default to Portrait unless
     the user manually flips it, and a portrait A4 page (~793px) is
     narrower than 900px — silently collapsing the 3-up layout into stacked copies
     full stacked "pages" exactly like Portrait would. */
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
    .page { box-shadow:none; margin:0; max-width:none; border-radius:0; page-break-after:always; }
    .page:last-child { page-break-after:auto; }
    /* Force Student, Bank and Office copies to print together. */
    .copies { grid-template-columns:repeat(3, minmax(0, 1fr)) !important; }
    .cut-line { display:flex !important; }
    .copy + .copy { border-left:1.5px dashed #b9b9b9 !important; border-top:none !important; }
    .copy { padding:8px 9px 6px; }
    /* Keep the entire three-copy voucher and its declarations on ONE A4 landscape sheet.
       Reduce vertical spacing rather than hiding any terms or signatures. */
    .page { break-inside:avoid; page-break-inside:avoid; }
    .copies { break-inside:avoid; page-break-inside:avoid; }
    .copy { padding:4px 7px 3px; break-inside:avoid; page-break-inside:avoid; }
    .page-accent { height:3px; }
    .copy-badge { margin-bottom:2px; font-size:7px; padding:2px 6px; }
    .fee-note.top-banner { padding:2px 4px; margin-bottom:3px; font-size:8px; }
    .letterhead .logo-wrap { width:25px; height:25px; }
    .letterhead .photo-wrap, .letterhead .photo-wrap img { width:30px; height:30px; }
    .letterhead .inst-name { font-size:10px; }
    .letterhead .inst-affil { font-size:8px; }
    .letterhead .doc-name { font-size:9px; margin-top:2px; }
    .letterhead .doc-sub { font-size:7px; }
    hr.rule { margin:4px 0 3px; border-top-width:1px; }
    .details-grid { font-size:7.5px; margin-bottom:3px; gap:0; }
    .details-grid .row { padding:1px 0; }
    .section-label { margin:4px 0 2px; padding:2px 5px; font-size:8px; }
    table.ledger { font-size:7px; }
    table.ledger th, table.ledger td { padding:2px 3px; }
    table.ledger th { font-size:7px; }
    .notes-box { font-size:7px; line-height:1.16; margin:3px 0; padding:4px 5px; border-width:1px; }
    .notes-box strong { font-size:8px; margin-bottom:2px; }
    .foot-row { margin-top:6px; }
    .sign-line { font-size:8px; }
    .gen-date { font-size:7px; }
    .disclaimer { font-size:7px; margin-top:4px; padding-top:3px; }
    .copy > div[style*="computer generated"] { padding:3px !important; font-size:7px !important; }
  }
</style>
</head>
<body>
@php
  $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $logoPath    = \App\Models\Setting::get('institution_logo');
  $currency    = \App\Models\Setting::get('currency', 'Rs.');
  $programName = $student->program->name ?? ($student->schoolClass->name ?? '—');
  $copyLabels  = ['Student Copy', 'Bank Copy', 'Office Copy']; // printed together, 3-up, side by side on one landscape page
@endphp

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print (3 Copies)</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="page">
  <div class="page-accent"></div>
  <div class="copies">
    <div class="cut-line"><i class="fas fa-scissors"></i></div>
    @foreach($copyLabels as $copyLabel)
    <div class="copy">

      <div class="copy-badge">{{ $copyLabel }}</div>

      {{-- Fee terms — printed at the very top of the slip, above the letterhead --}}
      <div class="fee-note top-banner">{{ $feeNote }}</div>

      {{-- Letterhead --}}
      <div class="letterhead">
        <div class="logo-wrap">
          @if($logoPath)
            <img src="{{ storage_asset($logoPath) }}" alt="Logo">
          @else
            <div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $instName), 0, 0)) ?: 'IN' }}</div>
          @endif
        </div>
        <div class="center">
          <div class="inst-name">{{ $instName }}</div>
          @if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif
          <div class="doc-name">Fee Installment Plan</div>
          <div class="doc-sub">{{ $cycleLabel ?? $feeAssignment->cycleRangeLabel() }}{{ $programName !== '—' ? ' · '.$programName : '' }}</div>
          <div class="doc-sub">Print Date: {{ now()->format('d M Y, h:i A') }}</div>
        </div>
        <div class="photo-wrap">
          @if($student->photo)
            <img src="{{ storage_asset($student->photo) }}" alt="">
          @endif
        </div>
      </div>

      <hr class="rule">

      {{-- Student / plan details --}}
      <div class="details-grid">
        <div class="row"><span class="lbl">Student Name / Father Name</span><span class="val">{{ $student->name }} / {{ $student->father_name ?: ($student->guardian_name ?: '—') }}</span></div>
        <div class="row"><span class="lbl">{{ $cycleLabel ? 'Semester Total' : ($feeAssignment->feeTemplate?->cycleLabel() ?? 'Term') . ' Total' }}</span><span class="val highlight">{{ $currency }} {{ number_format($scopeTotal, 0) }}</span></div>

        <div class="row"><span class="lbl">Installments</span><span class="val">{{ $installmentsCount }}</span></div>

        <div class="row"><span class="lbl">G.R. No #</span><span class="val">{{ $student->student_id }}</span></div>
        <div class="row"><span class="lbl">Per Installment</span><span class="val">{{ $currency }} {{ number_format($perInstallment, 0) }}</span></div>

        <div class="row"><span class="lbl">Session</span><span class="val">{{ $student->session ?? '—' }}</span></div>
        <div class="row"><span class="lbl">Semester/Part</span><span class="val">{{ $student->schoolClass->name ?? '—' }}{{ $student->section ? ' ('.$student->section.')' : '' }}</span></div>

        <div class="row"><span class="lbl">Program</span><span class="val">{{ $student->program->name ?? '—' }}</span></div>
        <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $student->academicYear->name ?? '—' }}</span></div>
      </div>

      {{-- Installment plan --}}
      <div class="section-label">{{ $cycleLabel ? "{$cycleLabel} — Installment Plan" : 'Installment Plan' }}</div>
      <table class="ledger">
        <thead>
          <tr>
            <th>Installment</th>
            <th>Voucher No</th>
            <th class="num">Amount Payable</th>
            <th class="center">Due Date</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $row)
          @php
            $position = $loop->iteration;
            $ordinal = $position == 1 ? '1st' : ($position == 2 ? '2nd' : ($position == 3 ? '3rd' : $position.'th'));
          @endphp
          <tr>
            <td>{{ $ordinal }} Payable</td>
            <td>{{ $row['voucher_no'] }}</td>
            <td class="num">{{ $currency }} {{ number_format($row['payable'], 0) }}</td>
            <td class="center">{{ $row['due_date']->format('d-M-y') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div style="font-size:10px;text-align:center;padding:8px;border-top:1px solid #ddd">This is computer generated documents does not use in legally in court.</div>
      @if($declaration)
      <div class="notes-box">
        <strong>Declaration</strong>
        {{ $declaration }}
      </div>
      @endif

      <div class="foot-row">
        <div class="sign-block"><div class="sign-line">Student Signature</div></div>
        <div class="gen-date">Generated on<br>{{ now()->format('d M Y, h:i A') }}</div>
        <div class="sign-block"><div class="sign-line">Guardian Signature</div></div>
      </div>

      <div class="disclaimer">
        This is a computer-generated fee installment plan issued by {{ $instName }}. Please retain it for your records.
      </div>

    </div>
    @endforeach
  </div>
</div>

<script>
  // Open the browser print dialog automatically when this printable slip loads.
  // A short delay lets logos/photos/fonts finish rendering first.
  window.addEventListener('load', function () {
    setTimeout(function () {
      window.print();
    }, 300);
  });
</script>

</body>
</html>
