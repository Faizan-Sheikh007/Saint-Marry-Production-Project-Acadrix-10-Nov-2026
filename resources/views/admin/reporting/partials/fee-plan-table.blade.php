<div class="card fee-report-card">
  <div class="fee-report-card-header">
    <div class="fee-report-card-heading">
      <div class="fee-report-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
      <div>
        <div class="fee-report-card-title">{{ $tableTitle }}</div>
        <div class="fee-report-card-subtitle">
          {{ $rows->count() }} fee plan{{ $rows->count() === 1 ? '' : 's' }} · {{ $maxInstallments }} installment column{{ $maxInstallments === 1 ? '' : 's' }}
        </div>
      </div>
    </div>

    <div class="fee-report-scroll-hint" aria-hidden="true">
      <i class="fas fa-arrows-left-right"></i>
      <span>Scroll to view all columns</span>
    </div>
  </div>

  <div class="fee-report-scroll-tools" aria-label="Horizontal report navigation">
    <span class="fee-report-scroll-tools-label"><i class="fas fa-grip-lines"></i> Quick horizontal scroll</span>
    <div class="fee-report-top-scroll" aria-label="Scroll report horizontally">
      <div></div>
    </div>
  </div>

  <div class="fee-report-scroll-shell">
    <div class="table-wrap fee-report-scroll" tabindex="0" aria-label="{{ $tableTitle }} table. Scroll horizontally to view more columns.">
      <table class="fee-report-table" style="min-width:{{ 1120 + ($maxInstallments * 165) }}px">
        <thead>
          <tr>
            <th>ID / Roll No.</th>
            <th>Name</th>
            <th>Father Name</th>
            <th>Program</th>
            <th>Semester / Part</th>
            <th class="money-col">Total Fee</th>
            <th class="center-col">Total Installments</th>
            <?php for ($i = 1; $i <= $maxInstallments; $i++): ?>
              <th class="installment-col">Installment {{ $i }} Due</th>
            <?php endfor; ?>
            <th class="money-col paid-col">Total Paid</th>
            <th class="money-col pending-col">Total Pending</th>
            <th class="money-col overdue-col">Outstandings</th>
            <th class="center-col">Fee Status</th>
            <th class="remarks-col">Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($rows) === 0): ?>
            <tr>
              <td colspan="{{ 12 + $maxInstallments }}" class="fee-report-empty">
                <div class="fee-report-empty-icon"><i class="fas fa-receipt"></i></div>
                <strong>No fee records found</strong>
                <span>Try changing the report filters above.</span>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="student-id-cell">{{ $row['student']->student_id ?? '—' }}</td>
                <td class="student-name-cell">{{ $row['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $row['student']])</td>
                <td>{{ $row['student']->father_name ?? '—' }}</td>
                <td class="program-cell">{{ $row['student']->program->name ?? '—' }}</td>
                <td>{{ $row['student']->schoolClass->section_label ?? '—' }}</td>
                <td class="money-cell">Rs. {{ number_format($row['total_fee'], 0) }}</td>
                <td class="center-cell"><span class="count-pill">{{ $row['total_installments'] }}</span></td>

                <?php for ($i = 1; $i <= $maxInstallments; $i++): ?>
                  <?php $inst = $row['installments']->get($i); ?>
                  <td class="installment-cell">
                    <?php if ($inst): ?>
                      <?php
                        $installmentStatus = strtolower((string) $inst['status']);
                        $installmentClass = $installmentStatus === 'paid' ? 'is-paid' : ($installmentStatus === 'overdue' ? 'is-overdue' : 'is-due');
                      ?>
                      <div class="installment-box {{ $installmentClass }}">
                        <div class="installment-amount">Rs. {{ number_format($inst['pending'] > 0 ? $inst['pending'] : $inst['total'], 0) }}</div>
                        <div class="installment-date"><i class="far fa-calendar"></i> {{ $inst['due_date']?->format('d M Y') ?? 'No date' }}</div>
                        <div class="installment-status">{{ $inst['status'] }}</div>
                      </div>
                    <?php else: ?>
                      <span class="muted-dash">—</span>
                    <?php endif; ?>
                  </td>
                <?php endfor; ?>

                <td class="money-cell paid-value">Rs. {{ number_format($row['total_paid'], 0) }}</td>
                <td class="money-cell pending-value">Rs. {{ number_format($row['total_pending'], 0) }}</td>
                <td class="money-cell overdue-value">Rs. {{ number_format($row['outstanding'], 0) }}</td>
                <td class="center-cell">
                  <span class="fee-status-pill {{ $row['fee_status'] === 'paid' ? 'is-paid' : 'is-unpaid' }}">
                    <i class="fas {{ $row['fee_status'] === 'paid' ? 'fa-circle-check' : 'fa-clock' }}"></i>
                    {{ ucfirst($row['fee_status']) }}
                  </span>
                </td>
                <td class="remarks-cell">
                  <?php $remarkRoute = auth('accounts')->check() ? 'accounts.reporting.fee-remarks.save' : 'admin.reporting.fee-remarks.save'; ?>
                  <form method="POST" action="{{ route($remarkRoute, [$reportType, $row['assignment']->id]) }}" class="fee-remark-form">
                    @csrf
                    <textarea name="remarks" class="form-control fee-remark-input" rows="2" placeholder="Add admin remarks...">{{ $row['remarks'] }}</textarea>
                    <button type="submit" class="btn btn-outline btn-sm fee-remark-save" title="Save remarks" aria-label="Save remarks">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

@once
<style>
  /* ─────────────────────────────────────────────────────────────
     Financial report table — premium dashboard presentation
     ───────────────────────────────────────────────────────────── */
  .fee-report-card {
    width:100%;
    max-width:100%;
    min-width:0;
    overflow:hidden;
    contain:inline-size;
    border:1px solid var(--border,#e2e8f0) !important;
    border-radius:16px !important;
    background:var(--card,#fff);
    box-shadow:0 10px 28px rgba(15,23,42,.07) !important;
  }

  .fee-report-card-header {
    min-height:68px;
    padding:14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    border-bottom:1px solid var(--border,#e2e8f0);
    background:linear-gradient(135deg,rgba(137,12,37,.075),rgba(137,12,37,.018) 58%,rgba(255,255,255,.3));
  }

  .fee-report-card-heading { display:flex;align-items:center;gap:11px;min-width:0; }
  .fee-report-card-icon {
    width:40px;height:40px;border-radius:11px;flex:0 0 auto;
    display:flex;align-items:center;justify-content:center;
    color:var(--primary,#890c25);background:rgba(137,12,37,.095);
    border:1px solid rgba(137,12,37,.10);
  }
  .fee-report-card-title { font-size:15px;font-weight:800;color:var(--text,#0f172a);letter-spacing:-.01em; }
  .fee-report-card-subtitle { margin-top:3px;font-size:11.5px;color:var(--text-muted,#64748b);font-weight:500; }

  .fee-report-scroll-hint {
    display:inline-flex;align-items:center;gap:7px;flex:0 0 auto;
    padding:7px 10px;border-radius:999px;
    font-size:11px;font-weight:700;color:var(--primary,#890c25);
    background:rgba(137,12,37,.06);border:1px solid rgba(137,12,37,.10);
  }

  /* Slim synchronized top rail: useful on wide reports without the bulky grey bar. */
  .fee-report-scroll-tools {
    display:none;
    grid-template-columns:auto minmax(160px,1fr);
    align-items:center;
    gap:12px;
    padding:7px 16px 6px;
    border-bottom:1px solid var(--border,#e2e8f0);
    background:color-mix(in srgb,var(--card,#fff) 94%,var(--primary,#890c25) 6%);
  }
  .fee-report-scroll-tools-label {
    white-space:nowrap;font-size:10px;font-weight:800;letter-spacing:.02em;
    text-transform:uppercase;color:var(--text-muted,#64748b);
  }
  .fee-report-scroll-tools-label i { margin-right:5px;color:var(--primary,#890c25); }
  .fee-report-top-scroll {
    width:100%;height:10px;overflow-x:auto;overflow-y:hidden;
    scrollbar-width:thin;scrollbar-color:var(--primary,#890c25) rgba(148,163,184,.20);
  }
  .fee-report-top-scroll > div { height:1px; }

  .fee-report-scroll-shell { position:relative;width:100%;max-width:100%;min-width:0; }
  .fee-report-scroll-shell::after {
    content:'';position:absolute;z-index:9;right:0;top:0;bottom:0;width:34px;
    pointer-events:none;opacity:0;transition:opacity .18s ease;
    background:linear-gradient(90deg,transparent,rgba(15,23,42,.07));
  }
  .fee-report-scroll-shell.is-overflowing:not(.at-end)::after { opacity:1; }

  .fee-report-scroll {
    display:block;width:100%;max-width:100%;min-width:0;
    overflow-x:auto;overflow-y:hidden;
    -webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;
    /* Keep horizontal scrolling functional, but hide the duplicate native bottom rail. */
    scrollbar-width:none;
    -ms-overflow-style:none;
    outline:none;
  }
  .fee-report-scroll:focus-visible { box-shadow:inset 0 0 0 2px rgba(137,12,37,.16); }

  /* Keep one professional maroon rail at the top only. */
  .fee-report-top-scroll::-webkit-scrollbar { height:10px; }
  .fee-report-top-scroll::-webkit-scrollbar-track {
    background:rgba(148,163,184,.16);
    border-radius:999px;
  }
  .fee-report-top-scroll::-webkit-scrollbar-thumb {
    background:linear-gradient(90deg,#7f0b20,#a50f2c);
    border:2px solid transparent;
    background-clip:padding-box;
    border-radius:999px;
    min-width:72px;
  }
  .fee-report-top-scroll::-webkit-scrollbar-thumb:hover { background:linear-gradient(90deg,#6f091b,#8f0c25);background-clip:padding-box; }

  /* Chromium/Safari: suppress the table's duplicate bottom scrollbar without disabling scroll. */
  .fee-report-scroll::-webkit-scrollbar { display:none;width:0;height:0; }

  .fee-report-scroll > table {
    width:max-content !important;max-width:none !important;margin-bottom:0 !important;
  }

  .fee-report-table {
    border-collapse:separate !important;border-spacing:0 !important;
    font-size:12px;background:var(--card,#fff);
  }
  .fee-report-table th,
  .fee-report-table td {
    border:0 !important;
    border-bottom:1px solid var(--border,#e2e8f0) !important;
    padding:13px 14px !important;
    vertical-align:middle;
    white-space:nowrap;
  }
  .fee-report-table thead th {
    position:relative;
    background:color-mix(in srgb,var(--bg,#f8fafc) 82%,var(--primary,#890c25) 18%) !important;
    color:var(--text-muted,#475569);
    font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.035em;
    border-bottom:1px solid color-mix(in srgb,var(--border,#e2e8f0) 68%,var(--primary,#890c25) 32%) !important;
  }
  .fee-report-table thead th::after {
    content:'';position:absolute;right:0;top:28%;height:44%;width:1px;background:rgba(100,116,139,.14);
  }
  .fee-report-table thead th:last-child::after { display:none; }
  .fee-report-table tbody tr { transition:background .14s ease,box-shadow .14s ease; }
  .fee-report-table tbody tr:nth-child(even) td { background:rgba(248,250,252,.62); }
  .fee-report-table tbody tr:hover td { background:rgba(137,12,37,.028); }
  .fee-report-table tbody tr:last-child td { border-bottom:0 !important; }

  /* Keep the most important identity columns visible while moving horizontally. */
  .fee-report-table th:nth-child(1),.fee-report-table td:nth-child(1) {
    position:sticky;left:0;z-index:4;min-width:142px;max-width:142px;
  }
  .fee-report-table th:nth-child(2),.fee-report-table td:nth-child(2) {
    position:sticky;left:142px;z-index:4;min-width:158px;max-width:158px;
    box-shadow:7px 0 14px -12px rgba(15,23,42,.75);
  }
  .fee-report-table thead th:nth-child(1),.fee-report-table thead th:nth-child(2) { z-index:7; }
  .fee-report-table tbody td:nth-child(1),.fee-report-table tbody td:nth-child(2) { background:var(--card,#fff); }
  .fee-report-table tbody tr:nth-child(even) td:nth-child(1),
  .fee-report-table tbody tr:nth-child(even) td:nth-child(2) { background:#fbfcfd; }
  .fee-report-table tbody tr:hover td:nth-child(1),
  .fee-report-table tbody tr:hover td:nth-child(2) { background:#fdf8f9; }

  .student-id-cell { font-weight:800;color:var(--text,#0f172a);font-variant-numeric:tabular-nums; }
  .student-name-cell { font-weight:750;color:var(--text,#0f172a);white-space:normal !important;overflow-wrap:anywhere; }
  .program-cell { white-space:normal !important;min-width:210px;max-width:260px;line-height:1.35; }
  .money-col,.money-cell { text-align:right; }
  .money-cell { font-weight:750;font-variant-numeric:tabular-nums; }
  .center-col,.center-cell { text-align:center; }
  .paid-value { color:#079455;font-weight:800; }
  .pending-value { color:#d97706;font-weight:800; }
  .overdue-value { color:#dc2626;font-weight:800; }

  .count-pill {
    min-width:28px;height:28px;padding:0 8px;border-radius:999px;
    display:inline-flex;align-items:center;justify-content:center;
    background:var(--bg,#f8fafc);border:1px solid var(--border,#e2e8f0);
    color:var(--text,#0f172a);font-weight:800;
  }

  .installment-col { min-width:160px; }
  .installment-cell { min-width:160px;padding-top:9px !important;padding-bottom:9px !important; }
  .installment-box {
    min-width:132px;padding:8px 10px;border-radius:10px;
    border:1px solid var(--border,#e2e8f0);background:var(--bg,#f8fafc);
  }
  .installment-box.is-paid { border-color:rgba(5,150,105,.18);background:rgba(5,150,105,.045); }
  .installment-box.is-due { border-color:rgba(217,119,6,.18);background:rgba(245,158,11,.05); }
  .installment-box.is-overdue { border-color:rgba(220,38,38,.18);background:rgba(220,38,38,.045); }
  .installment-amount { font-size:12px;font-weight:800;color:var(--text,#0f172a);font-variant-numeric:tabular-nums; }
  .installment-date { margin-top:3px;font-size:10px;color:var(--text-muted,#64748b); }
  .installment-date i { margin-right:4px; }
  .installment-status { margin-top:3px;font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.04em; }
  .installment-box.is-paid .installment-status { color:#059669; }
  .installment-box.is-due .installment-status { color:#d97706; }
  .installment-box.is-overdue .installment-status { color:#dc2626; }
  .muted-dash { color:#94a3b8; }

  .fee-status-pill {
    display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;
    font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.035em;
    border:1px solid transparent;
  }
  .fee-status-pill.is-paid { color:#047857;background:rgba(5,150,105,.09);border-color:rgba(5,150,105,.14); }
  .fee-status-pill.is-unpaid { color:#b45309;background:rgba(245,158,11,.11);border-color:rgba(217,119,6,.15); }
  .fee-status-pill i { font-size:9px; }

  .remarks-col,.remarks-cell { min-width:245px; }
  .fee-remark-form { display:flex;gap:7px;align-items:center; }
  .fee-remark-input {
    min-width:190px !important;width:190px;min-height:56px !important;resize:vertical;
    border-radius:9px !important;font-size:11.5px !important;line-height:1.35;
    background:var(--card,#fff) !important;
  }
  .fee-remark-input:focus { border-color:var(--primary,#890c25) !important;box-shadow:0 0 0 3px rgba(137,12,37,.08) !important; }
  .fee-remark-save { width:34px;height:34px;padding:0 !important;display:inline-flex !important;align-items:center;justify-content:center;border-radius:9px !important; }

  .fee-report-empty { text-align:center !important;padding:44px 24px !important;color:var(--text-muted,#64748b); }
  .fee-report-empty-icon {
    width:42px;height:42px;margin:0 auto 10px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;color:var(--primary,#890c25);background:rgba(137,12,37,.08);
  }
  .fee-report-empty strong { display:block;color:var(--text,#0f172a);font-size:13px;margin-bottom:3px; }
  .fee-report-empty span { display:block;font-size:11.5px; }

  /* Make the summary area above the table visually consistent with the premium table. */
  .fee-report-summary { display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px; }
  .fee-report-summary .fee-kpi {
    position:relative;overflow:hidden;min-height:88px;padding:16px 17px;
    display:flex;align-items:center;gap:12px;
    background:var(--card,#fff);border:1px solid var(--border,#e2e8f0);border-radius:14px;
    box-shadow:0 7px 18px rgba(15,23,42,.055);
  }
  .fee-report-summary .fee-kpi::after {
    content:'';position:absolute;right:-18px;top:-24px;width:74px;height:74px;border-radius:50%;background:rgba(137,12,37,.035);
  }
  .fee-kpi-icon {
    width:40px;height:40px;flex:0 0 auto;border-radius:11px;
    display:flex;align-items:center;justify-content:center;background:var(--bg,#f8fafc);border:1px solid var(--border,#e2e8f0);
    color:var(--primary,#890c25);font-size:14px;
  }
  .fee-kpi-value { font-size:20px;font-weight:850;line-height:1.05;color:var(--text,#0f172a);font-variant-numeric:tabular-nums; }
  .fee-kpi-label { margin-top:5px;font-size:10.5px;font-weight:650;color:var(--text-muted,#64748b); }
  .fee-kpi.is-paid .fee-kpi-icon,.fee-kpi.is-paid .fee-kpi-value { color:#059669; }
  .fee-kpi.is-pending .fee-kpi-icon,.fee-kpi.is-pending .fee-kpi-value { color:#d97706; }
  .fee-kpi.is-overdue .fee-kpi-icon,.fee-kpi.is-overdue .fee-kpi-value { color:#dc2626; }

  .fee-report-actions { display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap; }
  .fee-report-actions .btn { border-radius:9px !important;min-height:34px; }

  @media (max-width:900px) {
    .fee-report-card-header { align-items:flex-start; }
    .fee-report-scroll-hint { display:none; }
    .fee-report-scroll-tools { grid-template-columns:1fr;gap:5px; }
    .fee-report-scroll-tools-label { display:none; }
    .fee-report-table th:nth-child(2),.fee-report-table td:nth-child(2) { position:static;box-shadow:none; }
    .fee-report-table th:nth-child(1),.fee-report-table td:nth-child(1) { min-width:132px;max-width:132px; }
  }

  @media print {
    /* Never print UI scroll rails or browser scrollbar gutters. */
    .fee-report-scroll-tools,.fee-report-scroll-hint { display:none !important; }
    .fee-report-card,.fee-report-scroll-shell,.fee-report-scroll {
      overflow:visible !important;max-width:none !important;box-shadow:none !important;contain:none !important;
    }
    .fee-report-scroll::-webkit-scrollbar,.fee-report-top-scroll::-webkit-scrollbar { display:none !important;width:0 !important;height:0 !important; }
    .fee-report-scroll,.fee-report-top-scroll { scrollbar-width:none !important; }
    .fee-report-scroll-shell::after { display:none !important; }
    .fee-report-table { width:100% !important;min-width:0 !important;font-size:8px !important; }
    .fee-report-table th,.fee-report-table td { padding:5px 6px !important; }
    .fee-report-table th:nth-child(1),.fee-report-table td:nth-child(1),
    .fee-report-table th:nth-child(2),.fee-report-table td:nth-child(2) {
      position:static !important;min-width:0 !important;max-width:none !important;box-shadow:none !important;
    }
  }
</style>
@endonce

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
  function setupFeeReportScrollbars() {
    document.querySelectorAll('.fee-report-card').forEach(function (card) {
      var tableScroller = card.querySelector('.fee-report-scroll');
      var topScroller = card.querySelector('.fee-report-top-scroll');
      var tools = card.querySelector('.fee-report-scroll-tools');
      var shell = card.querySelector('.fee-report-scroll-shell');
      var hint = card.querySelector('.fee-report-scroll-hint');
      var spacer = topScroller ? topScroller.firstElementChild : null;

      if (!tableScroller || !topScroller || !tools || !shell || !spacer) return;

      function refresh() {
        var scrollWidth = tableScroller.scrollWidth;
        var hasOverflow = scrollWidth > tableScroller.clientWidth + 2;
        var maxScroll = Math.max(0, scrollWidth - tableScroller.clientWidth);

        spacer.style.width = scrollWidth + 'px';
        tools.style.display = hasOverflow ? 'grid' : 'none';
        if (hint) hint.style.display = hasOverflow ? '' : 'none';

        shell.classList.toggle('is-overflowing', hasOverflow);
        shell.classList.toggle('at-start', tableScroller.scrollLeft <= 2);
        shell.classList.toggle('at-end', !hasOverflow || tableScroller.scrollLeft >= maxScroll - 2);

        if (hasOverflow && Math.abs(topScroller.scrollLeft - tableScroller.scrollLeft) > 1) {
          topScroller.scrollLeft = tableScroller.scrollLeft;
        }
      }

      if (!tableScroller.dataset.scrollSyncReady) {
        tableScroller.dataset.scrollSyncReady = '1';
        var syncing = false;

        tableScroller.addEventListener('scroll', function () {
          if (!syncing) {
            syncing = true;
            topScroller.scrollLeft = tableScroller.scrollLeft;
            syncing = false;
          }
          refresh();
        }, { passive:true });

        topScroller.addEventListener('scroll', function () {
          if (syncing) return;
          syncing = true;
          tableScroller.scrollLeft = topScroller.scrollLeft;
          syncing = false;
          refresh();
        }, { passive:true });

        if ('ResizeObserver' in window) {
          var resizeObserver = new ResizeObserver(refresh);
          resizeObserver.observe(tableScroller);
          var table = tableScroller.querySelector('table');
          if (table) resizeObserver.observe(table);
        }

        window.addEventListener('resize', refresh, { passive:true });
      }

      refresh();
    });
  }

  setupFeeReportScrollbars();
  setTimeout(setupFeeReportScrollbars, 150);
  setTimeout(setupFeeReportScrollbars, 600);
});
</script>
@endonce
