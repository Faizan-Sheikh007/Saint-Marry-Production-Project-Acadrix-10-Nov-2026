@extends('layouts.app')
@section('title', $student->name.' — Fees')
@section('page_title', 'Fees — '.$student->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('parent.child', $student) }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:22px;font-weight:800"><i class="fas fa-receipt"></i> Fees — {{ $student->name }}</div>
</div>

{{-- Student Info --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:20px">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      @if($student->photo)
        <img src="{{ storage_asset($student->photo) }}"
             style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid var(--border)">
      @else
        <div style="width:56px;height:56px;border-radius:50%;background:var(--primary);
                    display:flex;align-items:center;justify-content:center;
                    font-weight:800;color:#fff;font-size:20px;flex-shrink:0">
          {{ strtoupper(substr($student->name, 0, 2)) }}
        </div>
      @endif
      <div style="flex:1">
        <div style="font-size:18px;font-weight:800">{{ $student->name }}</div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px;display:flex;gap:16px;flex-wrap:wrap">
          <span><i class="fas fa-id-card"></i> {{ $student->student_id }}</span>
          <span><i class="fas fa-graduation-cap"></i> {{ $student->program->name ?? '—' }}</span>
          <span><i class="fas fa-chalkboard"></i> {{ $student->schoolClass->name ?? '—' }}</span>
          <span><i class="fas fa-calendar"></i> {{ $student->academicYear->name ?? '—' }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Pending this semester --}}
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:18px 20px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;border-radius:12px;background:{{ $pendingCurrentSemesterTotal > 0 ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.1)' }};
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-hourglass-half" style="color:{{ $pendingCurrentSemesterTotal > 0 ? 'var(--danger)' : 'var(--success)' }};font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:22px;font-weight:800;color:{{ $pendingCurrentSemesterTotal > 0 ? 'var(--danger)' : 'var(--success)' }}">
        Rs.{{ number_format($pendingCurrentSemesterTotal) }}
      </div>
      <div style="font-size:12px;color:var(--text-muted)">Pending for this semester (overdue + due now + next installment)</div>
    </div>
  </div>
</div>

{{-- Installment plans --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list-ol" style="color:var(--primary)"></i> Installment Plans</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Plan</th>
          <th>Semester / Year Covered</th>
          <th>Installments</th>
          <th style="text-align:right">Total Amount</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($feeAssignments as $plan)
        <tr>
          <td style="font-weight:600;font-size:13px">{{ $plan->feeTemplate->name ?? 'Fee Plan' }}</td>
          <td>{{ $plan->cycleRangeLabel() }}</td>
          <td>{{ $plan->installments }}</td>
          <td style="text-align:right">Rs.{{ number_format($plan->total_amount) }}</td>
          <td style="text-align:center">
            <a href="{{ route('parent.fee-plan', $plan) }}" class="btn btn-sm btn-outline" target="_blank">
              <i class="fas fa-download"></i> View / Download
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">
            No installment plan assigned yet.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@include('partials.fee-summary', [
  'feeSummary'             => $feeSummary,
  'feeRouteName'           => 'parent.voucher',
  'flattenFutureEstimates' => true,
  'showInstallmentLabel'   => true,
])

{{-- Previous Exams --}}
<div class="card" style="margin-top:24px">
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
