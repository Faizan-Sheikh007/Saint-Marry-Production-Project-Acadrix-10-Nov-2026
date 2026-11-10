<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Defaulter / Overdue List</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar"><button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button><a href="javascript:history.back()" class="secondary">Back</a></div>
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Defaulter / Overdue List',
    'reportSubtitle' => 'As of '.$asOf->format('d M Y'),
    'metaLines' => [
      ['Students in Default', $byStudent->count()],
      ['Overdue Vouchers', $vouchers->count()],
      ['Total Outstanding', 'Rs. '.number_format($totalOutstanding,2)],
    ],
  ])
  <div class="stat-strip"><div class="stat"><div class="num">{{ $byStudent->count() }}</div><div class="lbl">Students</div></div><div class="stat"><div class="num">{{ $vouchers->count() }}</div><div class="lbl">Overdue Vouchers</div></div><div class="stat"><div class="num">{{ number_format($totalOutstanding,0) }}</div><div class="lbl">Outstanding (Rs.)</div></div></div>
  <div class="section-title">Overdue Vouchers</div>
  <table class="rpt"><thead><tr><th>#</th><th>Student</th><th>ID</th><th>Program / Semester</th><th>Voucher / Title</th><th>Payable</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Days Overdue</th><th>Contact</th></tr></thead><tbody>
    @forelse($vouchers as $i => $v)
      @php $days = $v->due_date ? max(0, (int) $v->due_date->diffInDays($asOf, false)) : 0; @endphp
      <tr><td class="ctr">{{ $i+1 }}</td><td>{{ $v->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $v->student])</td><td>{{ $v->student->student_id ?? '—' }}</td><td>{{ $v->student->program->name ?? '—' }} / {{ $v->student->schoolClass->section_label ?? '—' }}</td><td><strong>{{ $v->voucher_no }}</strong><br>{{ $v->title }}</td><td class="num">{{ number_format($v->total(),2) }}</td><td class="num">{{ number_format($v->paid_amount,2) }}</td><td class="num">{{ number_format($v->balance(),2) }}</td><td class="ctr">{{ $v->due_date?->format('d M Y') ?? '—' }}</td><td class="ctr">{{ $days }}</td><td>{{ $v->student->guardian_phone ?: ($v->student->phone ?? '—') }}</td></tr>
    @empty<tr><td colspan="11" class="ctr">No overdue vouchers match the filters.</td></tr>@endforelse
  </tbody>@if($vouchers->count())<tfoot><tr><td colspan="7" class="ctr">Total Outstanding</td><td class="num">{{ number_format($totalOutstanding,2) }}</td><td colspan="3"></td></tr></tfoot>@endif</table>
  <div class="foot-row"><div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div><div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
  <div class="disclaimer">Balances are calculated as of the selected report date. System-generated — for internal administrative use only.</div>
</div></div>
</body>
</html>
