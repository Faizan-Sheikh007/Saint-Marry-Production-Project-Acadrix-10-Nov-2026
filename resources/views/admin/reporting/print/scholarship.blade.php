<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Scholarship &amp; Financial Aid Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

@php
  $periodLabel = match($period) {
    'monthly' => 'Monthly — '.$from->format('F Y'),
    'yearly'  => 'Yearly — '.$from->format('Y'),
    'range'   => 'Custom Range — '.$from->format('d M Y').' to '.$to->format('d M Y'),
    default   => 'All Time',
  };
@endphp

<div class="sheet"><div class="sheet-inner">
  @include('admin.reporting.partials.report-letterhead', [
    'reportTitle' => 'Scholarship & Financial Aid Report',
    'reportSubtitle' => ($program->name ?? 'All Programs').' — '.$periodLabel.($search ? ' — Student: '.$search : ''),
    'metaLines' => [
      ['Program', $program->name ?? 'Overall (All Programs)'],
      ['Period', $periodLabel],
      ['Student Filter', $search ?: 'All Students'],
      ['Students with Relief', $summary['students']],
      ['Total Scholarship', 'Rs. '.number_format($summary['total_scholarship'], 0)],
      ['Total Financial Aid', 'Rs. '.number_format($summary['total_financial_aid'], 0)],
      ['Combined Relief', 'Rs. '.number_format($summary['total_relief'], 0)],
    ],
  ])

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $summary['students'] }}</div><div class="lbl">Students</div></div>
    <div class="stat"><div class="num">{{ number_format($summary['total_scholarship'], 0) }}</div><div class="lbl">Scholarship (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($summary['total_financial_aid'], 0) }}</div><div class="lbl">Financial Aid (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($summary['total_relief'], 0) }}</div><div class="lbl">Total Relief (Rs.)</div></div>
  </div>

  <div class="section-title">Student Scholarship &amp; Financial Aid Detail</div>
  <table class="rpt">
    <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Program</th><th>Semester/Part</th><th>Cycle</th><th>Net Payable</th><th>Scholarship</th><th>Financial Aid</th><th>Total Relief</th><th>Status</th></tr></thead>
    <tbody>
      @forelse($rows as $i => $r)
      <tr>
        <td class="ctr">{{ $i + 1 }}</td>
        <td class="ctr">{{ $r['student']->student_id ?? '—' }}</td>
        <td>{{ $r['student']->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $r['student']])</td>
        <td>{{ $r['program'] }}</td>
        <td class="ctr">{{ $r['student']->schoolClass->name ?? '—' }}</td>
        <td class="ctr">{{ $r['cycle'] }}</td>
        <td class="num">{{ !is_null($r['total']) ? number_format($r['total'], 0) : '—' }}</td>
        <td class="num">{{ $r['status'] === 'Allocated' ? number_format($r['scholarship_amount'], 0) : '—' }}</td>
        <td class="num">{{ $r['status'] === 'Allocated' ? number_format($r['financial_aid_amount'], 0) : '—' }}</td>
        <td class="num">{{ $r['status'] === 'Allocated' ? number_format($r['relief_total'], 0) : '—' }}</td>
        <td class="ctr">{{ $r['status'] }}</td>
      </tr>
      @empty
      <tr><td colspan="11" class="ctr">No scholarship or financial-aid records found for the selected filters.</td></tr>
      @endforelse
    </tbody>
    @if($rows->count())
    <tfoot>
      <tr><td colspan="7" class="ctr">Totals</td><td class="num">{{ number_format($summary['total_scholarship'], 0) }}</td><td class="num">{{ number_format($summary['total_financial_aid'], 0) }}</td><td class="num">{{ number_format($summary['total_relief'], 0) }}</td><td></td></tr>
    </tfoot>
    @endif
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>
  <div class="disclaimer">This is a system-generated report from the Reporting Center — for internal administrative use only.</div>
</div></div>
</body>
</html>
