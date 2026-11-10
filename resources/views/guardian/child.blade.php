@extends('layouts.app')
@section('title', $student->name)
@section('page_title', $student->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('parent.dashboard') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:22px;font-weight:800">{{ $student->name }}</div>
</div>

{{-- Student Info Card --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:20px">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      @if($student->photo)
        <img src="{{ storage_asset($student->photo) }}"
             style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--border)">
      @else
        <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);
                    display:flex;align-items:center;justify-content:center;
                    font-weight:800;color:#fff;font-size:22px;flex-shrink:0">
          {{ strtoupper(substr($student->name, 0, 2)) }}
        </div>
      @endif
      <div style="flex:1">
        <div style="font-size:20px;font-weight:800">{{ $student->name }}</div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px;display:flex;gap:16px;flex-wrap:wrap">
          <span><i class="fas fa-id-card"></i> {{ $student->student_id }}</span>
          <span><i class="fas fa-graduation-cap"></i> {{ $student->program->name ?? '—' }}</span>
          <span><i class="fas fa-chalkboard"></i> {{ $student->schoolClass->name ?? '—' }}</span>
          <span><i class="fas fa-calendar"></i> {{ $student->academicYear->name ?? '—' }}</span>
          <span><i class="fas fa-list-ol"></i> Roll: {{ $student->roll_number }}</span>
          <span><i class="fas fa-venus-mars"></i> {{ ucfirst($student->gender ?? '—') }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Stats --}}
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  @php
    $avg  = $student->averageGrade();
    $fees = $student->pendingFees();
  @endphp
  @foreach([
    ['Attendance',    $attendance . '%',          $attendance >= 75 ? 'green' : ($attendance >= 50 ? 'orange' : 'red'), 'fas fa-calendar-check'],
    ['Avg Grade',     $avg . '%',                 $avg >= 70 ? 'green' : ($avg >= 50 ? 'orange' : 'red'),               'fas fa-star'],
    ['Pending Fees',  'Rs.' . number_format($fees), $fees > 0 ? 'red' : 'green',                                          'fas fa-money-bill'],
  ] as [$label, $value, $color, $icon])
  <div class="card" style="flex:1;min-width:140px">
    <div class="card-body" style="padding:20px;display:flex;align-items:center;gap:14px">
      <div style="width:44px;height:44px;border-radius:12px;background:#f3f0ff;
                  display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="{{ $icon }}" style="color:{{ $color }};font-size:18px"></i>
      </div>
      <div>
        <div style="font-size:22px;font-weight:800;color:{{ $color }}">{{ $value }}</div>
        <div style="font-size:12px;color:var(--text-muted)">{{ $label }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Pending this semester --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;gap:14px">
    <div style="width:40px;height:40px;border-radius:10px;background:{{ $pendingCurrentSemesterTotal > 0 ? 'rgba(239,68,68,0.1)' : 'rgba(16,185,129,0.1)' }};
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-hourglass-half" style="color:{{ $pendingCurrentSemesterTotal > 0 ? 'var(--danger)' : 'var(--success)' }};font-size:16px"></i>
    </div>
    <div>
      <div style="font-size:18px;font-weight:800;color:{{ $pendingCurrentSemesterTotal > 0 ? 'var(--danger)' : 'var(--success)' }}">
        Rs.{{ number_format($pendingCurrentSemesterTotal) }}
      </div>
      <div style="font-size:12px;color:var(--text-muted)">Pending for this semester (overdue + due now + next installment)</div>
    </div>
  </div>
</div>

{{-- Upcoming Exams --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-file-pen" style="color:var(--primary)"></i> Upcoming Exams</div>
    <a href="{{ route('parent.child.exams', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-list"></i> Full Schedule &amp; Results</a>
  </div>
  @forelse($upcomingExams as $exam)
  <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
    <div style="width:36px;height:36px;border-radius:8px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0"><i class="fas fa-file-pen"></i></div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:13.5px">{{ $exam->subject->name ?? $exam->name }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">{{ $exam->name }} @if($exam->room) &middot; {{ $exam->room }} @endif</div>
    </div>
    <div style="text-align:right;font-size:12.5px;font-weight:700;color:var(--text-muted)">
      {{ $exam->exam_date?->format('d M Y') }}
      @if($exam->time_label)<div style="font-weight:500">{{ $exam->time_label }}</div>@endif
    </div>
  </div>
  @empty
  <div class="empty-state"><i class="fas fa-calendar-check"></i><p>No upcoming exams scheduled.</p></div>
  @endforelse
</div>

{{-- Results & Fees --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px">

  {{-- Exam Results --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-clipboard-list" style="color:var(--primary)"></i> Recent Exam Results</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Exam</th>
            <th style="text-align:center">Marks</th>
            <th style="text-align:center">Grade</th>
          </tr>
        </thead>
        <tbody>
          @forelse($examResults as $r)
          <tr>
            <td>
              <div style="font-weight:600;font-size:13px">{{ $r->examination->name ?? '—' }}</div>
              <div style="font-size:11px;color:var(--text-muted)">
                {{ $r->examination->exam_date?->format('d M Y') ?? '' }}
              </div>
            </td>
            <td style="text-align:center">
              {{ $r->marks_obtained }}/{{ $r->examination->total_marks ?? '—' }}
            </td>
            <td style="text-align:center">
              <span class="badge {{ in_array($r->grade, ['A+','A']) ? 'success' : ($r->grade === 'F' ? 'danger' : 'warning') }}">
                {{ $r->grade }}
              </span>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="3" style="text-align:center;padding:30px;color:var(--text-muted)">
              <i class="fas fa-file-alt" style="opacity:0.3;display:block;font-size:24px;margin-bottom:6px"></i>
              No results yet.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Fee History (Accounts module — single source of truth) --}}
  @if(($feeSummary['previous_balance_total'] ?? 0) > 0)
  <div class="alert alert-danger" style="margin-bottom:16px">
    <i class="fas fa-triangle-exclamation"></i>
    <strong>Previous Balance: Rs.{{ number_format($feeSummary['previous_balance_total']) }}</strong> is overdue for {{ $student->name }}.
  </div>
  @endif
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-receipt" style="color:var(--primary)"></i> Fee History
        @if($feeBreakdown['cycleLabel']) — {{ $feeBreakdown['cycleLabel'] }} @endif
      </div>
      <div style="display:flex;gap:8px">
        <a href="{{ route('parent.child.fees', $student) }}" class="btn btn-sm btn-primary">
          <i class="fas fa-clock"></i> Upcoming / Due / Previous
        </a>
        @if($student->feeAssignments->isNotEmpty())
        <a href="{{ route('parent.fee-plan', $student->feeAssignments->first()) }}" class="btn btn-sm btn-outline">
          <i class="fas fa-list-ol"></i> View Installment Plan
        </a>
        @endif
      </div>
    </div>

    {{-- Paid installments --}}
    <div style="padding:12px 20px 4px;font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.03em">
      <i class="fas fa-circle-check" style="color:var(--success)"></i> Paid Installments
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Voucher #</th>
            <th>Method</th>
            <th>Submitted</th>
            <th>Installment</th>
            <th style="text-align:right">Amount</th>
            <th style="text-align:center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($feeBreakdown['paid'] as $row)
          <tr>
            <td style="font-size:12px;color:var(--text-muted)">{{ $row->voucher_no }}</td>
            <td style="font-size:13px">{{ $row->method !== '—' ? ucwords(str_replace('_', ' ', $row->method)) : '—' }}</td>
            <td style="font-size:12.5px">{{ $row->submitted_at?->format('d M Y') ?? '—' }}</td>
            <td style="font-weight:600;font-size:13px">{{ $row->part_label }}</td>
            <td style="text-align:right">Rs.{{ number_format($row->amount) }}</td>
            <td style="text-align:center">
              <a href="{{ route('parent.voucher', $row->representative) }}" title="Download voucher"><i class="fas fa-download"></i></a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">No installments paid yet this semester.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Upcoming installments for this semester --}}
    <div style="padding:16px 20px 4px;font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.03em">
      <i class="fas fa-hourglass-half" style="color:var(--warning)"></i> Upcoming Installments — This Semester
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Voucher #</th>
            <th>Due Date</th>
            <th>Installment</th>
            <th style="text-align:right">Amount</th>
            <th style="text-align:center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($feeBreakdown['upcoming'] as $row)
          <tr>
            <td style="font-size:12px;color:var(--text-muted)">{{ $row->voucher_no }}</td>
            <td style="font-size:12.5px">{{ $row->due_date?->format('d M Y') ?? '—' }}</td>
            <td style="font-weight:600;font-size:13px">{{ $row->part_label }}</td>
            <td style="text-align:right;color:var(--warning);font-weight:700">Rs.{{ number_format($row->amount) }}</td>
            <td style="text-align:center">
              <a href="{{ route('parent.voucher', $row->representative) }}" title="View voucher"><i class="fas fa-download"></i></a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">No installments still due this semester.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($feeBreakdown['allSettled'])
    <div style="margin:16px 20px 20px;padding:14px 16px;border-radius:10px;background:rgba(16,185,129,0.08);border:1px solid var(--success);
                display:flex;align-items:center;gap:12px">
      <i class="fas fa-circle-check" style="color:var(--success);font-size:18px"></i>
      <div style="font-size:13px;font-weight:600">
        All installments for {{ $feeBreakdown['cycleLabel'] }} are paid — you're proceeding to
        {{ $feeBreakdown['nextCycleLabel'] ?? 'the next semester' }}.
      </div>
    </div>
    @endif
  </div>

</div>

{{-- Previous Exams --}}
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-clipboard-list" style="color:var(--primary)"></i> Previous Exams</div>
    <a href="{{ route('parent.child.exams', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-list"></i> Full Schedule &amp; Results</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Exam</th>
          <th>Subject</th>
          <th>Date</th>
          <th style="text-align:center">Marks</th>
          <th style="text-align:center">Grade</th>
        </tr>
      </thead>
      <tbody>
        @forelse($previousExams as $exam)
        @php $result = $previousExamResults->get($exam->id); @endphp
        <tr>
          <td><div style="font-weight:600;font-size:13px">{{ $exam->name }}</div>@if($exam->type_label)<span class="tag">{{ $exam->type_label }}</span>@endif</td>
          <td>{{ $exam->subject->name ?? 'All Subjects' }}</td>
          <td style="font-size:12.5px">{{ $exam->exam_date?->format('d M Y') }}</td>
          <td style="text-align:center">
            @if($result)
              <strong>{{ $result->marks_obtained }}</strong> <span style="color:var(--text-muted)">/ {{ $exam->total_marks }}</span>
            @else
              <span style="color:var(--text-muted);font-size:12px">Not yet marked</span>
            @endif
          </td>
          <td style="text-align:center">
            @if($result)
              @php $gradeColor = match($result->grade) { 'A+','A' => 'success', 'B','C' => 'info', 'D' => 'warning', default => 'danger' }; @endphp
              <span class="badge {{ $gradeColor }}">{{ $result->grade }}</span>
            @else
              <span style="color:var(--text-muted)">—</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">
            <i class="fas fa-file-alt" style="opacity:0.3;display:block;font-size:24px;margin-bottom:6px"></i>
            No previous exams yet.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection


