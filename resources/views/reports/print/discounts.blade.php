<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Scholarship &amp; Financial Aid Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>
<div class="toolbar"><button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button><a href="javascript:history.back()" class="secondary">Back</a></div>
@php $studentCount = $plans->pluck('student_id')->unique()->count(); @endphp
<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Scholarship & Financial Aid Report',
    'reportSubtitle' => ($search ? 'Student filter: '.$search.' — ' : '').'Generated '.now()->format('d M Y'),
    'metaLines' => [
      ['Students', $studentCount],
      ['Scholarship', 'Rs. '.number_format($totalScholarship,0)],
      ['Financial Aid', 'Rs. '.number_format($totalFinancialAid,0)],
      ['Booked Relief', 'Rs. '.number_format($totalRelief,0)],
    ],
  ])
  <div class="stat-strip"><div class="stat"><div class="num">{{ $studentCount }}</div><div class="lbl">Students</div></div><div class="stat"><div class="num">{{ number_format($totalScholarship,0) }}</div><div class="lbl">Scholarship (Rs.)</div></div><div class="stat"><div class="num">{{ number_format($totalFinancialAid,0) }}</div><div class="lbl">Financial Aid (Rs.)</div></div><div class="stat"><div class="num">{{ number_format($totalRelief,0) }}</div><div class="lbl">Booked Relief (Rs.)</div></div></div>
  <div class="section-title">Student Relief Detail</div>
  <table class="rpt"><thead><tr><th>#</th><th>Student / ID</th><th>Program / Semester</th><th>Cycle</th><th>Gross Fee</th><th>Scholarship</th><th>Financial Aid</th><th>Booked Relief</th><th>Net Payable</th><th>Paid</th><th>Pending</th><th>Fee Position</th></tr></thead><tbody>
    @forelse($plans as $i => $plan)
      @php $valid=$plan->vouchers->where('status','!=','cancelled'); $gross=(float)$valid->sum('amount'); $bookedRelief=(float)$valid->sum('discount_amount'); $paid=(float)$valid->sum('paid_amount'); $pending=(float)$valid->sum(fn($v)=>$v->balance()); $net=max(0,$gross-$bookedRelief); @endphp
      <tr><td class="ctr">{{ $i+1 }}</td><td>{{ $plan->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $plan->student])<br><small>{{ $plan->student->student_id ?? '—' }}</small></td><td>{{ $plan->student->program->name ?? '—' }} / {{ $plan->student->schoolClass->section_label ?? '—' }}</td><td class="ctr">{{ $plan->cycleRangeLabel() }}</td><td class="num">{{ number_format($gross,0) }}</td><td class="num">{{ number_format($plan->scholarship_amount,0) }}</td><td class="num">{{ number_format($plan->financial_aid_amount,0) }}</td><td class="num">{{ number_format($bookedRelief,0) }}</td><td class="num">{{ number_format($net,0) }}</td><td class="num">{{ number_format($paid,0) }}</td><td class="num">{{ number_format($pending,0) }}</td><td class="ctr">{{ $pending <= 0 ? 'Clear' : 'Pending' }}</td></tr>
    @empty<tr><td colspan="12" class="ctr">No scholarship or financial-aid records match the filters.</td></tr>@endforelse
  </tbody>@if($plans->count())<tfoot><tr><td colspan="5" class="ctr">Relief Totals</td><td class="num">{{ number_format($totalScholarship,0) }}</td><td class="num">{{ number_format($totalFinancialAid,0) }}</td><td class="num">{{ number_format($totalRelief,0) }}</td><td colspan="4"></td></tr></tfoot>@endif</table>
  <div class="foot-row"><div class="sign-block"><div class="sign-line">Prepared By (Accounts)</div></div><div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div><div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div></div>
  <div class="disclaimer">Scholarship and financial-aid settings come from fee assignments; gross, booked relief, net payable, paid and pending values come from linked non-cancelled vouchers. System-generated — for internal administrative use.</div>
</div></div>
</body>
</html>
