<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Ledger — Yearly Report {{ $year }}</title>
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
    'reportTitle' => 'Daily Ledger — Yearly Report',
    'reportSubtitle' => (string) $year,
    'metaLines' => [
      ['Year', (string) $year],
      ['Total In', 'Rs. '.number_format($totals['in'], 0)],
      ['Total Out', 'Rs. '.number_format($totals['out'], 0)],
      ['Total Expense', 'Rs. '.number_format($totals['expense'], 0)],
    ],
  ])

  <div class="section-title">Month-by-Month Breakdown</div>
  <table class="rpt">
    <thead><tr><th>#</th><th>Month</th><th>In</th><th>Out</th><th>Expense</th><th>Net</th></tr></thead>
    <tbody>
      @foreach($months as $i => $m)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td>{{ $m['label'] }}</td>
        <td class="num">{{ number_format($m['in'], 0) }}</td>
        <td class="num">{{ number_format($m['out'], 0) }}</td>
        <td class="num">{{ number_format($m['expense'], 0) }}</td>
        <td class="num">{{ number_format($m['in'] - $m['out'] - $m['expense'], 0) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="ctr">Total</td>
        <td class="num">{{ number_format($totals['in'], 0) }}</td>
        <td class="num">{{ number_format($totals['out'], 0) }}</td>
        <td class="num">{{ number_format($totals['expense'], 0) }}</td>
        <td class="num">{{ number_format($totals['in'] - $totals['out'] - $totals['expense'], 0) }}</td>
      </tr>
    </tfoot>
  </table>

</div>
</div>

</body>
</html>
