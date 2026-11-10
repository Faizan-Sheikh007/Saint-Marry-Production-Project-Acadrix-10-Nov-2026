<!doctype html>
<html><head><meta charset="utf-8"><title>Student Status Report</title>
<style>
@page{size:A4 landscape;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;font-size:10px;margin:0}.head{text-align:center;border-bottom:2px solid #8b0c22;padding-bottom:8px;margin-bottom:10px}.head h1{font-size:18px;margin:0 0 4px;color:#8b0c22}.meta{display:flex;justify-content:space-between;margin:7px 0 12px;font-size:9px}.summary{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}.pill{border:1px solid #bbb;padding:4px 7px;border-radius:4px}.pill strong{color:#8b0c22}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:5px 6px;text-align:left;vertical-align:top}th{background:#eee;font-size:9px}.status{font-weight:bold;text-transform:capitalize}.no-print{margin-bottom:10px}@media print{.no-print{display:none}}
</style></head><body>
<div class="no-print"><button onclick="window.print()">Print / Save PDF</button></div>
<div class="head"><h1>Student Status Report</h1><div>{{ \App\Models\Setting::get('institution_name','Institution') }}</div></div>
<div class="meta"><div><strong>Status:</strong> {{ $selectedStatus ? \App\Support\StudentStatus::label($selectedStatus) : 'All Statuses' }}</div><div><strong>Print Date:</strong> {{ now()->format('d M Y, h:i A') }}</div></div>
<div class="summary">@foreach(\App\Support\StudentStatus::VALUES as $status)<div class="pill">{{ \App\Support\StudentStatus::label($status) }}: <strong>{{ $statusCounts[$status] ?? 0 }}</strong></div>@endforeach</div>
<table><thead><tr><th>#</th><th>Student ID</th><th>Student</th><th>Father Name</th><th>Program</th><th>Semester/Part</th><th>Academic Year</th><th>Phone</th><th>Admission Date</th><th>Status</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $loop->iteration }}</td><td>{{ $student->student_id ?: '—' }}</td><td><strong>{{ $student->name }}</strong></td><td>{{ $student->father_name ?: '—' }}</td><td>{{ $student->program->name ?? '—' }}</td><td>{{ $student->schoolClass->section_label ?? '—' }}</td><td>{{ $student->academicYear->name ?? '—' }}</td><td>{{ $student->phone ?: '—' }}</td><td>{{ $student->admission_date?->format('d M Y') ?? '—' }}</td><td class="status">{{ $student->statusLabel() }}</td></tr>@empty<tr><td colspan="10" style="text-align:center;padding:20px">No students found.</td></tr>@endforelse
</tbody></table>
<script>window.addEventListener('load',()=>{ if(new URLSearchParams(location.search).get('autoprint')==='1') window.print(); });</script>
</body></html>
