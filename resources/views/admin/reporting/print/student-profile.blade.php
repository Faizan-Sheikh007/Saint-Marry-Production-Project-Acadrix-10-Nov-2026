<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Information Sheet — {{ $student->name }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('admin.reporting.partials.report-print-style')
<style>
  .photo-box {
    width:78px; height:96px; border:1.3px solid var(--line); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; text-align:center;
    font-size:9px; color:var(--muted); line-height:1.35; padding:4px;
  }
  .photo-box img { width:100%; height:100%; object-fit:cover; }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()"><i class="fas fa-download"></i> Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>

<div class="sheet">
<div class="sheet-inner">

  @php
    $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
    $instTagline = \App\Models\Setting::get('institution_tagline');
    $campusName  = \App\Models\Setting::get('campus_name');
    $logoPath    = \App\Models\Setting::get('institution_logo');
  @endphp

  <div class="letterhead">
    <div class="logo-wrap">
      @if($logoPath)
        <img src="{{ storage_asset($logoPath) }}" alt="Logo">
      @else
        <div class="logo-fallback">{{ strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $instName), 0, 2)) ?: 'IN' }}</div>
      @endif
    </div>
    <div class="center">
      <div class="inst-name">{{ $instName }}</div>
      @if($instTagline)<div class="inst-affil">{{ $instTagline }}</div>@endif
      @if($campusName)<div class="inst-address">{{ $campusName }}</div>@endif
      <div class="doc-name">Student Information Sheet</div>
      <div class="doc-sub">Personal, Academic &amp; Fee Summary</div>
    </div>
    <div class="photo-box">
      @if($student->photo)
        <img src="{{ storage_asset($student->photo) }}" alt="Photo">
      @else
        Paste your recent passport size photo
      @endif
    </div>
  </div>

  <hr class="rule">

  <div class="meta-grid" style="grid-template-columns:1fr 1fr">
    <div class="row"><span class="lbl">Student ID</span><span class="val">{{ $student->student_id ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Name</span><span class="val">{{ $student->name }}</span></div>

    <div class="row"><span class="lbl">Father's Name</span><span class="val">{{ $student->father_name ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Program</span><span class="val">{{ $student->program->name ?? '—' }}</span></div>

    <div class="row"><span class="lbl">Gender</span><span class="val">{{ ucfirst($student->gender ?: '—') }}</span></div>
    <div class="row"><span class="lbl">Session</span><span class="val">{{ $student->session ?: ($student->academicYear->name ?? '—') }}</span></div>

    <div class="row"><span class="lbl">Date of Birth</span><span class="val">{{ $student->date_of_birth?->format('d M Y') ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Admission Date</span><span class="val">{{ $student->admission_date?->format('d M Y') ?: '—' }}</span></div>

    <div class="row"><span class="lbl">Cell #</span><span class="val">{{ $student->phone ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Guardian Phone</span><span class="val">{{ $student->guardian_phone ?: '—' }}</span></div>

    <div class="row"><span class="lbl">CNIC / ID</span><span class="val">{{ $student->cnic ?: '—' }}</span></div>
    <div class="row"><span class="lbl">Student Status</span><span class="val">{{ $student->statusLabel() }}</span></div>

    <div class="row" style="grid-column:1 / -1"><span class="lbl">Address</span><span class="val">{{ $student->address ?: '—' }}</span></div>
    <div class="row" style="grid-column:1 / -1"><span class="lbl">Permanent Address</span><span class="val">{{ $student->permanent_address ?: '—' }}</span></div>
  </div>

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $attendancePct }}%</div><div class="lbl">Attendance</div></div>
    <div class="stat"><div class="num">{{ number_format($student->totalPaid(), 0) }}</div><div class="lbl">Total Paid (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($student->pendingDues(), 0) }}</div><div class="lbl">Pending Dues (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($reliefSummary['scholarship'], 0) }}</div><div class="lbl">Scholarship (Rs.)</div></div>
    <div class="stat"><div class="num">{{ number_format($reliefSummary['financial_aid'], 0) }}</div><div class="lbl">Financial Aid (Rs.)</div></div>
  </div>

  @if($reliefPlans->count())
  <div class="section-title">Scholarship &amp; Financial Aid</div>
  <table class="rpt">
    <thead><tr><th>Fee Plan</th><th>Cycle</th><th>Scholarship</th><th>Financial Aid</th><th>Total Relief</th></tr></thead>
    <tbody>
      @foreach($reliefPlans as $plan)
      <tr>
        <td>{{ $plan->feeTemplate->name ?? 'Fee Plan' }}</td>
        <td class="ctr">{{ $plan->cycleRangeLabel() }}</td>
        <td class="num">{{ number_format($plan->scholarship_amount, 0) }}</td>
        <td class="num">{{ number_format($plan->financial_aid_amount, 0) }}</td>
        <td class="num">{{ number_format((float)$plan->scholarship_amount + (float)$plan->financial_aid_amount, 0) }}</td>
      </tr>
      @endforeach
    </tbody>
    <tfoot><tr><td colspan="2" class="ctr">Total Relief</td><td class="num">{{ number_format($reliefSummary['scholarship'], 0) }}</td><td class="num">{{ number_format($reliefSummary['financial_aid'], 0) }}</td><td class="num">{{ number_format($reliefSummary['total'], 0) }}</td></tr></tfoot>
  </table>
  @endif

  @if($fee['previous_balance']->count() || $fee['active']->count() || $fee['upcoming']->count())
  <div class="section-title">Outstanding Vouchers</div>
  <table class="rpt">
    <thead><tr><th>Voucher No</th><th>Title</th><th>Due Date</th><th>Balance</th></tr></thead>
    <tbody>
      @foreach($fee['previous_balance']->concat($fee['active'])->concat($fee['upcoming']) as $v)
      <tr>
        <td class="ctr">{{ $v->voucher_no }}</td>
        <td>{{ $v->title }}</td>
        <td class="ctr">{{ $v->due_date?->format('d M Y') }}</td>
        <td class="num">{{ number_format($v->balance(), 0) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  @if($fee['previous_paid']->count())
  <div class="section-title">Recent Paid Vouchers</div>
  <table class="rpt">
    <thead><tr><th>Voucher No</th><th>Title</th><th>Due Date</th><th>Paid Amount</th></tr></thead>
    <tbody>
      @foreach($fee['previous_paid']->take(6) as $v)
      <tr>
        <td class="ctr">{{ $v->voucher_no }}</td>
        <td>{{ $v->title }}</td>
        <td class="ctr">{{ $v->due_date?->format('d M Y') }}</td>
        <td class="num">{{ number_format($v->paid_amount, 0) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>

  <div class="disclaimer">This is a system-generated report from the Reporting Center — for internal administrative use only.</div>

</div>
</div>

</body>
</html>
