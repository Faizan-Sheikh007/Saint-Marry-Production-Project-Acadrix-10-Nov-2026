{{--
  Reusable read-only "agenda" timetable list.
  Expects:
    $items    - Collection of Timetable models (subject + teacher/schoolClass eager loaded)
    $context  - 'class'   => secondary line shows the TEACHER (used on a student's schedule)
                'teacher' => secondary line shows the CLASS   (used on a teacher's schedule)
    $emptyText (optional) - custom empty-state message
--}}
@php
  $dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  $grouped  = $items->groupBy('day')->sortBy(fn($v, $k) => array_search($k, $dayOrder));
  $today    = now()->format('l');

  if (!function_exists('tt_agenda_color')) {
      function tt_agenda_color(?string $name): array {
          $palette = [
              ['#fde8e8', '#c0192e'], ['#e8f9ef', '#0d9e6e'], ['#fff4e6', '#d97706'],
              ['#fde8ee', '#9d174d'], ['#f0e8fd', '#6d28d9'], ['#e6f4fd', '#0369a1'],
              ['#fef9e6', '#92400e'], ['#e8eafd', '#3730a3'],
          ];
          $idx = $name ? (crc32($name) % count($palette)) : 0;
          return $palette[$idx];
      }
  }
@endphp

@once
<style>
.tt-agenda-wrap{font-family:inherit}
.tt-agenda-day{margin-bottom:14px}
.tt-agenda-day:last-child{margin-bottom:0}
.tt-agenda-day-label{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px}
.tt-agenda-day-label.is-today{color:var(--primary)}
.tt-agenda-day-label .tt-today-dot{width:6px;height:6px;border-radius:50%;background:var(--primary)}
.tt-agenda-period{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;background:var(--bg,#f8fafc);margin-bottom:6px}
.tt-agenda-period:last-child{margin-bottom:0}
.tt-agenda-time{flex-shrink:0;font-size:11.5px;font-weight:700;color:var(--text-muted);width:118px}
.tt-agenda-swatch{width:4px;align-self:stretch;border-radius:3px;flex-shrink:0}
.tt-agenda-main{flex:1;min-width:0}
.tt-agenda-subject{font-size:13.5px;font-weight:700;display:block}
.tt-agenda-secondary{font-size:11.5px;color:var(--text-muted);margin-top:1px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tt-agenda-room{flex-shrink:0;font-size:11px;color:var(--text-muted);white-space:nowrap}
.tt-agenda-empty{text-align:center;padding:32px 16px;color:var(--text-muted)}
.tt-agenda-empty i{font-size:26px;opacity:.35;display:block;margin-bottom:8px}
.tt-agenda-empty p{font-size:13px;margin:0}
</style>
@endonce

<div class="tt-agenda-wrap">
  @forelse($grouped as $day => $periods)
    <div class="tt-agenda-day">
      <div class="tt-agenda-day-label {{ $day === $today ? 'is-today' : '' }}">
        @if($day === $today)<span class="tt-today-dot"></span>@endif
        {{ $day }}
      </div>
      @foreach($periods->sortBy('start_time') as $p)
        @php [$bg, $fg] = tt_agenda_color($p->subject->name ?? null); @endphp
        <div class="tt-agenda-period">
          <div class="tt-agenda-swatch" style="background:{{ $fg }}"></div>
          <div class="tt-agenda-time">
            {{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
          </div>
          <div class="tt-agenda-main">
            <span class="tt-agenda-subject">{{ $p->subject->name ?? '—' }}</span>
            <span class="tt-agenda-secondary">
              @if($context === 'teacher')
                <i class="fas fa-chalkboard"></i> {{ $p->schoolClass->display_name ?? ($p->schoolClass->name ?? '—') }}
              @else
                <i class="fas fa-user"></i> {{ $p->teacher->name ?? 'Teacher TBA' }}
              @endif
            </span>
          </div>
          @if($p->room)
            <div class="tt-agenda-room"><i class="fas fa-location-dot"></i> {{ $p->room }}</div>
          @endif
        </div>
      @endforeach
    </div>
  @empty
    <div class="tt-agenda-empty">
      <i class="fas fa-calendar-xmark"></i>
      <p>{{ $emptyText ?? 'No timetable entries yet.' }}</p>
    </div>
  @endforelse
</div>
