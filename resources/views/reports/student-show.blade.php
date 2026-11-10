@extends('layouts.app')
@section('title', 'Student Report - '.$student->name)
@section('page_title', 'Student Report')
@section('content')
@php
  $accountsReport = auth('accounts')->check();
  $listRoute = $accountsReport ? 'accounts.reports.students' : 'admin.reports.students';
  $printRoute = $accountsReport ? 'accounts.reports.students.print' : 'admin.reports.students.print';
  $pdfRoute = $accountsReport ? 'accounts.reports.students.pdf' : 'admin.reports.students.pdf';
  $remarkStore = $accountsReport ? 'accounts.reports.students.remarks.store' : 'admin.reports.students.remarks.store';
  $remarkDestroy = $accountsReport ? 'accounts.reports.students.remarks.destroy' : 'admin.reports.students.remarks.destroy';
  $attendanceStudentRoute = $accountsReport ? 'accounts.reports.attendance.student' : 'admin.reports.attendance.student';
@endphp

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:20px">
  <div style="display:flex;gap:12px;align-items:center">
    <a href="{{ route($listRoute, request()->only(['search','program_id','class_id','academic_year_id','status','fee_status'])) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    <div>
      <div style="font-size:23px;font-weight:850">{{ $student->name }}</div>
      <div style="font-size:12px;color:var(--text-muted)">{{ $student->student_id }} • {{ $student->program->name ?? 'No Program' }} • {{ $student->schoolClass->section_label ?? 'No Semester/Part' }}</div>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="{{ route($attendanceStudentRoute, array_merge(['student'=>$student], request()->only(['from','to']))) }}" class="btn btn-outline"><i class="fas fa-calendar-check"></i> Attendance Detail</a>
    <a href="{{ route($printRoute, array_merge(['student'=>$student], request()->query())) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Printable View</a>
    <a href="{{ route($pdfRoute, array_merge(['student'=>$student], request()->query())) }}" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download PDF</a>
  </div>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-body" style="padding:14px 18px">
    <form method="GET" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap" data-ajax-filter>
      <div class="form-group" style="margin:0"><label class="form-label">From</label><input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="form-control"></div>
      <div class="form-group" style="margin:0"><label class="form-label">To</label><input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="form-control"></div>
      <div class="form-group" style="margin:0;min-width:150px"><label class="form-label">Exam Type</label>
        <select name="exam_type" class="form-control"><option value="">All Exams</option>
          @foreach(['midterm'=>'Mid Term','final'=>'Final','quiz'=>'Quiz','unit_test'=>'Unit Test'] as $v=>$l)<option value="{{ $v }}" {{ request('exam_type')===$v?'selected':'' }}>{{ $l }}</option>@endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0;min-width:180px"><label class="form-label">Subject</label>
        <select name="subject_id" class="form-control"><option value="">All Subjects</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" {{ request('subject_id')==$subject->id?'selected':'' }}>{{ $subject->name }}</option>@endforeach</select>
      </div>
      <button class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
      <a href="{{ route($accountsReport ? 'accounts.reports.students.show' : 'admin.reports.students.show', $student) }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:18px;margin-bottom:18px">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-id-card"></i> Student Information</div></div>
    <div class="card-body" style="padding:18px">
      <div style="display:flex;gap:18px;align-items:flex-start">
        @if($student->photo)
          <img src="{{ storage_asset($student->photo) }}" style="width:86px;height:100px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
        @else
          <div style="width:86px;height:100px;border-radius:10px;background:#f3f0ff;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:850">{{ strtoupper(substr($student->name,0,2)) }}</div>
        @endif
        <div style="flex:1;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 20px;font-size:13px">
          @foreach([
            ['Student ID',$student->student_id],['Roll No',$student->roll_number],
            ['Program',$student->program->name ?? '—'],['Semester / Part',$student->schoolClass->section_label ?? '—'],
            ['Academic Year',$student->academicYear->name ?? '—'],['Admission',$student->admission_date?->format('d M Y') ?? '—'],
            ["Father's Name",$student->father_name ?? '—'],['Guardian',$student->guardian_name ?? '—'],
            ['Phone',$student->phone ?? '—'],['Guardian Phone',$student->guardian_phone ?? '—'],
            ['Gender',ucfirst($student->gender ?? '—')],['Student Status',$student->statusLabel()],
          ] as [$label,$value])
            <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);font-weight:700">{{ $label }}</div><div style="font-weight:650;margin-top:2px">{{ $value }}</div></div>
          @endforeach
          <div style="grid-column:1/-1"><div style="font-size:10px;text-transform:uppercase;color:var(--text-muted);font-weight:700">Address</div><div style="font-weight:600;margin-top:2px">{{ $student->address ?: '—' }}</div></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-gauge-high"></i> Report Snapshot</div></div>
    <div class="card-body" style="padding:18px;display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
      @php $attColor=$attendanceSummary['percentage']>=90?'var(--success)':($attendanceSummary['percentage']>=75?'var(--warning)':'var(--danger)'); @endphp
      <div style="padding:14px;border:1px solid var(--border);border-radius:10px"><div style="font-size:22px;font-weight:850;color:{{ $attColor }}">{{ $attendanceSummary['percentage'] }}%</div><div style="font-size:11px;color:var(--text-muted)">Attendance</div></div>
      <div style="padding:14px;border:1px solid var(--border);border-radius:10px"><div style="font-size:22px;font-weight:850;color:var(--primary)">{{ $examSummary['average'] }}%</div><div style="font-size:11px;color:var(--text-muted)">Exam Average</div></div>
      <div style="padding:14px;border:1px solid var(--border);border-radius:10px"><div style="font-size:20px;font-weight:850;color:var(--success)">Rs. {{ number_format($feeSummary['paid'],0) }}</div><div style="font-size:11px;color:var(--text-muted)">Paid Fee</div></div>
      <div style="padding:14px;border:1px solid var(--border);border-radius:10px"><div style="font-size:20px;font-weight:850;color:{{ $feeSummary['pending']>0?'var(--danger)':'var(--success)' }}">Rs. {{ number_format($feeSummary['pending'],0) }}</div><div style="font-size:11px;color:var(--text-muted)">Pending Fee</div></div>
      <div style="grid-column:1/-1;padding:12px 14px;border-radius:10px;background:#f8f8fa;display:flex;justify-content:space-between;gap:10px"><span style="font-size:12px;color:var(--text-muted)">Fee Status</span><strong>{{ $feeSummary['status'] }}</strong></div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:12px;margin-bottom:18px">
  @foreach([
    ['Present',$attendanceSummary['present'],'var(--success)'],['Absent',$attendanceSummary['absent'],'var(--danger)'],['Late',$attendanceSummary['late'],'var(--warning)'],['Leave',$attendanceSummary['leave'],'#6366f1'],['Total Attendance',$attendanceSummary['total'],'var(--primary)']
  ] as [$label,$value,$color])
    <div class="stat-card"><div><div style="font-size:21px;font-weight:850;color:{{ $color }}">{{ $value }}</div><div style="font-size:11px;color:var(--text-muted)">{{ $label }}</div></div></div>
  @endforeach
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center"><div class="card-title"><i class="fas fa-chart-column"></i> Multiple Exam Results</div><div style="font-size:12px;color:var(--text-muted)">{{ $examSummary['count'] }} result(s) • Highest {{ $examSummary['highest'] }}% • Passed {{ $examSummary['passed'] }}</div></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Exam</th><th>Subject</th><th>Type</th><th>Teacher</th><th>Marks</th><th>%</th><th>Grade</th><th>Result / Teacher Remark</th></tr></thead>
    <tbody>
      @forelse($examResults as $result)
        @php $pass=(float)$result->marks_obtained >= (float)($result->examination?->passing_marks ?? 0); @endphp
        <tr>
          <td>{{ $result->examination?->exam_date?->format('d M Y') ?? '—' }}</td>
          <td style="font-weight:700">{{ $result->examination?->name ?? '—' }}</td>
          <td>{{ $result->examination?->subject?->name ?? '—' }}</td>
          <td>{{ $result->examination?->type_label ?? '—' }}</td>
          <td>{{ $result->gradedBy?->name ?? $result->examination?->teacher?->name ?? '—' }}</td>
          <td>{{ number_format((float)$result->marks_obtained,1) }} / {{ $result->examination?->total_marks ?? 0 }}</td>
          <td style="font-weight:800">{{ number_format((float)$result->percentage,1) }}%</td>
          <td><span class="badge {{ $pass?'success':'danger' }}">{{ $result->grade ?: ($pass?'Pass':'Fail') }}</span></td>
          <td style="min-width:180px">{{ $result->remarks ?: '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">No exam results for the selected filters.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:18px;margin-bottom:18px">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-money-check-dollar"></i> Fee Position</div></div>
    <div class="card-body" style="padding:16px">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px">
        @foreach([
          ['Charged',$feeSummary['charged'],'var(--primary)'],['Paid',$feeSummary['paid'],'var(--success)'],['Pending',$feeSummary['pending'],'var(--danger)'],['Overdue',$feeSummary['overdue'],'var(--warning)']
        ] as [$label,$value,$color])<div style="padding:12px;border:1px solid var(--border);border-radius:9px"><div style="font-size:17px;font-weight:850;color:{{ $color }}">Rs. {{ number_format($value,0) }}</div><div style="font-size:10px;color:var(--text-muted)">{{ $label }}</div></div>@endforeach
      </div>
      <div class="table-wrap"><table>
        <thead><tr><th>Voucher</th><th>Title</th><th>Due</th><th>Payable</th><th>Paid</th><th>Pending</th><th>Status</th></tr></thead>
        <tbody>@forelse($pendingVouchers as $voucher)<tr>
          <td>{{ $voucher->voucher_no }}</td><td>{{ $voucher->title }}</td><td>{{ $voucher->due_date?->format('d M Y') ?? '—' }}</td><td>Rs. {{ number_format($voucher->total(),0) }}</td><td>Rs. {{ number_format($voucher->paid_amount,0) }}</td><td style="font-weight:800;color:var(--danger)">Rs. {{ number_format($voucher->balance(),0) }}</td><td><span class="badge {{ $voucher->isOverdue()?'danger':'warning' }}">{{ $voucher->autoStatusLabel() }}</span></td>
        </tr>@empty<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)">No pending vouchers. Fee account is clear.</td></tr>@endforelse</tbody>
      </table></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap"></i> Scholarship / Aid & Payments</div></div>
    <div class="card-body" style="padding:16px">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">
        <div><div style="font-weight:850;color:var(--success)">Rs. {{ number_format($reliefSummary['scholarship'],0) }}</div><div style="font-size:10px;color:var(--text-muted)">Scholarship</div></div>
        <div><div style="font-weight:850;color:var(--warning)">Rs. {{ number_format($reliefSummary['financial_aid'],0) }}</div><div style="font-size:10px;color:var(--text-muted)">Financial Aid</div></div>
        <div><div style="font-weight:850;color:var(--primary)">Rs. {{ number_format($reliefSummary['total'],0) }}</div><div style="font-size:10px;color:var(--text-muted)">Total Relief</div></div>
      </div>
      <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px">Recent Payments</div>
      @forelse($recentPayments as $payment)
        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:12px"><div><strong>{{ $payment->paid_at?->format('d M Y') }}</strong><div style="font-size:10px;color:var(--text-muted)">{{ $payment->voucher?->voucher_no }} • {{ ucfirst(str_replace('_',' ',$payment->method)) }}</div></div><div style="font-weight:850;color:var(--success)">Rs. {{ number_format($payment->amount,0) }}</div></div>
      @empty<div style="font-size:12px;color:var(--text-muted)">No payments found.</div>@endforelse
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center"><div class="card-title"><i class="fas fa-comment-dots"></i> Teacher / Academic Remarks</div><div style="font-size:11px;color:var(--text-muted)">Manual remarks become part of the student report and PDF.</div></div>
  <div class="card-body" style="padding:18px">
    <form method="POST" action="{{ route($remarkStore, $student) }}" style="display:grid;grid-template-columns:1fr 1fr 160px;gap:10px;margin-bottom:16px">
      @csrf
      <div><label class="form-label">Teacher / Staff Name</label><input class="form-control" name="teacher_name" placeholder="e.g. Dr. Ahmed / Class Teacher"></div>
      <div><label class="form-label">Term / Context</label><input class="form-control" name="term" placeholder="e.g. Semester 1, Mid Term"></div>
      <div><label class="form-label">Remark Date</label><input type="date" class="form-control" name="remark_date" value="{{ now()->format('Y-m-d') }}"></div>
      <div style="grid-column:1/-1"><label class="form-label">Remark</label><textarea class="form-control" name="remarks" rows="3" maxlength="3000" required placeholder="Enter academic progress, conduct, strengths, concerns, action points or teacher feedback..."></textarea></div>
      <div style="grid-column:1/-1"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Remark</button></div>
    </form>

    @forelse($remarks as $remark)
      <div style="padding:13px 0;border-top:1px solid var(--border);display:flex;justify-content:space-between;gap:14px;align-items:flex-start">
        <div><div style="font-size:12px;font-weight:800">{{ $remark->teacher_name ?: 'Teacher / Academic Office' }} @if($remark->term)<span style="font-weight:500;color:var(--text-muted)">• {{ $remark->term }}</span>@endif</div><div style="font-size:11px;color:var(--text-muted);margin:2px 0 6px">{{ $remark->remark_date?->format('d M Y') ?? $remark->created_at?->format('d M Y') }}</div><div style="font-size:13px;line-height:1.55">{{ $remark->remarks }}</div></div>
        <form method="POST" action="{{ route($remarkDestroy, ['student'=>$student,'remark'=>$remark]) }}" onsubmit="return confirm('Remove this remark from the report?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" title="Delete remark"><i class="fas fa-trash"></i></button></form>
      </div>
    @empty
      <div style="padding:18px;text-align:center;color:var(--text-muted);font-size:12px;border-top:1px solid var(--border)">No manual teacher remarks yet.</div>
    @endforelse
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-days"></i> Recent Attendance Records</div></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Subject</th><th>Status</th><th>Attendance Remark</th></tr></thead>
    <tbody>@forelse($attendanceRecords->take(25) as $record)<tr><td>{{ $record->date?->format('d M Y') }}</td><td>{{ $record->subject?->name ?? 'General' }}</td><td><span class="badge {{ $record->status==='present'?'success':($record->status==='absent'?'danger':'warning') }}">{{ ucfirst($record->status) }}</span></td><td>{{ $record->remarks ?: '—' }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;padding:28px;color:var(--text-muted)">No attendance records found.</td></tr>@endforelse</tbody>
  </table></div>
</div>
@endsection
