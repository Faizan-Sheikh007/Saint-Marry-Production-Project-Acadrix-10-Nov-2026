@extends('layouts.app')

@section('title', $student->name)
@section('page_title', 'Student Profile')

@push('styles')
<style>
  :root{
    --acx-gold:#c9982b;
    --acx-gold-dark:#9c7419;
    --acx-gold-soft:rgba(201,152,43,.12);
  }

  /* ── Fee Overview tiles ─────────────────────────────────────── */
  .fov-grid{ display:grid; grid-template-columns:repeat(5, 1fr); gap:14px; }
  @media (max-width: 1000px){ .fov-grid{ grid-template-columns:repeat(2, 1fr); } }
  .fov-tile{
    border-radius:14px; padding:16px 14px; text-align:center; border:1px solid transparent;
    background:var(--bg);
  }
  .fov-icon{ width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; font-size:14px; }
  .fov-value{ font-size:17px; font-weight:800; }
  .fov-label{ font-size:11px; color:var(--text-muted); margin-top:3px; font-weight:600; text-transform:uppercase; letter-spacing:.03em; }

  .fov-paid{ background:rgba(16,185,129,.08); } .fov-paid .fov-icon{ background:rgba(16,185,129,.15); color:var(--success); } .fov-paid .fov-value{ color:var(--success); }
  .fov-due{ background:var(--acx-gold-soft); } .fov-due .fov-icon{ background:rgba(201,152,43,.2); color:var(--acx-gold-dark); } .fov-due .fov-value{ color:var(--acx-gold-dark); }
  .fov-outstanding{ background:rgba(239,68,68,.08); } .fov-outstanding .fov-icon{ background:rgba(239,68,68,.15); color:var(--danger); } .fov-outstanding .fov-value{ color:var(--danger); }
  .fov-pending{ background:rgba(245,158,11,.08); } .fov-pending .fov-icon{ background:rgba(245,158,11,.15); color:var(--warning); } .fov-pending .fov-value{ color:var(--warning); }
  .fov-upcoming{ background:var(--card); border-color:var(--border); } .fov-upcoming .fov-icon{ background:var(--bg); color:var(--text-muted); } .fov-upcoming .fov-value{ color:var(--text); }

  .fov-progress{ margin-top:18px; }
  .fov-progress-top{ display:flex; justify-content:space-between; font-size:12px; color:var(--text-muted); font-weight:600; margin-bottom:6px; }
  .fov-progress-track{ height:8px; border-radius:8px; background:var(--border); overflow:hidden; }
  .fov-progress-bar{ height:100%; border-radius:8px; background:linear-gradient(90deg, var(--acx-gold-dark), var(--acx-gold)); }

  .fov-cta{
    margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
    background:linear-gradient(120deg, var(--acx-gold-soft), rgba(201,152,43,.03));
    border:1px dashed var(--acx-gold); border-radius:12px; padding:14px 16px;
  }
  .fov-cta-text{ display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:var(--acx-gold-dark); }
  .fov-cta-text i{ margin-top:2px; }

  /* ── Per-semester fee plan cards ───────────────────────────── */
  .fp-plan{ border-top:1px solid var(--border); }
  .fp-plan-head{
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    padding:16px 20px; background:var(--bg); position:relative;
  }
  .fp-plan-head::before{ content:""; position:absolute; left:0; top:0; width:4px; height:100%; background:linear-gradient(180deg, var(--acx-gold), var(--acx-gold-dark)); }
  .fp-plan-badge{
    width:38px; height:38px; border-radius:11px; flex-shrink:0;
    background:linear-gradient(150deg, var(--acx-gold), var(--acx-gold-dark));
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px;
  }
  .fp-plan-title{ font-weight:800; font-size:14.5px; color:var(--text); }
  .fp-plan-sub{ font-size:12px; color:var(--text-muted); margin-top:2px; }

  .fp-plan-stats{ display:flex; flex-wrap:wrap; gap:10px; padding:14px 20px; }
  .fp-stat{ flex:1; min-width:120px; text-align:center; border-radius:10px; padding:10px 8px; }
  .fp-stat-value{ font-size:14px; font-weight:800; }
  .fp-stat-label{ font-size:10.5px; color:var(--text-muted); margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:.02em; }
  .fp-stat-paid{ background:rgba(16,185,129,0.08); } .fp-stat-paid .fp-stat-value{ color:var(--success); }
  .fp-stat-outstanding{ background:rgba(239,68,68,0.08); } .fp-stat-outstanding .fp-stat-value{ color:var(--danger); }
  .fp-stat-pending{ background:rgba(245,158,11,0.08); } .fp-stat-pending .fp-stat-value{ color:var(--warning); }
  .fp-stat-upcoming{ background:var(--card); border:1px solid var(--border); } .fp-stat-upcoming .fp-stat-value{ color:var(--text); }

  /* ── Complete student profile details ───────────────────────────── */
  .student-details-card .card-body{ padding:0; }
  .student-details-section{ padding:18px 22px; }
  .student-details-section + .student-details-section{ border-top:1px solid var(--border); }
  .student-details-heading{
    display:flex; align-items:center; gap:8px; margin-bottom:10px;
    font-size:12px; font-weight:800; color:var(--text); text-transform:uppercase; letter-spacing:.04em;
  }
  .student-details-heading i{ color:var(--primary); }
  .student-details-grid{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 24px; }
  .student-detail-item{ min-width:0; padding:10px 0; border-bottom:1px solid var(--border); }
  .student-detail-item.full{ grid-column:1/-1; }
  .student-detail-label{ font-size:10.5px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:.035em; }
  .student-detail-value{ margin-top:4px; font-size:13.5px; font-weight:600; color:var(--text); word-break:break-word; white-space:pre-line; }
  @media (max-width: 700px){
    .student-details-grid{ grid-template-columns:1fr; }
    .student-detail-item.full{ grid-column:auto; }
  }
</style>
@endpush

@section('content')

@php
  // The top-level Fee Summary button should print the latest assigned fee plan,
  // not open the read-only admin fee-history screen.
  $printableFeePlan = $student->feeAssignments
      ->sortByDesc(fn ($plan) => [$plan->cycle_start, $plan->created_at])
      ->first();
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="{{ route('students.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('students.edit', $student) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
  <a href="{{ route('fee-assignments.create', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-invoice-dollar"></i> Assign Fee Plan</a>

  @if($printableFeePlan)
    <a href="{{ route('fee-assignments.summary', $printableFeePlan) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-money-bill-wave"></i> Installment Plan</a>
  @else
    <button type="button" class="btn btn-outline btn-sm" disabled title="Assign a fee plan first"><i class="fas fa-money-bill-wave"></i> Installment Plan</button>
  @endif

  <a href="{{ route('students.message', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-envelope"></i> Message</a>

  @if($student->user_id)
    <span class="badge success" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-graduation-cap"></i> LMS ID Active</span>
  @else
    <span class="badge warning" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-triangle-exclamation"></i> No LMS ID Yet</span>
  @endif
</div>

{{-- Post-admission aid: visible on the Admin student profile as well as Accounts. --}}
@if($student->feeAssignments->isNotEmpty())
<div class="card" style="margin-bottom:18px" id="student-relief">
  <div class="card-header"><div class="card-title"><i class="fas fa-hand-holding-heart"></i> Add Scholarship / Financial Aid After Admission</div></div>
  <div class="card-body">
    <p style="color:var(--text-muted);font-size:12px">Apply new aid to remaining unpaid installments. Previous payments and receipts remain unchanged.</p>
    <form method="POST" action="{{ route('students.relief.store', [$student, $student->feeAssignments->sortByDesc('id')->first()]) }}" id="adminStudentReliefForm" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
      @csrf
      <label>Installment Plan
        <select class="form-control" required onchange="document.getElementById('adminStudentReliefForm').action=this.value">
          @foreach($student->feeAssignments->sortByDesc('id') as $plan)
            <option value="{{ route('students.relief.store', [$student, $plan]) }}">{{ $plan->feeTemplate?->name ?? 'Fee Plan' }} #{{ $plan->id }} — {{ $plan->cycleRangeLabel() }}</option>
          @endforeach
        </select>
      </label>
      <label>Type <select class="form-control" name="type" required><option value="scholarship">Scholarship</option><option value="financial_aid">Financial Aid</option></select></label>
      <label>Amount (Rs.) <input class="form-control" type="number" min="1" step="1" name="amount" required></label>
      <label>Approval / Reason <input class="form-control" name="notes" maxlength="500" placeholder="Optional reference"></label>
      <button class="btn btn-primary" type="submit"><i class="fas fa-check"></i> Apply Aid</button>
    </form>
  </div>
</div>
@endif

<div class="grid grid-2" style="gap:20px">
  <!-- Profile Card -->
  <div class="card">
    <div class="card-body" style="text-align:center;padding:32px 24px">
     @if($student->photo)
    <img src="{{ storage_asset($student->photo) }}"
         alt="{{ $student->name }}"
         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin:0 auto 16px;display:block">
@else
    <div style="width:80px;height:80px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px">
        {{ strtoupper(substr($student->name, 0, 0)) }}
    </div>
@endif
      <div style="font-size:20px;font-weight:800">{{ $student->name }}</div>
      <div style="color:var(--text-muted);margin-bottom:16px">{{ $student->student_id }} • {{ $student->schoolClass->name ?? 'N/A' }}-{{ $student->section }}</div>
      <span class="badge {{ $student->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($student->status) }}</span>
    </div>

    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
  <div style="display:flex;flex-wrap:wrap;gap:12px">

    <div style="flex:1;min-width:80px;text-align:center;background:#eff6ff;border-radius:10px;padding:12px">
      <div style="font-size:20px;font-weight:800;color:#2563eb">{{ $student->attendance_percentage ?? '—' }}{{ $student->attendance_percentage !== null ? '%' : '' }}</div>
      <div style="font-size:11px;color:#64748b;margin-top:2px">Attendance</div>
    </div>

    <div style="flex:1;min-width:80px;text-align:center;background:{{ $student->isAttendanceHeld() ? '#fef2f2' : '#f0fdf4' }};border-radius:10px;padding:12px">
      <div style="font-size:14px;font-weight:800;color:{{ $student->isAttendanceHeld() ? '#dc2626' : '#16a34a' }}">{{ $student->isAttendanceHeld() ? 'Exam Fee Held' : 'No Hold' }}</div>
      <div style="font-size:11px;color:#64748b;margin-top:2px">Fee Hold Status</div>
    </div>

    <div style="flex:1;min-width:80px;text-align:center;background:#fff7ed;border-radius:10px;padding:12px">
      <div style="font-size:20px;font-weight:800;color:#ea580c">Rs. {{ number_format($student->pendingDues(), 0) }}</div>
      <div style="font-size:11px;color:#64748b;margin-top:2px">Dues</div>
    </div>

  </div>
</div>
    <div style="padding:0 20px 16px">
      <a href="{{ route('students.id-card', $student) }}" class="btn btn-outline" style="width:100%;justify-content:center"><i class="fas fa-id-card"></i> ID Card</a>
    </div>

    <!-- LMS Access -->
    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
      <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">
        <i class="fas fa-graduation-cap"></i> LMS Access
      </div>
      @if($student->user_id)
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin-bottom:10px">
          <div style="font-size:10.5px;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:3px">LMS ID (Login Email)</div>
          <div style="font-weight:600;font-size:13.5px;word-break:break-all">{{ $student->user->email }}</div>
        </div>
        <div style="font-size:11.5px;color:var(--text-muted);line-height:1.5;margin-bottom:10px">
          <i class="fas fa-circle-info"></i> Passwords are encrypted and can't be viewed after they're set — reset it below to get a new one to share.
        </div>
        <form action="{{ route('students.reset-lms-password', $student) }}" method="POST"
          data-confirm="This sets a new LMS password for {{ $student->name }}. Their current password will stop working."
          data-confirm-title="Reset LMS password?"
          data-confirm-variant="warning"
          data-confirm-icon="fa-key"
          data-confirm-ok-text="Yes, reset it">
          @csrf
          <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center">
            <i class="fas fa-key"></i> Reset Password
          </button>
        </form>
      @else
        <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:10px">No LMS login yet — generate one so {{ $student->name }} can access courses, assignments and grades.</div>
        <form action="{{ route('students.generate-lms-id', $student) }}" method="POST"
          data-confirm="This creates a login for {{ $student->name }} so they can access the LMS — courses, assignments, and grades."
          data-confirm-title="Generate LMS login?"
          data-confirm-variant="warning"
          data-confirm-icon="fa-graduation-cap"
          data-confirm-ok-text="Yes, generate it">
          @csrf
          <button type="submit" class="btn btn-sm" style="width:100%;justify-content:center;background:#d97706;color:#fff;border:none">
            <i class="fas fa-graduation-cap"></i> Generate LMS ID
          </button>
        </form>
      @endif
    </div>
  </div>

  <!-- Complete Student Details -->
  @php
    $programName = $student->program?->name ?? $student->schoolClass?->program?->name;
    $institutionName = $student->institution?->name
      ?? $student->program?->institution?->name
      ?? $student->schoolClass?->program?->institution?->name;
    $partSemester = $student->schoolClass?->section_label ?? $student->section;
    $attendanceValue = $student->attendance_percentage !== null
      ? rtrim(rtrim(number_format((float) $student->attendance_percentage, 2, '.', ''), '0'), '.') . '%'
      : null;
  @endphp

  <div class="card student-details-card">
    <div class="card-header"><div class="card-title"><i class="fas fa-address-card"></i> Student Details</div></div>
    <div class="card-body">
      <div class="student-details-section">
        <div class="student-details-heading"><i class="fas fa-user"></i> Personal Information</div>
        <div class="student-details-grid">
          @foreach([
            ['label' => 'Student ID', 'value' => $student->student_id],
            ['label' => 'Applicant Name', 'value' => $student->name],
            ['label' => "Father's Name", 'value' => $student->father_name],
            ['label' => 'Date of Birth', 'value' => $student->date_of_birth?->format('d-m-Y')],
            ['label' => 'Gender', 'value' => $student->gender ? ucfirst($student->gender) : null],
            ['label' => 'Marital Status', 'value' => $student->marital_status ? ucfirst($student->marital_status) : null],
            ['label' => 'CNIC / B-Form Number', 'value' => $student->cnic],
            ['label' => 'Religion', 'value' => $student->religion],
            ['label' => 'Mobile Number', 'value' => $student->phone],
            ['label' => 'Student Email', 'value' => $student->email],
            ['label' => 'Present / Mailing Address', 'value' => $student->address, 'full' => true],
            ['label' => 'Permanent Address', 'value' => $student->permanent_address, 'full' => true],
          ] as $detail)
            <div class="student-detail-item {{ !empty($detail['full']) ? 'full' : '' }}">
              <div class="student-detail-label">{{ $detail['label'] }}</div>
              <div class="student-detail-value">{{ filled($detail['value']) ? $detail['value'] : 'N/A' }}</div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="student-details-section">
        <div class="student-details-heading"><i class="fas fa-graduation-cap"></i> Academic Information</div>
        <div class="student-details-grid">
          @foreach([
            ['label' => 'Academic Level', 'value' => $student->academicLevel?->name ?? ($student->level ? ucfirst($student->level) : null)],
            ['label' => 'Academic Session', 'value' => $student->academicYear?->name ?? $student->session],
            ['label' => 'Institution / University', 'value' => $institutionName],
            ['label' => 'Program', 'value' => $programName],
            ['label' => 'Part / Semester', 'value' => $partSemester],
            ['label' => 'Shift', 'value' => $student->shift ? ucfirst($student->shift) : null],
            ['label' => 'Roll Number', 'value' => $student->roll_number],
            ['label' => 'Admission Date', 'value' => $student->admission_date?->format('d/m/Y')],
            ['label' => 'Status', 'value' => $student->status ? ucfirst($student->status) : null],
            ['label' => 'Attendance', 'value' => $attendanceValue],
          ] as $detail)
            <div class="student-detail-item">
              <div class="student-detail-label">{{ $detail['label'] }}</div>
              <div class="student-detail-value">{{ filled($detail['value']) ? $detail['value'] : 'N/A' }}</div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="student-details-section">
        <div class="student-details-heading"><i class="fas fa-hand-holding-dollar"></i> Financial Aid / Scholarship</div>
        <div class="student-details-grid">
          @foreach([
            ['label' => 'Requested', 'value' => $student->financial_aid_requested ? 'Yes' : 'No'],
            ['label' => 'Type', 'value' => $student->financial_aid_type],
            ['label' => 'Notes', 'value' => $student->financial_aid_notes, 'full' => true],
          ] as $detail)
            <div class="student-detail-item {{ !empty($detail['full']) ? 'full' : '' }}">
              <div class="student-detail-label">{{ $detail['label'] }}</div>
              <div class="student-detail-value">{{ filled($detail['value']) ? $detail['value'] : 'N/A' }}</div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="student-details-section">
        <div class="student-details-heading"><i class="fas fa-id-card"></i> ID Card Information</div>
        <div class="student-details-grid">
          @foreach([
            ['label' => 'ID Card Number', 'value' => $student->id_card_number],
            ['label' => 'Issued Date', 'value' => $student->id_card_issued_at?->format('d/m/Y')],
            ['label' => 'Reissue Count', 'value' => (string) ($student->id_card_reissue_count ?? 0)],
          ] as $detail)
            <div class="student-detail-item">
              <div class="student-detail-label">{{ $detail['label'] }}</div>
              <div class="student-detail-value">{{ filled($detail['value']) ? $detail['value'] : 'N/A' }}</div>
            </div>
          @endforeach
        </div>
      </div>

    </div>
  </div>
</div>

@php
  // All tiles use exactly the same authoritative voucher ledger as Accounts
  // and Student > My Fees. Never rely on a possibly stale stored status.
  $ovPaidTotal = $feeLedger['totals']['paid'];
  $ovOutstandingTotal = $feeLedger['totals']['overdue'];
  $ovPendingTotal = $feeLedger['totals']['pending'];
  $ovDueTotal = collect($feeLedger['rows'])
      ->filter(fn ($v) => $v->schedule === 'Due Today')
      ->sum('balance');
  $ovUpcomingTotal = max(0, $ovPendingTotal - $ovOutstandingTotal - $ovDueTotal);

  // ── "How much of the program is planned" — used for the CTA banner below.
  $program = $student->program ?: ($student->schoolClass->program ?? null);
  $totalCycles = $feePlanProgression['total_cycles'];
  $coveredCycles = collect($feePlanProgression['covered_cycles']);
  $remainingCycles = $feePlanProgression['remaining_cycles'] ?? 0;
  $nextFeeCycle = $feePlanProgression['next_cycle'];
@endphp

<div class="card fov-card-wrap" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-chart-pie" style="color:var(--acx-gold-dark)"></i> Fee Overview <span style="font-weight:500;color:var(--text-muted);font-size:12px">&mdash; all semesters combined</span></div>
  </div>
  <div class="card-body">
    <div class="fov-grid">
      <div class="fov-tile fov-paid">
        <div class="fov-icon"><i class="fas fa-circle-check"></i></div>
        <div class="fov-value">Rs. {{ number_format($ovPaidTotal, 0) }}</div>
        <div class="fov-label">Paid</div>
      </div>
      <div class="fov-tile fov-due">
        <div class="fov-icon"><i class="fas fa-clock"></i></div>
        <div class="fov-value">Rs. {{ number_format($ovDueTotal, 0) }}</div>
        <div class="fov-label">Due Now</div>
      </div>
      <div class="fov-tile fov-outstanding">
        <div class="fov-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="fov-value">Rs. {{ number_format($ovOutstandingTotal, 0) }}</div>
        <div class="fov-label">Overdue</div>
      </div>
      <div class="fov-tile fov-pending">
        <div class="fov-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="fov-value">Rs. {{ number_format($ovPendingTotal, 0) }}</div>
        <div class="fov-label">Pending (total)</div>
      </div>
      <div class="fov-tile fov-upcoming">
        <div class="fov-icon"><i class="fas fa-calendar-plus"></i></div>
        <div class="fov-value">Rs. {{ number_format($ovUpcomingTotal, 0) }}</div>
        <div class="fov-label">Upcoming</div>
      </div>
    </div>

    @if($program && $totalCycles)
    <div class="fov-progress">
      <div class="fov-progress-top">
        <span><i class="fas fa-layer-group"></i> {{ $coveredCycles->count() }} of {{ $totalCycles }} {{ $program->system_type === 'annual' ? 'part(s)' : 'semester(s)' }} have a fee plan</span>
        <span>{{ $totalCycles ? round(($coveredCycles->count() / $totalCycles) * 100) : 0 }}%</span>
      </div>
      <div class="fov-progress-track"><div class="fov-progress-bar" style="width:{{ $totalCycles ? min(100, round(($coveredCycles->count() / $totalCycles) * 100)) : 0 }}%"></div></div>
    </div>

    @if($remainingCycles > 0)
    <div class="fov-cta">
      <div class="fov-cta-text">
        <i class="fas fa-wand-magic-sparkles"></i>
        <div>
          <strong>{{ $remainingCycles }} {{ $program->system_type === 'annual' ? 'part(s)' : 'semester(s)' }} still need a fee plan.</strong>
          <div style="font-weight:500;color:var(--text-muted);font-size:12px;margin-top:2px">Create {{ $feePlanProgression['cycle_label'] }} {{ $nextFeeCycle }} next. Only one semester/year is generated at a time.</div>
        </div>
      </div>
      <a href="{{ route('fee-assignments.create', $student) }}" class="btn btn-primary btn-sm"><i class="fas fa-forward"></i> Create {{ $feePlanProgression['cycle_label'] }} {{ $nextFeeCycle }} Fee Plan</a>
    </div>
    @endif
    @endif
  </div>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <div class="card-title"><i class="fas fa-layer-group" style="color:var(--acx-gold-dark);margin-right:6px"></i> Installment Plan</div>
    <div style="display:flex;gap:8px">
      <a href="{{ route('vouchers.create', ['student_id' => $student->id]) }}" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> New Voucher</a>
      @unless($feePlanProgression['is_complete'])
        <a href="{{ route('fee-assignments.create', $student) }}" class="btn btn-primary btn-sm" title="Create {{ $feePlanProgression['cycle_label'] }} {{ $nextFeeCycle }} only"><i class="fas fa-plus"></i> New Fee Plan</a>
      @endunless
    </div>
  </div>

  @php
    $activePlanIds = $student->authoritativeVouchers()
      ->pluck('fee_assignment_id')->filter()->unique();
  @endphp

  @forelse($student->feeAssignments->sortByDesc(fn ($p) => [$p->cycle_start, $p->created_at]) as $plan)
  @php
    $planVouchers = $student->vouchers->where('fee_assignment_id', $plan->id);
    $voucherGroups = $planVouchers->groupBy('voucher_no')->sortBy(fn ($g) => $g->first()->installment_no);

    $planPosition = \App\Services\StudentFeeLedger::summarize($planVouchers, now());
    $planLedgerByNo = collect($planPosition['rows'])->keyBy('voucher_no');
    $paidTotal = $planPosition['totals']['paid'];
    $outstandingTotal = $planPosition['totals']['overdue'];
    $pendingTotal = $planPosition['totals']['pending'];
    $upcomingTotal = max(0, $pendingTotal - $outstandingTotal);
    $planIsHistorical = $planVouchers->isNotEmpty() && ! $activePlanIds->contains($plan->id);
  @endphp
  <div class="fp-plan">
    <div class="fp-plan-head">
      <div class="fp-plan-badge"><i class="fas fa-layer-group"></i></div>
      <div style="flex:1;min-width:160px">
        <div class="fp-plan-title">{{ $plan->cycleRangeLabel() }}
          @if($planIsHistorical) <span class="badge gray" title="This fee plan has been superseded and is excluded from current balance">Historical / Replaced</span> @endif
        </div>
        <div class="fp-plan-sub">{{ $plan->feeTemplate->name ?? '—' }} &middot; {{ $plan->installments }} installment(s) &middot; Rs. {{ number_format($plan->total_amount, 0) }}</div>
        @if(($plan->scholarship_amount ?? 0) > 0 || ($plan->financial_aid_amount ?? 0) > 0)
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:5px;font-size:11px">
          @if(($plan->scholarship_amount ?? 0) > 0)
            <span class="badge badge-success">Scholarship: Rs. {{ number_format($plan->scholarship_amount, 0) }}</span>
          @endif
          @if(($plan->financial_aid_amount ?? 0) > 0)
            <span class="badge badge-warning">Financial Aid: Rs. {{ number_format($plan->financial_aid_amount, 0) }}</span>
          @endif
        </div>
        @endif
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="{{ route('fee-assignments.show', $plan) }}" class="btn btn-outline btn-sm" title="View plan"><i class="fas fa-eye"></i></a>
        <a href="{{ route('fee-assignments.plan', $plan) }}" target="_blank" class="btn btn-outline btn-sm" title="Installment plan (bank/student/school copies)"><i class="fas fa-download"></i></a>
        <a href="{{ route('fee-assignments.summary', $plan) }}" target="_blank" class="btn btn-outline btn-sm" title="Fee summary slip"><i class="fas fa-file-invoice"></i></a>
        @if($plan->feeTemplate)
        <a href="{{ route('fee-assignments.renew', $plan) }}" class="btn btn-outline btn-sm" title="Renew / increase fee"><i class="fas fa-rotate"></i></a>
        @endif
      </div>
    </div>

    <div class="fp-plan-stats">
      <div class="fp-stat fp-stat-paid">
        <div class="fp-stat-value">Rs. {{ number_format($paidTotal, 0) }}</div>
        <div class="fp-stat-label">Paid</div>
      </div>
      <div class="fp-stat fp-stat-outstanding">
        <div class="fp-stat-value">Rs. {{ number_format($outstandingTotal, 0) }}</div>
        <div class="fp-stat-label">Overdue</div>
      </div>
      <div class="fp-stat fp-stat-pending">
        <div class="fp-stat-value">Rs. {{ number_format($pendingTotal, 0) }}</div>
        <div class="fp-stat-label">Pending (total)</div>
      </div>
      <div class="fp-stat fp-stat-upcoming">
        <div class="fp-stat-value">Rs. {{ number_format($upcomingTotal, 0) }}</div>
        <div class="fp-stat-label">Upcoming</div>
      </div>
    </div>

    @if($planIsHistorical)
      <p style="padding:8px 20px;color:var(--text-muted);font-size:12px">Historical plan shown for audit only. These values are <strong>not</strong> included in current outstanding-fee totals above.</p>
    @endif
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Voucher No</th><th>Items</th><th>Amount</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @if($voucherGroups->isEmpty())
          <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">No installments generated for this plan yet.</td></tr>
          @else
          @php
            // Group the flat installment list into one block per semester/year
            // (cycle_no) so a 36-installment plan reads semester-by-semester.
            // Older plans predating cycle_no fall back to the flat list.
            $hasCycles = $voucherGroups->every(fn ($lines) => !is_null($lines->first()->cycle_no));
            // Preserve voucher_no keys when grouping by semester. Without this,
            // Laravel renumbers each semester's vouchers 0, 1, 2, ... and the
            // $planLedgerByNo lookup below misses their paid/partial states.
            $bySemester = $hasCycles
                ? $voucherGroups->groupBy(fn ($lines) => $lines->first()->cycle_no, preserveKeys: true)
                : collect([null => $voucherGroups]);
          @endphp
          @foreach($bySemester as $cycleNo => $cycleGroups)
          @if($hasCycles)
          @php $cycleTotal = $cycleGroups->sum(fn ($lines) => $lines->sum(fn ($v) => $v->total())); @endphp
          <tr style="background:var(--bg)">
            <td colspan="4" style="font-weight:800;color:var(--primary);padding-top:12px">{{ $plan->termLabelFor((int) $cycleNo) }}</td>
            <td colspan="3" style="text-align:right;font-weight:700;color:var(--text-muted);padding-top:12px">Rs. {{ number_format($cycleTotal, 0) }} total</td>
          </tr>
          @endif
          @foreach($cycleGroups as $voucherNo => $lines)
          @php
            $first       = $lines->first();
            $position = $planLedgerByNo->get($voucherNo);
            $groupTotal = $position?->amount ?? (float)$lines->sum(fn ($v) => $v->total());
            $groupPaid = $position?->paid ?? (float)$lines->sum('paid_amount');
            $groupBalance = $position?->balance ?? max(0, $groupTotal - $groupPaid);
            $groupPaymentState = $position?->payment_state ?? 'Unpaid';
            $groupSchedule = $position?->schedule ?? null;
            $groupStatusClass = $groupPaymentState === 'Paid' ? 'success'
              : ($groupSchedule === 'Overdue' ? 'danger' : ($groupPaymentState === 'Cancelled' ? 'gray' : 'warning'));
            $itemsLabel = $lines->map(fn ($v) => $v->feeHead->name ?? $v->title)->unique()->implode(', ');
          @endphp
          <tr>
            <td>{{ $first->installment_no }}/{{ $plan->installments }}</td>
            <td>{{ $voucherNo }}</td>
            <td style="font-size:12px;color:var(--text-muted)">{{ $itemsLabel }}</td>
            <td>
              Rs. {{ number_format($groupTotal, 0) }}
              @if($groupPaid > 0 || $groupBalance > 0)
                <div style="font-size:11px;color:var(--text-muted)">Rs. {{ number_format($groupPaid, 0) }} paid / credited &middot; Rs. {{ number_format($groupBalance, 0) }} remaining</div>
              @endif
            </td>
            <td>{{ $first->due_date?->format('M d, Y') ?? '—' }}</td>
            <td>
              <span class="badge {{ $groupStatusClass }}">{{ $groupPaymentState === 'Partial' ? 'Partially Paid' : $groupPaymentState }}</span>
              @if($groupSchedule)
                <span class="badge {{ $groupSchedule === 'Overdue' ? 'danger' : 'gray' }}">{{ $groupSchedule }}</span>
              @endif
            </td>
            <td>
              @if($lines->count() > 1)
                <a href="{{ route('vouchers.index', ['search' => $voucherNo]) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
              @else
                <a href="{{ route('vouchers.show', $first) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
              @endif
            </td>
          </tr>
          @endforeach
          @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>
  @empty
  <div style="text-align:center;padding:24px;color:var(--text-muted)">No fee plan assigned yet.</div>
  @endforelse

  @php
    $unassigned = $student->authoritativeVouchers()->whereNull('fee_assignment_id');
  @endphp
  @if($unassigned->isNotEmpty())
  <div style="border-top:1px solid var(--border)">
    <div style="padding:14px 20px;background:var(--bg)">
      <div style="font-weight:800;font-size:14px;color:var(--text-muted)">Other Vouchers</div>
      <div style="font-size:12px;color:var(--text-muted)">Vouchers created individually, not tied to a semester/year fee plan.</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Voucher No</th><th>Title</th><th>Amount</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($unassigned as $voucher)
          <tr>
            <td>{{ $voucher->voucher_no }}</td>
            <td>{{ $voucher->title }}</td>
            <td>Rs. {{ number_format($voucher->total(), 0) }}</td>
            <td>{{ $voucher->due_date?->format('M d, Y') ?? '—' }}</td>
            <td>
              @php $label = $voucher->balance() <= 0 ? 'Paid' : ($voucher->paid_amount > 0 ? 'Partially Paid' : 'Unpaid'); @endphp
              <span class="badge {{ $label === 'Paid' ? 'success' : 'warning' }}">{{ $label }}</span>
              @if($voucher->balance() > 0) <span class="badge {{ $voucher->autoStatusLabel() === 'Overdue' ? 'danger' : 'gray' }}">{{ $voucher->autoStatusLabel() }}</span> @endif
            </td>
            <td><a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>

{{-- Complete voucher/fee-head/payment history: read-only, in sync with Accounts. --}}
@include('partials.student-finance-history', [
  'feeLedger' => $feeLedger,
  'paymentHistory' => $paymentHistory,
  'financeShowTotals' => false,
  'financeShowPlans' => true,
  'financePlans' => $student->feeAssignments,
  'financeVoucherRoute' => 'vouchers.show',
  'financeReceiptRoute' => 'vouchers.receipt',
  'financeCreditTransactions' => $financeCreditTransactions,
  'financeAvailableCredit' => $financeAvailableCredit,
])

<div class="card" style="margin-top:20px">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <div class="card-title"><i class="fas fa-calendar-alt"></i> Class Timetable</div>
    @if($student->school_class_id)
      <a href="{{ route('students.timetable.print', $student) }}" target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-print"></i> Print
      </a>
    @endif
  </div>
  <div class="card-body">
    @include('timetable._agenda', [
      'items'    => $timetable,
      'context'  => 'class',
      'emptyText' => $student->school_class_id ? 'No timetable has been set up for this class yet.' : 'Assign this student to a class to see their timetable.',
    ])
  </div>
</div>





@endsection