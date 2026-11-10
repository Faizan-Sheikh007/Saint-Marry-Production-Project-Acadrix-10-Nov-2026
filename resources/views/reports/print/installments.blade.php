<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Installment-wise Pending Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar"><button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button><a href="javascript:history.back()" class="secondary">Back</a></div>
@php $overdueTotal = $vouchers->filter(fn($v) => $v->due_date && $v->due_date->lt(today()))->sum(fn($v) => $v->balance()); @endphp
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Installment-wise Pending Report',
    'reportSubtitle' => ($from ? $from->format('d M Y') : 'Any due date').' – '.($to ? $to->format('d M Y') : 'Any due date'),
    'metaLines' => [
      ['Pending Installments', $vouchers->count()],
      ['Total Pending', 'Rs. '.number_format($totalPending,2)],
      ['Overdue Pending', 'Rs. '.number_format($overdueTotal,2)],
    ],
  ])
  <div class="stat-strip"><div class="stat"><div class="num">{{ $vouchers->count() }}</div><div class="lbl">Installments</div></div><div class="stat"><div class="num">{{ number_format($totalPending,0) }}</div><div class="lbl">Pending (Rs.)</div></div><div class="stat"><div class="num">{{ number_format($overdueTotal,0) }}</div><div class="lbl">Overdue (Rs.)</div></div></div>
  <div class="section-title">Pending Installments</div>
  <table class="rpt"><thead><tr><th>#</th><th>Student</th><th>ID</th><th>Program / Semester</th><th>Voucher</th><th>Installment</th><th>Payable</th><th>Paid</th><th>Pending</th><th>Due Date</th><th>Due / Overdue</th><th>Voucher Status</th></tr></thead><tbody>
    @forelse($vouchers as $i => $v)
      @php $isOver = $v->due_date?->lt(today()) ?? false; $days = $v->due_date ? abs((int) today()->diffInDays($v->due_date, false)) : 0; @endphp
      <tr><td class="ctr">{{ $i+1 }}</td><td>{{ $v->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $v->student])</td><td>{{ $v->student->student_id ?? '—' }}</td><td>{{ $v->student->program->name ?? '—' }} / {{ $v->student->schoolClass->section_label ?? '—' }}</td><td>{{ $v->voucher_no }}</td><td class="ctr">{{ $v->installment_no }} / {{ $v->installment_total }}</td><td class="num">{{ number_format($v->total(),2) }}</td><td class="num">{{ number_format($v->paid_amount,2) }}</td><td class="num">{{ number_format($v->balance(),2) }}</td><td class="ctr">{{ $v->due_date?->format('d M Y') ?? '—' }}</td><td class="ctr">{{ $v->due_date ? ($isOver ? $days.' days overdue' : $days.' days left') : '—' }}</td><td class="ctr">{{ $v->autoStatusLabel() }}</td></tr>
    @empty<tr><td colspan="12" class="ctr">No pending installments match the filters.</td></tr>@endforelse
  </tbody>@if($vouchers->count())<tfoot><tr><td colspan="8" class="ctr">Total Pending</td><td class="num">{{ number_format($totalPending,2) }}</td><td colspan="3"></td></tr></tfoot>@endif</table>
  <div class="foot-row"><div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div><div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
  <div class="disclaimer">System-generated pending installment report — for internal administrative use only.</div>
</div></div>
</body>
</html>
