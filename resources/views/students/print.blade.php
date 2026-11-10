<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students Roster</title>
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

  @php
    $instName    = \App\Models\Setting::get('institution_name', 'Acadrix Institute');
    $instTagline = \App\Models\Setting::get('institution_tagline');
    $campusName  = \App\Models\Setting::get('campus_name');
    $logoPath    = \App\Models\Setting::get('institution_logo');

    $activeCount = $students->where('status', 'active')->count();
    $unpaidCount = $students->filter(fn($student) => $student->currentFeeStatus() === 'unpaid')->count();
    $totalDues   = $students->sum(fn($s) => $s->pendingDues());
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
      <div class="doc-name">Students Roster</div>
      <div class="doc-sub">Enrollment &amp; Fee Status Summary</div>
    </div>
    <div class="spacer"></div>
  </div>

  <hr class="rule">

  <div class="meta-grid">
    <div class="row"><span class="lbl">Session</span><span class="val">{{ $filters['session'] ?? 'All Sessions' }}</span></div>
    <div class="row"><span class="lbl">Program</span><span class="val">{{ $filters['program'] ?? 'All Programs' }}</span></div>
    <div class="row"><span class="lbl">Semester / Part</span><span class="val">{{ $filters['class'] ?? 'All Semester / Parts' }}</span></div>
    <div class="row"><span class="lbl">Status</span><span class="val">{{ $filters['status'] ?? 'All' }}</span></div>
    @if($filters['search'] ?? null)
    <div class="row" style="grid-column:1 / -1"><span class="lbl">Search</span><span class="val">{{ $filters['search'] }}</span></div>
    @endif
  </div>

  <div class="stat-strip">
    <div class="stat"><div class="num">{{ $students->count() }}</div><div class="lbl">Total Students</div></div>
    <div class="stat"><div class="num">{{ $activeCount }}</div><div class="lbl">Active</div></div>
    <div class="stat"><div class="num">{{ $unpaidCount }}</div><div class="lbl">Unpaid</div></div>
    <div class="stat"><div class="num">{{ number_format($totalDues, 0) }}</div><div class="lbl">Pending Dues (Rs.)</div></div>
  </div>

  <table class="rpt">
    <thead>
      <tr>
        <th>Sr. No.</th>
        <th>Student ID</th>
        <th>Roll Number</th>
        <th>Name</th>
        <th>Father Name</th>
        <th>Program</th>
        <th>Contact</th>
        <th>Pending Balance</th>
        <th>Fees Status</th><th>Student Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $student)
      <tr>
        <td class="ctr">{{ $loop->iteration }}</td>
        <td class="ctr" style="white-space:nowrap;font-weight:700">{{ $student->student_id ?: '—' }}</td>
        <td class="ctr">{{ $student->roll_number ?: '—' }}</td>
        <td><div style="font-weight:700">{{ $student->name }}</div></td>
        <td>{{ $student->father_name ?: ($student->guardian_name ?: '—') }}</td>
        <td>
          {{ $student->program->name ?? '—' }}
          <div style="font-size:9.5px;color:var(--muted)">{{ $student->schoolClass->name ?? '—' }}</div>
        </td>
        <td class="ctr">{{ $student->phone ?: '—' }}</td>
        <td class="num">Rs.{{ number_format($student->pendingDues(), 0) }}</td>
        <td class="ctr">{{ ucfirst($student->currentFeeStatus()) }}</td><td class="ctr">{{ $student->statusLabel() }}</td>
      </tr>
      @empty
      <tr><td colspan="10" class="ctr" style="padding:20px;color:var(--muted)">No students found.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="7" class="ctr">Totals</td>
        <td class="num">Rs.{{ number_format($totalDues, 0) }}</td>
        <td></td><td></td>
      </tr>
    </tfoot>
  </table>

  <div class="foot-row">
    <div class="sign-block"><div class="sign-line">Prepared By</div></div>
    <div class="gen-date">Generated on {{ now()->format('d M Y, h:i A') }}</div>
    <div class="sign-block"><div class="sign-line">Principal / Head of Institution</div></div>
  </div>

  <div class="disclaimer">This is a system-generated report — for internal administrative use only.</div>

</div>
</div>

</body>
</html>
