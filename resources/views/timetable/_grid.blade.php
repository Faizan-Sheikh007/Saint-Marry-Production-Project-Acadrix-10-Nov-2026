{{--
  Reusable read-only weekly pivot-grid timetable.
  Expects:
    $items    - Collection of Timetable models (subject + teacher/schoolClass eager loaded)
    $context  - 'class'   => secondary line in each cell shows the TEACHER
                'teacher' => secondary line in each cell shows the CLASS
                'merged'  => secondary line shows CLASS + TEACHER
    $emptyText (optional) - custom empty-state message
--}}
@php
  $gridDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  // Only show days that actually have at least one entry, but always keep Mon–Fri visible.
  $daysWithData = $items->pluck('day')->unique()->toArray();
  $gridDays = array_values(array_filter($gridDays, fn($d) => in_array($d, ['Monday','Tuesday','Wednesday','Thursday','Friday']) || in_array($d, $daysWithData)));

  $rows = [];
  foreach ($items as $item) {
      $key = $item->start_time . '|' . $item->end_time;
      $start = \Carbon\Carbon::parse($item->start_time);
      $end   = \Carbon\Carbon::parse($item->end_time);
      $rows[$key]['label'] = $start->format('g:i A') . ' – ' . $end->format('g:i A');
      $rows[$key]['start_sort'] = $start->format('H:i:s');
      $rows[$key]['end_sort']   = $end->format('H:i:s');
      // A day/slot cell can hold more than one lecture (e.g. two sections
      // sharing a room, or a combined/elective period) — collect an array
      // per cell instead of a single item, or every lecture past the first
      // one in that slot silently disappears from print.
      $rows[$key]['cells'][$item->day][] = $item;
  }
  uasort($rows, fn($a, $b) => [$a['start_sort'], $a['end_sort']] <=> [$b['start_sort'], $b['end_sort']]);

  // Insert the configured break according to its real clock position instead
  // of always placing it after the table. Old values such as "4:30-5:15"
  // are interpreted against the timetable rows so afternoon schedules still
  // place the break at 4:30 PM rather than 4:30 AM.
  $gridBreakLabel = \App\Models\Setting::get('timetable_break_label', '🕌 Juma Prayer (Friday) / Lunch Break');
  $gridBreakTime  = \App\Models\Setting::get('timetable_break_time', '12:00-12:45');
  $gridClockMinutes = function (?string $value): ?int {
      if (!$value || !preg_match('/(\d{1,2})\s*:\s*(\d{2})\s*(am|pm)?/i', trim($value), $m)) return null;
      $h = (int) $m[1]; $min = (int) $m[2]; $ampm = strtolower($m[3] ?? '');
      if ($min > 59 || $h > 23) return null;
      if ($ampm) {
          if ($h < 1 || $h > 12) return null;
          $h = $h % 12 + ($ampm === 'pm' ? 12 : 0);
      }
      return $h * 60 + $min;
  };
  $gridBreakParts = preg_split('/\s*[-–—]\s*/u', (string) $gridBreakTime, 2);
  $gridBreakSortMinute = $gridClockMinutes($gridBreakParts[0] ?? null);
  $gridBreakHasAmPm = (bool) preg_match('/\b(?:am|pm)\b/i', $gridBreakParts[0] ?? '');
  $gridRowMinutes = collect($rows)->map(fn($r) => $gridClockMinutes($r['start_sort']))->filter(fn($v) => $v !== null)->values();
  if ($gridBreakSortMinute !== null && !$gridBreakHasAmPm && $gridBreakSortMinute < 12 * 60 && $gridRowMinutes->isNotEmpty()) {
      $pmCandidate = $gridBreakSortMinute + 12 * 60;
      $distance = fn($candidate) => $gridRowMinutes->map(fn($m) => abs($m - $candidate))->min();
      if ($pmCandidate < 24 * 60 && $distance($pmCandidate) < $distance($gridBreakSortMinute)) $gridBreakSortMinute = $pmCandidate;
  }

  // ── Group same lecture across the days it repeats on (for the small
  // "Mon–Wed" / "Wed–Sat" badge), matching the on-screen grid's logic. ──
  $gridDayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  $gridDayShort = ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed','Thursday'=>'Thu','Friday'=>'Fri','Saturday'=>'Sat'];

  $gridSeriesKey = fn($item) => implode('|', [$item->school_class_id, $item->subject_id, $item->teacher_id, $item->start_time, $item->end_time]);

  $gridSeriesDays = [];
  foreach ($items as $item) {
      $gridSeriesDays[$gridSeriesKey($item)][] = $item->day;
  }

  if (!function_exists('tt_grid_format_day_range')) {
      function tt_grid_format_day_range(array $days, array $dayOrder, array $dayShort): string {
          $sorted = collect($days)->unique()->sortBy(fn($d) => array_search($d, $dayOrder))->values();
          if ($sorted->count() <= 1) return $dayShort[$sorted->first()] ?? '';

          $runs = [];
          $runStart = $sorted[0];
          $prevIdx  = array_search($sorted[0], $dayOrder);
          for ($i = 1; $i < $sorted->count(); $i++) {
              $idx = array_search($sorted[$i], $dayOrder);
              if ($idx === $prevIdx + 1) { $prevIdx = $idx; continue; }
              $runs[] = [$runStart, $dayOrder[$prevIdx]];
              $runStart = $sorted[$i];
              $prevIdx  = $idx;
          }
          $runs[] = [$runStart, $dayOrder[$prevIdx]];

          return collect($runs)
              ->map(fn($r) => $r[0] === $r[1] ? $dayShort[$r[0]] : $dayShort[$r[0]] . '–' . $dayShort[$r[1]])
              ->implode(', ');
      }
  }

  // Colour by TEACHER, matching the on-screen timetable grid exactly —
  // including any manually-assigned color from the "Teacher Colors" modal.
  // (Previously this hashed the subject name into a fixed 8-color palette,
  // which is why print never reflected the colors picked on-screen.)
  if (!function_exists('tt_grid_color')) {
      function tt_grid_color(?int $teacherId, ?string $manualColor = null): array {
          if (!$teacherId) {
              return ['#f1f1f3', '#5b5b63'];
          }

          if ($manualColor && preg_match('/^#[0-9A-Fa-f]{6}$/', $manualColor)) {
              $r = hexdec(substr($manualColor, 1, 2));
              $g = hexdec(substr($manualColor, 3, 2));
              $b = hexdec(substr($manualColor, 5, 2));
              return ["rgba({$r},{$g},{$b},.16)", $manualColor];
          }

          $hue = fmod($teacherId * 137.508, 360);
          return ["hsl({$hue}, 68%, 93%)", "hsl({$hue}, 45%, 28%)"];
      }
  }
@endphp

@once
<style>
.tt-grid-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.tt-grid{width:100%;min-width:700px;table-layout:fixed;border-collapse:collapse;font-family:inherit}
.tt-grid-scroll-hint{display:none;font-size:11.5px;color:var(--text-muted,#64748b);padding:0 0 8px}
.tt-grid th{font-size:9.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);padding:8px 6px;background:var(--bg,#f8fafc);border-bottom:1.5px solid var(--border,#e2e8f0);text-align:center;white-space:normal;overflow:hidden;text-overflow:ellipsis;overflow-wrap:break-word;word-break:break-word}
.tt-grid th:first-child{text-align:left;padding-left:12px}
.tt-grid td{border:1px solid var(--border,#e2e8f0);padding:4px;vertical-align:middle;text-align:center;overflow:hidden}
.tt-grid td:first-child{text-align:center;padding:7px 5px;background:var(--bg,#f8fafc);font-size:10px;line-height:1.3;font-weight:700;color:var(--text-muted);white-space:normal;overflow-wrap:break-word;word-break:normal}
.tt-grid-cell{border-radius:5px;padding:5px 8px;border:1px solid rgba(0,0,0,.05)}
.tt-grid-cell-stack{display:flex;flex-direction:column;gap:3px}
.tt-grid-top{display:flex;align-items:flex-start;justify-content:center;gap:5px}
.tt-grid-subject{font-size:10.5px;font-weight:700;line-height:1.25;overflow-wrap:break-word}
.tt-grid-secondary{font-size:9px;margin-top:1px;font-weight:500;line-height:1.3;overflow-wrap:break-word;opacity:.82}
.tt-grid-days{flex-shrink:0;display:inline-block;font-size:7.5px;font-weight:700;letter-spacing:.02em;padding:1px 5px;margin-top:1px;border-radius:8px;background:rgba(255,255,255,.65);white-space:nowrap;opacity:.9}
.tt-grid-empty-cell{color:var(--border,#cbd5e1);font-size:12px}
.tt-grid-break-row td{background:#fff8f8 !important;border-color:rgba(139,12,34,.22) !important}
.tt-grid-break-row td:first-child{color:var(--primary,#8b0c22);font-weight:800}
.tt-grid-break-cell{padding:6px 8px !important}
.tt-grid-break-label{display:flex;align-items:center;justify-content:center;gap:6px;font-size:10px;font-weight:700;color:var(--primary,#8b0c22)}
.tt-grid-break-label i{font-size:9px}
.tt-grid-empty{padding:48px 16px;text-align:center;color:var(--text-muted)}
.tt-grid-empty i{font-size:36px;opacity:.3;display:block;margin-bottom:10px}
@media (max-width:768px){
  .tt-grid-scroll-hint{display:block}
}
.tt-grid tr{page-break-inside:avoid;break-inside:avoid}
@media print{
  .tt-grid-wrap{overflow:visible !important}
  .tt-grid{min-width:0}
  .tt-grid th{background:#f3f3f3 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .tt-grid td:first-child{background:#f8f8f8 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .tt-grid-cell{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
@endonce

@if(count($rows) > 0)
<div class="tt-grid-scroll-hint"><i class="fas fa-arrows-left-right"></i> Swipe to see more days</div>
<div class="tt-grid-wrap">
  <table class="tt-grid">
    {{-- Explicit column widths so the "fixed" layout can't let a long time
         label (e.g. "10:00 AM – 11:00 AM") overflow its cell and visually
         bleed into the neighbouring day column — the bug this replaces. --}}
    <colgroup>
      <col style="width:15%">
      @foreach($gridDays as $day)
        <col>
      @endforeach
    </colgroup>
    <thead>
      <tr>
        <th>Time / Day</th>
        @foreach($gridDays as $day)
          <th>{{ strtoupper($day) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @php $gridBreakInserted = false; @endphp
      @foreach($rows as $row)
        @php $gridRowStartMinute = $gridClockMinutes($row['start_sort']); @endphp
        @if(!$gridBreakInserted && $gridBreakSortMinute !== null && $gridRowStartMinute !== null && $gridBreakSortMinute <= $gridRowStartMinute)
          <tr class="tt-grid-break-row">
            <td>{{ $gridBreakTime }}</td>
            <td class="tt-grid-break-cell" colspan="{{ count($gridDays) }}">
              <div class="tt-grid-break-label"><i class="fas fa-mug-hot"></i> {{ $gridBreakLabel }}</div>
            </td>
          </tr>
          @php $gridBreakInserted = true; @endphp
        @endif

        <tr>
          <td>{{ $row['label'] }}</td>
          @foreach($gridDays as $day)
            <td>
              @if(!empty($row['cells'][$day]))
                <div class="tt-grid-cell-stack">
                @foreach($row['cells'][$day] as $c)
                  @php
                    [$bg, $fg] = tt_grid_color($c->teacher_id, $c->teacher->timetable_color ?? null);
                    $cDays = $gridSeriesDays[$gridSeriesKey($c)] ?? [$c->day];
                  @endphp
                  <div class="tt-grid-cell" style="background:{{ $bg }};color:{{ $fg }}">
                    <div class="tt-grid-top">
                      <span class="tt-grid-subject">{{ $c->subject->name ?? '—' }}</span>
                      @if(count($cDays) > 1)
                        <span class="tt-grid-days">{{ tt_grid_format_day_range($cDays, $gridDayOrder, $gridDayShort) }}</span>
                      @endif
                    </div>
                    <div class="tt-grid-secondary">
                      @if($context === 'teacher')
                        {{ $c->schoolClass->display_name ?? ($c->schoolClass->name ?? '—') }}
                      @elseif($context === 'merged')
                        {{ $c->schoolClass->display_name ?? ($c->schoolClass->name ?? '—') }}
                        &middot; {{ $c->teacher->name ?? 'TBA' }}
                      @else
                        {{ $c->teacher->name ?? 'TBA' }}
                      @endif
                      @if($c->room) &middot; Rm {{ $c->room }} @endif
                    </div>
                  </div>
                @endforeach
                </div>
              @else
                <span class="tt-grid-empty-cell">—</span>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach

      @if(!$gridBreakInserted)
        <tr class="tt-grid-break-row">
          <td>{{ $gridBreakTime }}</td>
          <td class="tt-grid-break-cell" colspan="{{ count($gridDays) }}">
            <div class="tt-grid-break-label"><i class="fas fa-mug-hot"></i> {{ $gridBreakLabel }}</div>
          </td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
@else
<div class="tt-grid-empty">
  <i class="fas fa-calendar-xmark"></i>
  <p>{{ $emptyText ?? 'No timetable entries yet.' }}</p>
</div>
@endif
