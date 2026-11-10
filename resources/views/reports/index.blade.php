@extends('layouts.app')
@section('title', 'Reports')
@section('page_title', 'Reports & Analytics')
@section('content')

@php
  $accountsReport = auth('accounts')->check();
  $aiSummaryRoute = $accountsReport ? 'accounts.reports.ai-summary' : 'admin.reports.ai-summary';

  $institutionFinancialReports = $accountsReport ? [
    ['accounts.reporting.scholarship','fa-graduation-cap','Scholarship & Financial Aid','Students on aid, by program & period, printable.'],
    ['accounts.reporting.fee-pending','fa-file-invoice-dollar','Pending Fee Report','Outstanding dues by student, program or overall.'],
    ['accounts.reporting.fee-paid','fa-circle-check','Paid Fee Report','Collected fees by student, program or overall.'],
  ] : [
    ['admin.reporting.scholarship','fa-graduation-cap','Scholarship & Financial Aid','Students on aid, by program & period, printable.'],
    ['admin.reporting.fee-pending','fa-file-invoice-dollar','Pending Fee Report','Outstanding dues by student, program or overall.'],
    ['admin.reporting.fee-paid','fa-circle-check','Paid Fee Report','Collected fees by student, program or overall.'],
  ];

  $studentAttendanceReports = $accountsReport ? [
    ['accounts.reporting.student-search','fa-magnifying-glass','Student Information Search','Find by cell #, name, father\'s name, address or program.'],
    ['accounts.reporting.attendance-summary','fa-clipboard-check','Attendance Report (Monthly/Yearly)','Attendance rate by student & program, printable.'],
    ['accounts.reports.students','fa-user-graduate','Student Directory Report','Browse & filter the full student list.'],
    ['accounts.reports.attendance','fa-calendar-check','Daily Attendance Records','Raw attendance records by class & date range.'],
  ] : [
    ['admin.reporting.student-search','fa-magnifying-glass','Student Information Search','Find by cell #, name, father\'s name, address or program.'],
    ['admin.reporting.attendance-summary','fa-clipboard-check','Attendance Report (Monthly/Yearly)','Attendance rate by student & program, printable.'],
    ['admin.reports.students','fa-user-graduate','Student Directory Report','Browse & filter the full student list.'],
    ['admin.reports.student-status','fa-user-tag','Student Status Report','Generate lists for dropped, terminated, left, active and other student statuses.'],
    ['admin.reports.attendance','fa-calendar-check','Daily Attendance Records','Raw attendance records by class & date range.'],
  ];
@endphp

{{-- ═══════════════════ SHARED INSTITUTION REPORTING CENTER ═══════════════════ --}}
<div style="border-radius:16px;padding:28px 30px;margin-bottom:24px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;position:relative;overflow:hidden">
  <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:.08"><i class="fas fa-chart-line"></i></div>
  <div style="position:relative">
    <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;font-weight:700">Reporting Center</div>
    <div style="font-size:24px;font-weight:800;margin-top:4px">Institution Reports &amp; Analytics</div>
    <div style="font-size:13px;opacity:.9;margin-top:6px;max-width:640px">Generate accurate, professional, printable reports — scholarships &amp; financial aid, fee collection, attendance, and student records — sliced monthly, yearly, or by program.</div>
  </div>
</div>

<div class="card" style="padding:20px 24px;margin-bottom:24px">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,12,34,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-robot"></i></div>
    <div style="flex:1;min-width:200px">
      <div style="font-weight:700;font-size:14.5px">AI Executive Summary</div>
      <div style="font-size:12px;color:var(--text-muted)">A rule-based read of this month's numbers, generated instantly — no external API.</div>
    </div>
    <button type="button" id="aiSummaryBtn" class="btn btn-primary btn-sm">
      <i class="fas fa-wand-magic-sparkles"></i> Generate
    </button>
  </div>
  <div id="aiSummaryOutput" style="display:none;margin-top:14px;padding-top:14px;border-top:1px dashed var(--border);font-size:13.5px;line-height:1.65"></div>
</div>
<script>
document.getElementById('aiSummaryBtn')?.addEventListener('click', function () {
  const btn = this, out = document.getElementById('aiSummaryOutput');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating…';
  fetch("{{ route($aiSummaryRoute) }}", {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
  })
    .then(res => { if (!res.ok) throw new Error('Request failed'); return res.json(); })
    .then(data => { out.style.display = 'block'; out.textContent = data.insight || 'No summary available right now.'; })
    .catch(() => { out.style.display = 'block'; out.textContent = 'Could not generate a summary right now — please try again.'; })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Regenerate'; });
});
</script>

<a href="{{ route($accountsReport ? 'accounts.reports.general' : 'admin.reports.general') }}" class="card" style="display:block;padding:19px 24px;margin-bottom:24px;text-decoration:none;color:inherit;border:1px solid var(--primary)">
  <strong><i class="fas fa-file-export"></i> General Student Report</strong>
  <div style="font-size:12px;color:var(--text-muted);margin-top:5px">Filter by program, semester or academic session; select individual columns; export to PDF, CSV or Excel.</div>
</a>
{{-- Quick stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:26px">
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:10px;background:rgba(139,12,34,.1);color:var(--primary);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-user-graduate"></i></div><div><div style="font-size:20px;font-weight:800">{{ $stats['students'] ?? 0 }}</div><div style="font-size:12px;color:var(--text-muted)">Total Students</div></div></div>
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:10px;background:rgba(59,130,246,.1);color:var(--info);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-sitemap"></i></div><div><div style="font-size:20px;font-weight:800">{{ $stats['programs'] ?? 0 }}</div><div style="font-size:12px;color:var(--text-muted)">Active Programs</div></div></div>
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:10px;background:rgba(200,151,58,.15);color:var(--gold,#c8973a);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-hand-holding-dollar"></i></div><div><div style="font-size:20px;font-weight:800">{{ $stats['aidedStudents'] ?? 0 }}</div><div style="font-size:12px;color:var(--text-muted)">Students on Financial Aid</div></div></div>
  <div class="stat-card"><div style="width:42px;height:42px;border-radius:10px;background:rgba(239,68,68,.1);color:var(--danger);display:flex;align-items:center;justify-content:center;margin-right:12px"><i class="fas fa-triangle-exclamation"></i></div><div><div style="font-size:20px;font-weight:800">{{ $stats['pendingVouchers'] ?? 0 }}</div><div style="font-size:12px;color:var(--text-muted)">Pending Vouchers</div></div></div>
</div>

<div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:12px">Financial &amp; Scholarship Reports</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin-bottom:28px">
  @foreach($institutionFinancialReports as [$route,$icon,$title,$desc])
  <a href="{{ route($route) }}" class="card" style="padding:20px;text-decoration:none;color:inherit;transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
    <div style="width:44px;height:44px;border-radius:10px;background:rgba(200,151,58,.12);display:flex;align-items:center;justify-content:center;color:var(--gold, #c8973a);margin-bottom:12px"><i class="fas {{ $icon }}"></i></div>
    <div style="font-weight:700;margin-bottom:4px">{{ $title }}</div>
    <div style="font-size:12px;color:var(--text-muted)">{{ $desc }}</div>
  </a>
  @endforeach
</div>

<div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:12px">Student &amp; Attendance Reports</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;{{ $accountsReport ? 'margin-bottom:28px' : '' }}">
  @foreach($studentAttendanceReports as [$route,$icon,$title,$desc])
  <a href="{{ route($route) }}" class="card" style="padding:20px;text-decoration:none;color:inherit;transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
    <div style="width:44px;height:44px;border-radius:10px;background:rgba(139,12,34,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);margin-bottom:12px"><i class="fas {{ $icon }}"></i></div>
    <div style="font-weight:700;margin-bottom:4px">{{ $title }}</div>
    <div style="font-size:12px;color:var(--text-muted)">{{ $desc }}</div>
  </a>
  @endforeach
</div>

@if($accountsReport)
{{-- Keep the Accounts-only operational financial reports as an additional section. --}}
<div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);margin-bottom:12px">Accounts Financial Reports</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">
  @foreach([
    ['reports.collection','fa-money-bill-wave','Collection Report','Daily / monthly fee collection.'],
    ['reports.defaulters','fa-triangle-exclamation','Defaulter / Overdue List','Students with pending or overdue dues.'],
    ['reports.class-revenue','fa-school','Class/Program-wise Revenue','Revenue breakdown by class or program.'],
    ['reports.installments','fa-layer-group','Installment-wise Pending','Pending installment vouchers.'],
    ['reports.discounts','fa-graduation-cap','Scholarship & Financial Aid Detail','Per-student scholarship and financial-aid relief.'],
    ['reports.income-expense','fa-chart-line','Income vs Expense','Verified fee income, ledger income, expenses and net — daily or monthly.'],
  ] as [$route,$icon,$title,$desc])
  <a href="{{ route($route) }}" class="card" style="padding:20px;text-decoration:none;color:inherit;transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
    <div style="width:44px;height:44px;border-radius:10px;background:rgba(139,12,34,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);margin-bottom:12px"><i class="fas {{ $icon }}"></i></div>
    <div style="font-weight:700;margin-bottom:4px">{{ $title }}</div>
    <div style="font-size:12px;color:var(--text-muted)">{{ $desc }}</div>
  </a>
  @endforeach
</div>
@endif
@endsection
