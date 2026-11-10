<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Date Sheet — {{ $examTitle }}{{ $session ? ' — '.$session->name : '' }}</title>
<style>
* { box-sizing: border-box; }
:root { --ink: #20242b; --line: #3b424c; }
body { margin: 0; color: var(--ink); background: #edf0f4; font-family: Arial, Helvetica, sans-serif; }
.toolbar { max-width: 1600px; margin: 18px auto 0; padding: 0 16px; display: flex; justify-content: flex-end; gap: 9px; }
.toolbar button, .toolbar a { border-radius: 6px; padding: 10px 16px; font: 600 13px Arial, sans-serif; text-decoration: none; cursor: pointer; }
.toolbar button { background: #9b1735; color: white; border: 0; }
.toolbar a { color: #273244; background: white; border: 1px solid #cdd3db; }
.sheet { max-width: 1600px; margin: 12px auto 24px; padding: 22px 24px 40px; background: #fff; box-shadow: 0 5px 24px #15203318; }
.institution { text-align: center; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 7px; }
.heading { text-align: center; }
h1 { font-size: 25px; line-height: 1.1; margin: 2px 0 5px; }
.exam-title { font-size: 17px; font-weight: 700; line-height: 1.3; margin-bottom: 15px; }
.meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end; margin: 0 0 14px; font-size: 13px; }
.meta .end { text-align: center; }
.meta .count { text-align: right; }
.table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
@media screen and (max-width: 800px) {
  .toolbar { margin-top: 8px; flex-wrap: wrap; padding: 0 10px; }
  .toolbar a, .toolbar button { flex: 1 1 auto; text-align: center; }
  .sheet { margin: 10px; padding: 16px 12px 24px; }
  .meta { grid-template-columns: 1fr 1fr; gap: 5px 10px; font-size: 11px; }
  .meta .end { text-align: right; }
  .meta .count { grid-column: 1 / -1; text-align: left; }
  .table-wrap table { min-width: max-content; }
  .table-wrap th.class-col { min-width: 110px; }
  h1 { font-size: 21px; }
  .exam-title { font-size: 14px; }
  .signatures { margin: 35px 0 0; }
  .sign { width: 42%; }
}
table { border-collapse: collapse; table-layout: fixed; width: 100%; }
th, td { border: 1px solid var(--line); text-align: center; vertical-align: middle; padding: 11px 6px; font-size: 12px; line-height: 1.35; overflow-wrap: anywhere; }
thead th { font-weight: 750; background: #f7f8fa; }
th.serial { width: 44px; }
th.date { width: 105px; }
th.day { width: 94px; }
th.class-col { min-width: 88px; }
.subject { font-weight: 600; }
.subject .entry + .entry { margin-top: 7px; padding-top: 7px; border-top: 1px dashed #aab0b9; }
.empty-cell { color: #7b8490; font-weight: 400; }
.no-exams { text-align: center; padding: 32px; border: 1px solid #bcc4ce; }
.signatures { display: flex; justify-content: space-between; margin: 54px 25px 0; gap: 30px; }
.sign { width: 210px; text-align: center; font-size: 11px; font-weight: 700; }
.sign .line { border-top: 1px solid #555; padding-top: 7px; }
.generated { color: #808997; font-size: 9px; text-align: center; margin-top: 22px; }
/* A4 landscape printable area after 8mm margins: 281mm x 194mm. */
@page { size: A4 landscape; margin: 8mm; }
@media print {
  html, body { margin: 0 !important; padding: 0 !important; background: white !important; width: 281mm; height: 194mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .toolbar { display: none !important; }
  /* Fixed one-page canvas prevents pagination; sheet is scaled by script to fit. */
  .print-canvas { position: relative; width: 281mm; height: 194mm; overflow: hidden; break-after: avoid; page-break-after: avoid; }
  .sheet { position: absolute; left: 0; top: 0; width: 281mm; max-width: none; margin: 0; padding: 0; box-shadow: none; transform: scale(var(--print-scale, 1)); transform-origin: top left; }
  .institution { font-size: 10px; margin-bottom: 4px; }
  h1 { font-size: 20px; }
  .exam-title { font-size: 13px; margin-bottom: 8px; }
  .meta { font-size: 10px; margin-bottom: 8px; }
  .table-wrap { overflow: visible; }
  .table-wrap table { min-width: 0 !important; width: 100%; }
  th, td { font-size: 9px; padding: 5px 3px; line-height: 1.22; }
  th.serial { width: 28px; } th.date { width: 65px; } th.day { width: 62px; }
  th.class-col { min-width: 0 !important; }
  .subject .entry + .entry { margin-top: 3px; padding-top: 3px; }
  thead { display: table-header-group; }
  tr { break-inside: avoid; page-break-inside: avoid; }
  .signatures { margin: 20px 18px 0; }
  .sign .line { padding-top: 4px; }
  .generated { font-size: 7px; margin-top: 8px; }
}

</style>
</head>
<body>
<div class="toolbar">
  <a href="{{ route('admin.examinations.index', request()->query()) }}">Back to Examinations</a>
  <button type="button" onclick="window.print()">Print / Save PDF</button>
</div>
<div class="print-canvas">
<div class="sheet">
@php
  $instName = \App\Models\Setting::get('institution_name', 'Institute');
  $controllerName = \App\Models\Setting::get('exam_controller_name');
  $controllerTitle = \App\Models\Setting::get('exam_controller_title', 'Controller of Examinations');
  $ordinal = function ($n) {
      $n = (int) $n;
      return $n . (in_array($n % 100, [11, 12, 13]) ? 'th' : (match ($n % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' }));
  };
@endphp
<div class="heading">
  <div class="institution">{{ $instName }}</div>
  <h1>Date Sheet</h1>
  <div class="exam-title">{{ $examTitle }}{{ $session ? ' — '.$session->name : '' }}</div>
</div>
@if($examinations->isNotEmpty())
  <div class="meta">
    <div><strong>Start Time:</strong> {{ $commonStart ?? 'See individual schedule' }}</div>
    <div class="end"><strong>End Time:</strong> {{ $commonEnd ?? 'See individual schedule' }}</div>
    <div class="count"><strong>Total Subjects:</strong> {{ $totalSubjects }}</div>
  </div>
  <div class="table-wrap">
    <table aria-label="Examination date sheet by date and program semester">
      <thead>
        <tr>
          <th scope="col" class="serial">Sr #</th>
          <th scope="col" class="date">Date</th>
          <th scope="col" class="day">Day</th>
          @foreach($programSemesters as $col)
            <th scope="col" class="class-col">
              {{ $col->program_name }} — {{ $col->term_number !== null ? $ordinal($col->term_number) : $col->term_label }}
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($byDate as $date => $exams)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ \Carbon\Carbon::parse($date)->format('d-M-Y') }}</td>
          <td>{{ \Carbon\Carbon::parse($date)->format('l') }}</td>
          @foreach($programSemesters as $col)
          @php $cellExams = $dateGrid->get($date)?->get($col->key, collect()) ?? collect(); @endphp
          <td class="subject">
            @forelse($cellExams as $exam)
              <div class="entry">
                {{ $exam->subject?->name ?? '—' }}
                @if(!$commonStart || !$commonEnd)
                  @if($exam->time_label)<div class="empty-cell" style="margin-top:3px;font-size:.87em">{{ $exam->time_label }}</div>@endif
                @endif
              </div>
            @empty
              <span class="empty-cell">—</span>
            @endforelse
          </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="signatures">
    <div class="sign"><div class="line">{{ $controllerName ?: $controllerTitle }}</div>@if($controllerName)<div style="font-weight:400;margin-top:3px">{{ $controllerTitle }}</div>@endif</div>
    <div class="sign"><div class="line">Principal / Head of Institution</div></div>
  </div>
  <div class="generated">System-generated examination date sheet · Printed {{ now()->format('d M Y, h:i A') }}</div>
@else
  <div class="no-exams">No examinations match the selected filters. Go back and adjust the filters.</div>
@endif
</div>
</div>
<script>
(function () {
  const sheet = document.querySelector('.sheet');
  const printMedia = window.matchMedia('print');
  if (!sheet) return;
  function fitSinglePage() {
    if (!printMedia.matches) return;
    sheet.style.setProperty('--print-scale', '1');
    // The print stylesheet uses actual millimetre units; measure in the same units.
    const mmToPixels = 96 / 25.4;
    const maxWidth = 281 * mmToPixels;
    const maxHeight = 194 * mmToPixels;
    const naturalWidth = Math.max(sheet.scrollWidth, sheet.getBoundingClientRect().width);
    const naturalHeight = Math.max(sheet.scrollHeight, sheet.getBoundingClientRect().height);
    const scale = Math.min(1, maxWidth / Math.max(1, naturalWidth), maxHeight / Math.max(1, naturalHeight)) * 0.975;
    sheet.style.setProperty('--print-scale', String(scale));
  }
  window.addEventListener('beforeprint', fitSinglePage);
  printMedia.addEventListener?.('change', function (event) {
    if (event.matches) fitSinglePage();
    else sheet.style.removeProperty('--print-scale');
  });
})();
</script>
</body>
</html>
