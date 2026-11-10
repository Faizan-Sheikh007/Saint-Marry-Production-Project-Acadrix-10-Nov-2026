<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Income vs Expense Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Income vs Expense Report',
    'reportSubtitle' => $from->format('d M Y').' – '.$to->format('d M Y'),
    'metaLines' => [
      ['Grouping', ucfirst($group)],
      ['Verified Fee Income', 'Rs. '.number_format($totalFeeIncome, 2)],
      ['Other Ledger Income', 'Rs. '.number_format($totalOtherIncome, 2)],
      ['Total Expenses / Out', 'Rs. '.number_format($totalExpenses, 2)],
      ['Net', 'Rs. '.number_format($net, 2)],
    ],
  ])

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ number_format($totalIncome, 0) }}</div><div class="lbl">Total Income (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totalExpenses, 0) }}</div><div class="lbl">Total Expenses (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($net, 0) }}</div><div class="lbl">Net (Rs.)</div></div>
  </div>

  <div class="section-title">{{ ucfirst($group) }} Breakdown</div>
  <table class="rpt">
    <thead><tr><th>#</th><th>Period</th><th>Fee Income</th><th>Ledger In</th><th>Total Income</th><th>Out</th><th>Expense</th><th>Total Expense</th><th>Net</th></tr></thead>
    <tbody>
      @forelse($breakdown as $i => $row)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td>{{ $row['label'] }}</td>
        <td class="num">{{ number_format($row['feeIncome'], 2) }}</td>
        <td class="num">{{ number_format($row['otherIncome'], 2) }}</td>
        <td class="num">{{ number_format($row['totalIncome'], 2) }}</td>
        <td class="num">{{ number_format($row['out'], 2) }}</td>
        <td class="num">{{ number_format($row['expense'], 2) }}</td>
        <td class="num">{{ number_format($row['totalExpense'], 2) }}</td>
        <td class="num">{{ number_format($row['net'], 2) }}</td>
      </tr>
      @empty
      <tr><td colspan="9" class="ctr">No transactions for this period.</td></tr>
      @endforelse
    </tbody>
    @if($breakdown->count())
    <tfoot><tr><td colspan="2" class="ctr">Totals</td><td class="num">{{ number_format($totalFeeIncome,2) }}</td><td class="num">{{ number_format($totalOtherIncome,2) }}</td><td class="num">{{ number_format($totalIncome,2) }}</td><td class="num">{{ number_format($totalOut,2) }}</td><td class="num">{{ number_format($totalExpense,2) }}</td><td class="num">{{ number_format($totalExpenses,2) }}</td><td class="num">{{ number_format($net,2) }}</td></tr></tfoot>
    @endif
  </table>

  @if($expenseByCategory->count())
  <div class="section-title">Expense / Out by Category</div>
  <table class="rpt"><thead><tr><th>#</th><th>Category</th><th>Amount</th></tr></thead><tbody>
    @foreach($expenseByCategory as $category => $amount)<tr><td class="ctr">{{ $loop->iteration }}</td><td>{{ $category }}</td><td class="num">{{ number_format($amount,2) }}</td></tr>@endforeach
  </tbody></table>
  @endif

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>
  <div class="disclaimer">Fee income includes verified payments only. Ledger In, Out and Expense entries come from the Daily Ledger. System-generated — for internal administrative use.</div>
</div></div>
</body>
</html>
