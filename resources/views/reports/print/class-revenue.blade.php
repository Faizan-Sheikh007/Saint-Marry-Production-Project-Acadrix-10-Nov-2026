<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class/Program-wise Revenue</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar"><button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button><a href="javascript:history.back()" class="secondary">Back</a></div>
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Class / Program-wise Revenue',
    'reportSubtitle' => ($from ? $from->format('d M Y') : 'Beginning').' – '.($to ? $to->format('d M Y') : 'Current'),
    'metaLines' => [
      ['Classes / Programs', $classes->count()],
      ['Students', $totals['students']],
      ['Total Charged', 'Rs. '.number_format($totals['charged'], 0)],
      ['Verified Collected', 'Rs. '.number_format($totals['collected'], 0)],
      ['Outstanding', 'Rs. '.number_format($totals['outstanding'], 0)],
      ['Collection Rate', number_format($totals['rate'],1).'%'],
    ],
  ])
  <div class="stat-strip">
    <div class="stat"><div class="num">{{ number_format($totals['charged'],0) }}</div><div class="lbl">Charged (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totals['collected'],0) }}</div><div class="lbl">Collected (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totals['outstanding'],0) }}</div><div class="lbl">Outstanding (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($totals['rate'],1) }}%</div><div class="lbl">Collection Rate</div></div>
  </div>
  <div class="section-title">Revenue by Class / Program</div>
  <table class="rpt"><thead><tr><th>#</th><th>Program</th><th>Semester/Part</th><th>Students</th><th>Charged</th><th>Discounts</th><th>Collected</th><th>Outstanding</th><th>Students with Dues</th><th>Rate</th></tr></thead><tbody>
    @forelse($classes as $i => $c)
    <tr><td class="ctr">{{ $i+1 }}</td><td>{{ $c->program->name ?? '—' }}</td><td>{{ $c->section_label }}</td><td class="ctr">{{ $c->students_count }}</td><td class="num">{{ number_format($c->total_charged,0) }}</td><td class="num">{{ number_format($c->discounts,0) }}</td><td class="num">{{ number_format($c->total_collected,0) }}</td><td class="num">{{ number_format($c->outstanding,0) }}</td><td class="ctr">{{ $c->students_with_dues }}</td><td class="ctr">{{ number_format($c->collection_rate,1) }}%</td></tr>
    @empty<tr><td colspan="11" class="ctr">No classes found.</td></tr>@endforelse
  </tbody>
  @if($classes->count())<tfoot><tr><td colspan="3" class="ctr">Totals</td><td class="ctr">{{ $totals['students'] }}</td><td class="num">{{ number_format($totals['charged'],0) }}</td><td colspan="2"></td><td class="num">{{ number_format($totals['collected'],0) }}</td><td class="num">{{ number_format($totals['outstanding'],0) }}</td><td></td><td class="ctr">{{ number_format($totals['rate'],1) }}%</td></tr></tfoot>@endif
  </table>
  <div class="foot-row"><div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div><div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
  <div class="disclaimer">Collected amounts use verified payments. Outstanding is based on voucher balances for the selected issued-voucher period.</div>
</div></div>
</body>
</html>
