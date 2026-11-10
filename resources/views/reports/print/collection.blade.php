<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Collection Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar"><button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button><a href="javascript:history.back()" class="secondary">Back</a></div>
@php $avg = $payments->count() ? $total / $payments->count() : 0; @endphp
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Collection Report',
    'reportSubtitle' => $from->format('d M Y').' – '.$to->format('d M Y'),
    'metaLines' => [
      ['Period', $from->format('d M Y').' – '.$to->format('d M Y')],
      ['Payments', $payments->count()],
      ['Total', 'Rs. '.number_format($total,2)],
      ['Average Receipt', 'Rs. '.number_format($avg,2)],
    ],
  ])
  <div class="stat-strip"><div class="stat"><div class="num">{{ $payments->count() }}</div><div class="lbl">Payments</div></div><div class="stat"><div class="num">{{ number_format($total,0) }}</div><div class="lbl">Collection (Rs.)</div></div><div class="stat"><div class="num">{{ number_format($avg,0) }}</div><div class="lbl">Avg Receipt (Rs.)</div></div></div>
  <div class="section-title">Payment Detail</div>
  <table class="rpt"><thead><tr><th>#</th><th>Date / Time</th><th>Student</th><th>ID</th><th>Program / Semester</th><th>Voucher</th><th>Amount</th><th>Method</th><th>Payment Status</th><th>Reference</th><th>Received By</th></tr></thead><tbody>
    @forelse($payments as $i => $p)
    <tr><td class="ctr">{{ $i+1 }}</td><td class="ctr">{{ $p->paid_at?->format('d M Y h:i A') ?? '—' }}</td><td>{{ $p->student->name ?? $p->voucher->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $p->student ?? $p->voucher->student ?? null])</td><td>{{ $p->student->student_id ?? $p->voucher->student->student_id ?? '—' }}</td><td>{{ $p->student->program->name ?? $p->voucher->student->program->name ?? '—' }} / {{ $p->student->schoolClass->section_label ?? $p->voucher->student->schoolClass->section_label ?? '—' }}</td><td>{{ $p->voucher->voucher_no ?? ('Extra receipt CR-'.$p->id) }}</td><td class="num">{{ number_format($p->amount,2) }}</td><td class="ctr">{{ ucfirst(str_replace('_',' ', $p->method ?? '—')) }}</td><td class="ctr">{{ ucfirst($p->status ?? 'verified') }}</td><td>{{ $p->reference_no ?: '—' }}</td><td>{{ ($p->received_by ?? $p->recorded_by) ?: '—' }}</td></tr>
    @empty<tr><td colspan="11" class="ctr">No payments found for the selected filters.</td></tr>@endforelse
  </tbody>@if($payments->count())<tfoot><tr><td colspan="6" class="ctr">Total</td><td class="num">{{ number_format($total,2) }}</td><td colspan="4"></td></tr></tfoot>@endif</table>
  <div class="foot-row"><div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div><div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
  <div class="disclaimer">The report follows the selected payment-status filter; the default collection view includes verified payments only. System-generated — for internal administrative use.</div>
</div></div>
</body>
</html>
