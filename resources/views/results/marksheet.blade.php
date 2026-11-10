<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Marksheet — {{ $publication?->exam_name ?? ($sheets->first()['student']->name ?? 'Result') }}</title>
<style>
  :root { --primary:#8b0c22; --ink:#1a1a1a; --muted:#555; --line:#333; --hair:#ccc; }
  * { box-sizing:border-box; }
  body {
    font-family:Georgia,'Times New Roman',Times,serif; background:#e9edf2; margin:0; padding:24px; color:var(--ink);
  }
  .toolbar { max-width:900px; margin:0 auto 16px; display:flex; justify-content:flex-end; gap:10px; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar button, .toolbar a {
    padding:9px 18px; border-radius:7px; border:none; background:var(--primary); color:#fff;
    font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
  }
  .toolbar a.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }
  .print-help { max-width:900px; margin:0 auto 12px; font:12px/1.5 'Segoe UI',Arial,sans-serif; color:var(--muted); }

  .sheet {
    max-width:900px; margin:0 auto 28px; background:#fff; padding:28px 36px 32px;
    box-shadow:0 1px 4px rgba(0,0,0,.12);
  }
  /* No outer page/frame border: keep only the content/table rules. */
  .sheet-inner { border:0; padding:0; position:relative; }

  /* ── Letterhead ── */
  .letterhead { display:flex; align-items:flex-start; gap:16px; }
  .letterhead .logo-wrap { width:76px; flex-shrink:0; display:flex; align-items:center; justify-content:center; padding-top:2px; }
  .letterhead .logo-wrap img { width:100%; height:auto; object-fit:contain; }
  .letterhead .logo-wrap .logo-fallback {
    width:72px; height:72px; border-radius:50%; border:2px solid var(--line);
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:22px; color:var(--primary);
  }
  .letterhead .center { flex:1; text-align:center; }
  .letterhead .inst-name { font-size:23px; font-weight:700; letter-spacing:.6px; color:var(--primary); text-transform:uppercase; }
  .letterhead .inst-affil { font-size:11px; color:var(--muted); margin-top:2px; font-style:italic; }
  .letterhead .inst-address { font-size:12px; font-weight:600; margin-top:3px; }
  .letterhead .doc-name { font-size:13.5px; font-weight:700; letter-spacing:.4px; margin-top:8px; text-transform:uppercase; }
  .letterhead .doc-sub { font-size:11.5px; color:var(--muted); margin-top:2px; letter-spacing:.3px; }

  .photo-box {
    width:78px; height:96px; border:1.3px solid var(--line); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; text-align:center;
    font-size:9px; color:var(--muted); line-height:1.35; padding:4px;
  }
  .photo-box img { width:100%; height:100%; object-fit:cover; }

  hr.rule { border:none; border-top:1.5px solid var(--line); margin:14px 0 12px; }

  /* ── Student details ── */
  /* Compact student information: two balanced rows, then a three-field row.
     Every cell is populated; no empty placeholder or status badge. */
  .details-grid {
    display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr);
    column-gap:14px; row-gap:4px; font-size:13px; margin-bottom:6px;
  }
  .details-grid .row { display:flex; align-items:baseline; gap:6px; padding:2px 0; min-width:0; }
  .details-grid .lbl { font-weight:700; white-space:nowrap; }
  .details-grid .lbl::after { content:' :'; }
  .details-grid .val { border-bottom:1px dotted var(--hair); flex:1; min-width:0; padding-bottom:1px; overflow-wrap:anywhere; }
  .details-last-row {
    grid-column:1 / -1; display:grid;
    grid-template-columns:minmax(0,0.7fr) minmax(0,1.1fr) minmax(0,1.2fr);
    column-gap:14px;
  }

  /* ── Subjects table ── */
  table.subjects { width:100%; border-collapse:collapse; margin:16px 0 10px; font-size:12px; }
  table.subjects th, table.subjects td { border:1px solid var(--line); padding:6px 8px; }
  table.subjects th {
    background:#f0f0f0; font-size:10.5px; text-transform:uppercase; letter-spacing:.3px; font-weight:700; text-align:center;
  }
  table.subjects td.subject-name { text-align:left; }
  table.subjects td.num { text-align:center; }
  table.subjects tfoot td { font-weight:700; background:#fafafa; text-align:center; }
  table.subjects tfoot td.subject-name { text-align:left; }

  .status-pass { color:#166534; font-weight:700; }
  .status-fail { color:#991b1b; font-weight:700; }
  .status-pending { color:var(--muted); }

  .credits-line { font-size:11.5px; display:flex; justify-content:space-between; gap:20px; margin-bottom:6px; flex-wrap:wrap; }
  .credits-line > div { flex:1; min-width:220px; }

  .sgpa-line { font-size:13.5px; font-weight:700; margin:10px 0 4px; }
  .sgpa-line span { color:var(--primary); }

  .notes-box {
    font-size:11.5px; margin:10px 0; padding:8px 12px; border:1px dashed var(--hair); background:#fcfcfc;
  }
  .notes-box strong { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.3px; margin-bottom:3px; color:var(--primary); }

  .foot-row { display:flex; justify-content:space-between; align-items:flex-end; margin-top:34px; }
  .sign-block { text-align:center; min-width:200px; }
  .sign-block img.sig-img { max-height:38px; margin-bottom:2px; }
  .sign-block .sig-name { font-style:italic; font-size:15px; min-height:20px; }
  .sign-line { border-top:1.3px solid var(--line); padding-top:4px; font-size:11.5px; font-weight:700; }
  .pub-date { font-size:12px; }
  .pub-date strong { font-weight:700; }

  .abbrev-box { font-size:9.7px; color:var(--muted); margin-top:22px; line-height:1.55; }
  .abbrev-box strong { color:var(--ink); }

  table.grade-table { width:100%; border-collapse:collapse; margin-top:10px; font-size:10px; }
  table.grade-table caption {
    text-align:left; font-size:11px; font-weight:700; margin-bottom:4px; text-transform:uppercase; letter-spacing:.3px;
  }
  table.grade-table th, table.grade-table td { border:1px solid var(--line); padding:5px 4px; text-align:center; }
  table.grade-table th { background:#f0f0f0; font-weight:700; }

  .disclaimer { font-size:10px; font-weight:700; text-align:center; margin-top:16px; border-top:1px solid var(--hair); padding-top:8px; }

  /* Reserve whitespace within the paper instead of using browser page
     margins, so Chromium does not add the URL, date, title or page number. */
  @page { size:A4 portrait; margin:0; }
  @media print {
    html, body { background:#fff; margin:0; padding:0; }
    .toolbar, .print-help { display:none !important; }
    .sheet {
      box-shadow:none; margin:0; max-width:none;
      padding:15mm 16mm 18mm;
      page-break-after:always;
      -webkit-box-decoration-break:clone;
      box-decoration-break:clone;
    }
    .sheet:last-child { page-break-after:auto; }
    a[href]::after { content:none !important; }
  }
  @media screen and (max-width:600px) {
    body { padding:12px; }
    .sheet { padding:18px; }
    .details-grid { grid-template-columns:1fr; }
    .details-last-row { grid-template-columns:1fr; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>
<p class="print-help">Print on A4 paper. If your browser still adds a URL, title, date or page number, open <strong>More settings</strong> in the Print dialog and uncheck <strong>Headers and footers</strong>.</p>

@php
  $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
  $instTagline = \App\Models\Setting::get('institution_tagline');
  $logoPath    = \App\Models\Setting::get('institution_logo');
  $sigName     = \App\Models\Setting::get('exam_controller_name', \App\Models\Setting::get('authorized_signatory_name'));
  $sigTitle    = \App\Models\Setting::get('exam_controller_title', \App\Models\Setting::get('authorized_signatory_title', 'Controller of Examinations'));
  $sigImageRaw = \App\Models\Setting::get('exam_controller_signature', \App\Models\Setting::get('authorized_signatory_signature'));
  // Signatures saved via the new Examinations panel are stored as a base64
  // data URI directly in the database; older ones may still be a path on
  // the storage disk, so render either correctly.
  $sigImage = $sigImageRaw
      ? (str_starts_with($sigImageRaw, 'data:') ? $sigImageRaw : storage_asset($sigImageRaw))
      : null;

  $gradeBands = [
    ['90 – 100', 'A+'], ['80 – 89.9', 'A'], ['70 – 79.9', 'B'],
    ['60 – 69.9', 'C'], ['50 – 59.9', 'D'], ['Below 50', 'F'],
  ];
@endphp

@foreach($sheets as $sheet)
@php
  $student = $sheet['student'];
  $class   = $sheet['school_class'] ?? $student->schoolClass;

  // Strip ordinal batch numbers (e.g. "33rd") from displayed text — the
  // underlying stored program / exam name / publication record is left
  // untouched, only how it's shown on the marksheet changes.
  $stripOrdinal = fn ($s) => $s ? trim(preg_replace('/\s{2,}/', ' ', preg_replace('/\b\d+(?:st|nd|rd|th)\b\s*/i', '', $s))) : $s;

  $program = $stripOrdinal($class->program->name ?? null);

  $rawExamName     = $publication?->exam_name ?? ($examName ?? 'Examination Result');
  $examNameDisplay = $stripOrdinal($rawExamName);
@endphp
<div class="sheet">
<div class="sheet-inner">

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
      <div class="doc-name">{{ $examNameDisplay }}</div>
      <div class="doc-sub">Result Intimation Card</div>
    </div>
    <div class="photo-box">
      @if($student->photo)
        <img src="{{ storage_asset($student->photo) }}" alt="Photo">
      @else
        Paste your recent passport size photo
      @endif
    </div>
  </div>

  <hr class="rule">

  {{-- Student details: compact three-row layout with no status badge or empty cells --}}
  <div class="details-grid">
    <div class="row"><span class="lbl">Student's Name</span><span class="val">{{ $student->name }}</span></div>
    <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $student->session ?: '—' }}</span></div>

    <div class="row"><span class="lbl">Father's Name</span><span class="val">{{ $student->father_name ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Program</span><span class="val">{{ $program ?? '—' }}</span></div>

    <div class="details-last-row">
      <div class="row"><span class="lbl">Roll No.</span><span class="val">{{ $student->roll_number ?: '—' }}</span></div>
      <div class="row"><span class="lbl">Semester/Part</span><span class="val">{{ $class->section_label ?? $class->name ?? '—' }}</span></div>
      <div class="row"><span class="lbl">Registration No.</span><span class="val">{{ $student->student_id }}</span></div>
    </div>
  </div>

  {{-- Subjects --}}
  <table class="subjects">
    <thead>
      <tr>
        <th style="width:34px">Sr.<br>No.</th>
        <th style="text-align:left">Subject</th>
        <th>Total<br>Marks</th>
        <th>Marks<br>Obtained</th>
        <th>Grade</th>
        <th>Result</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sheet['subjects'] as $i => $s)
      <tr>
        <td class="num">{{ $i + 1 }}</td>
        <td class="subject-name">{{ $s['subject'] }}</td>
        <td class="num">{{ rtrim(rtrim(number_format($s['total_marks'], 2), '0'), '.') }}</td>
        <td class="num">
          @if($s['marks_obtained'] !== null)
            {{ rtrim(rtrim(number_format($s['marks_obtained'], 2), '0'), '.') }}
          @else
            <span class="status-pending">—</span>
          @endif
        </td>
        <td class="num">{{ $s['grade'] ?? '—' }}</td>
        <td class="num">
          @if($s['status'] === 'Pass') <span class="status-pass">Pass</span>
          @elseif($s['status'] === 'Fail') <span class="status-fail">Fail</span>
          @else <span class="status-pending">Awaited</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="subject-name">Total</td>
        <td class="num">{{ rtrim(rtrim(number_format($sheet['total_max'], 2), '0'), '.') }}</td>
        <td class="num">{{ rtrim(rtrim(number_format($sheet['total_obtained'], 2), '0'), '.') }}</td>
        <td class="num">{{ $sheet['overall_grade'] }}</td>
        <td class="num">{{ $sheet['overall_status'] }}</td>
      </tr>
    </tfoot>
  </table>

  <div class="credits-line">
    <div>Total Subjects: {{ $sheet['subjects']->count() }}</div>
    <div style="text-align:right">Position in Class: {{ $sheet['position'] ? '#' . $sheet['position'] : '—' }}</div>
  </div>

  <div class="sgpa-line">
    Percentage: <span>{{ $sheet['percentage'] }}%</span> &nbsp; &middot; &nbsp;
    Overall Grade: <span>{{ $sheet['overall_grade'] }}</span> &nbsp; &middot; &nbsp;
    Result: <span>{{ $sheet['overall_status'] }}</span>
  </div>

  @if($publication?->notes)
  <div class="notes-box">
    <strong>Note from Examination Office</strong>
    {{ $publication->notes }}
  </div>
  @endif

  {{-- Signature / publication date --}}
  <div class="foot-row">
    <div class="sign-block">
      @if($sigImage)<img src="{{ $sigImage }}" class="sig-img" alt="Signature">@else<div class="sig-name">&nbsp;</div>@endif
      <div class="sign-line">{{ $sigTitle }}</div>
    </div>
    <div class="pub-date">
      <strong>Date of publication of Result:</strong>
      {{ optional($publication?->published_at)->format('d/m/Y') ?? '—' }}
    </div>
  </div>

  {{-- Key to abbreviations --}}
  <div class="abbrev-box">
    <strong>Key to Abbreviations:</strong>
    TM: Total Marks; A+/A/B/C/D/F: Grade Letter as per Grade Table below.
  </div>

  {{-- Grade table --}}
  <table class="grade-table">
    <caption>Grade Table</caption>
    <thead>
      <tr>
        @foreach($gradeBands as $band) <th>{{ $band[0] }}</th> @endforeach
      </tr>
    </thead>
    <tbody>
      <tr>
        @foreach($gradeBands as $band) <td>{{ $band[1] }}</td> @endforeach
      </tr>
    </tbody>
  </table>

  <div class="disclaimer">
    Disclaimer: The result displayed is subject to correction in case of any inadvertent errors. This is a computer-generated marksheet issued by {{ $instName }}.
  </div>

</div>
</div>
@endforeach

</body>
</html>
