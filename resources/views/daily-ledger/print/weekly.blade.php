<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Ledger — Weekly Report</title>
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
    'reportTitle' => 'Daily Ledger — Weekly Report',
    'reportSubtitle' => $from->format('M d').' – '.$to->format('M d, Y'),
    'metaLines' => [
      ['Period', $from->format('M d').' – '.$to->format('M d, Y')],
      ['Total In', 'Rs. '.number_format($totals['in'], 0)],
      ['Total Out', 'Rs. '.number_format($totals['out'], 0)],
      ['Total Expense', 'Rs. '.number_format($totals['expense'], 0)],
    ],
  ])

  <div class="section-title">Day-by-Day Breakdown</div>
  <table class="rpt">
    <thead><tr><th>#</th><th>Date</th><th>In</th><th>Out</th><th>Expense</th><th>Net</th></tr></thead>
    <tbody>
      @foreach($days as $i => $d)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td>{{ $d['date']->format('D, M d, Y') }}</td>
        <td class="num">{{ number_format($d['in'], 0) }}</td>
        <td class="num">{{ number_format($d['out'], 0) }}</td>
        <td class="num">{{ number_format($d['expense'], 0) }}</td>
        <td class="num">{{ number_format($d['in'] - $d['out'] - $d['expense'], 0) }}</td>
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
