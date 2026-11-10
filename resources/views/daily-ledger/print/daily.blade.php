<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Ledger — {{ $date->format('M d, Y') }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="sheet">
<div class="sheet-inner">

  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Daily Ledger Report',
    'reportSubtitle' => $date->format('l, F d, Y'),
    'metaLines' => [
      ['Date', $date->format('Y-m-d')],
      ['Total In', 'Rs. '.number_format($totals['in'], 0)],
      ['Total Out + Expense', 'Rs. '.number_format($totals['out'] + $totals['expense'], 0)],
      ['Closing Balance', 'Rs. '.number_format($closingBalance, 0)],
    ],
  ])

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ number_format($totals['in'], 0) }}</div><div class="lbl">In (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totals['out'], 0) }}</div><div class="lbl">Out (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totals['expense'], 0) }}</div><div class="lbl">Expense (Rs.)</div></div>
  </div>

  <div class="section-title">Entries</div>
  <table class="rpt">
    <thead><tr><th>#</th><th>Time</th><th>Type</th><th>Particulars</th><th>Category</th><th>Amount</th><th>Entered By</th><th>Remarks</th></tr></thead>
    <tbody>
      @forelse($entries as $i => $e)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td>{{ $e->created_at->format('g:i A') }}</td>
        <td>{{ ucfirst($e->type) }}</td>
        <td>{{ $e->particulars }}</td>
        <td>{{ $e->category ?? '—' }}</td>
        <td class="num">{{ number_format($e->amount, 0) }}</td>
        <td>{{ $e->createdBy->name ?? '—' }}</td>
        <td>{{ $e->remarks ?? '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="8" class="ctr">No entries for this day.</td></tr>
      @endforelse
    </tbody>
    @if($entries->count())
    <tfoot>
      <tr><td colspan="5" class="ctr">Totals</td><td class="num">{{ number_format($totals['in'] + $totals['out'] + $totals['expense'], 0) }}</td><td colspan="2"></td></tr>
    </tfoot>
    @endif
  </table>

  @if($closing)
  <div class="section-title">Cash Closing Count</div>
  <table class="rpt">
    <thead><tr><th>Counted Total</th><th>System Closing Balance</th><th>Difference</th><th>Closed By</th></tr></thead>
    <tbody>
      <tr>
        <td class="num">{{ number_format($closing->counted_total, 0) }}</td>
        <td class="num">{{ number_format($closing->expected_total, 0) }}</td>
        <td class="num">{{ number_format($closing->difference, 0) }}</td>
        <td>{{ $closing->closedBy->name ?? '—' }}</td>
      </tr>
    </tbody>
  </table>
  @endif

</div>
</div>

</body>
</html>
