@extends('layouts.app')
@section('title','Student Dashboard')
@push('styles')
<style>
  /* Financial highlights inside the student-only maroon welcome banner. */
  .student-dashboard-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
  }
  .student-dashboard-intro { flex: 1 1 0; min-width: 0; position: relative; z-index: 1; }
  .student-dashboard-status { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
  .student-fee-highlights {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: min(48%, 500px);
    flex: 0 0 auto;
    gap: 14px;
    position: relative;
    z-index: 1;
  }
  .student-fee-highlight {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    min-height: 108px;
    padding: 16px 18px;
    border: 1px solid rgba(255, 255, 255, .23);
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    box-shadow: 0 8px 24px rgba(35, 0, 10, .11);
    text-decoration: none;
    color: #fff;
    transition: background .15s ease, border-color .15s ease;
  }
  .student-fee-highlight:hover, .student-fee-highlight:focus-visible {
    background: rgba(255, 255, 255, .19);
    border-color: rgba(255, 255, 255, .55);
    color: #fff;
  }
  .student-fee-highlight:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }
  .student-fee-highlight__heading {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .045em;
    text-transform: uppercase;
    opacity: .9;
    white-space: nowrap;
  }
  .student-fee-highlight__amount {
    margin-top: 10px;
    font-size: clamp(19px, 2vw, 27px);
    font-weight: 850;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }
  .student-fee-highlight--paid .student-fee-highlight__amount { color: #c1ffd8; }
  .student-fee-highlight--pending .student-fee-highlight__amount { color: #ffe6a0; }
  .student-fee-highlight__note { margin-top: 6px; font-size: 10.5px; opacity: .77; }
  @media (max-width: 1050px) {
    .student-dashboard-hero { flex-wrap: wrap; }
    .student-dashboard-intro { flex-basis: 100%; }
    .student-fee-highlights { width: 100%; }
  }
  @media (max-width: 520px) {
    .student-dashboard-hero { padding: 22px 18px; gap: 16px; }
    .student-fee-highlights { gap: 9px; }
    .student-fee-highlight { min-height: 100px; padding: 12px; }
    .student-fee-highlight__heading { font-size: 9px; white-space: normal; }
    .student-fee-highlight__amount { font-size: clamp(15px, 4vw, 20px); }
    .student-fee-highlight__note { font-size: 9px; }
  }
</style>
@endpush
@section('content')
@if(!$student)
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Your student profile is not linked yet. Please contact admin.</div>
@else
<div class="welcome-banner student-dashboard-hero" style="margin-bottom:24px">
  <div class="student-dashboard-intro">
    <div style="font-size:11px;opacity:.7;font-weight:700;text-transform:uppercase;letter-spacing:.06em">{{ $student->session }}</div>
    <h1 style="font-size:24px;font-weight:800;margin:6px 0">Hi {{ explode(' ', $student->name)[0] }}! 🎓</h1>
    <p style="opacity:.8">{{ $student->schoolClass->name ?? '' }} — Roll No. {{ $student->roll_number }}</p>
    <div class="student-dashboard-status">
      <div style="background:rgba(255,255,255,.15);padding:6px 14px;border-radius:16px;font-size:12px;font-weight:700"><i class="fas fa-clipboard-check"></i> {{ $attendancePct }}% Attendance</div>
      @if($pendingFees > 0)
      <div style="background:rgba(245,158,11,.3);padding:6px 14px;border-radius:16px;font-size:12px;font-weight:700"><i class="fas fa-file-invoice-dollar"></i> Rs.{{ number_format($pendingFees) }} Fees Remaining</div>
      @if($overdueFees > 0)
      <div style="background:rgba(220,38,38,.3);padding:6px 14px;border-radius:16px;font-size:12px;font-weight:700"><i class="fas fa-triangle-exclamation"></i> Rs.{{ number_format($overdueFees) }} Overdue</div>
      @else
      <div style="background:rgba(16,185,129,.3);padding:6px 14px;border-radius:16px;font-size:12px;font-weight:700"><i class="fas fa-calendar-check"></i> Nothing overdue</div>
      @endif
      @endif
    </div>
    <div style="margin-top:14px">
      <a href="{{ route('student.fees') }}" class="btn btn-sm" style="background:#fff;color:var(--primary);font-weight:800">
        <i class="fas fa-file-invoice-dollar"></i> My Fees, Vouchers &amp; Payment History
      </a>
    </div>
  </div>

  <div class="student-fee-highlights" aria-label="Your current fee position">
    <a class="student-fee-highlight student-fee-highlight--paid" href="{{ route('student.fees') }}"
       aria-label="Paid or credited toward fees: Rs.{{ number_format($paidCreditedFees, 2) }}. View payment history.">
      <span class="student-fee-highlight__heading"><i class="fas fa-circle-check" aria-hidden="true"></i> Paid / Credited</span>
      <span class="student-fee-highlight__amount">Rs.{{ number_format($paidCreditedFees, 2) }}</span>
      <span class="student-fee-highlight__note">Allocated to your fees</span>
    </a>
    <a class="student-fee-highlight student-fee-highlight--pending" href="{{ route('student.fees') }}"
       aria-label="Remaining fee balance: Rs.{{ number_format($pendingFees, 2) }}. View fee details.">
      <span class="student-fee-highlight__heading"><i class="fas fa-wallet" aria-hidden="true"></i> Remaining Balance</span>
      <span class="student-fee-highlight__amount">Rs.{{ number_format($pendingFees, 2) }}</span>
      <span class="student-fee-highlight__note">Unpaid &amp; future installments</span>
    </a>
  </div>
</div>

<div class="grid grid-3" style="margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-laptop-code"></i></div>
    <div><div class="stat-value">{{ $enrollments->count() }}</div><div class="stat-label">Enrolled Courses</div></div></div>
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
    <div><div class="stat-value">{{ $pendingAssignments->count() }}</div><div class="stat-label">Due Assignments</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-star"></i></div>
    <div><div class="stat-value">{{ $student->averageGrade() }}%</div><div class="stat-label">Avg. Grade</div></div></div>
</div>

<div class="grid grid-2" style="gap:20px;margin-bottom:24px">
  <div class="card" style="margin-bottom:0">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-calendar-day"></i> Today's Classes — {{ $todayName }}</div>
      <a href="{{ route('student.timetable.index') }}" class="btn btn-outline btn-sm">Full Timetable</a>
    </div>
    @forelse($todaysClasses as $p)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="width:40px;height:40px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0"><i class="fas fa-book-open"></i></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px">{{ $p->subject->name ?? '—' }}</div>
        <div class="text-sm text-muted">{{ $p->teacher->name ?? 'Teacher TBA' }}</div>
      </div>
      <div style="text-align:right;font-size:12.5px;font-weight:700;color:var(--text-muted)">
        {{ \Carbon\Carbon::parse($p->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($p->end_time)->format('g:i A') }}
      </div>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-calendar-check"></i><p>No classes scheduled for today.</p></div>
    @endforelse
  </div>

  <div class="card" style="margin-bottom:0">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-file-pen"></i> Upcoming Exams</div>
      <a href="{{ route('student.exams.index') }}" class="btn btn-outline btn-sm">All Exams</a>
    </div>
    @forelse($upcomingExams as $exam)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="width:40px;height:40px;border-radius:8px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:14px;flex-shrink:0"><i class="fas fa-file-pen"></i></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px">{{ $exam->subject->name ?? $exam->name }}</div>
        <div class="text-sm text-muted">{{ $exam->name }} @if($exam->room) &middot; {{ $exam->room }} @endif</div>
      </div>
      <div style="text-align:right;font-size:12.5px;font-weight:700;color:var(--text-muted)">
        {{ $exam->exam_date?->format('d M') }}
        @if($exam->time_label)<div style="font-weight:500">{{ $exam->time_label }}</div>@endif
      </div>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-calendar-check"></i><p>No upcoming exams scheduled.</p></div>
    @endforelse
  </div>
</div>

<div class="grid grid-2">
  <!-- Enrolled Subjects -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">My Subjects</div>
      <a href="{{ route('lms.courses.index') }}" class="btn btn-outline btn-sm">View All</a>
    </div>
    @forelse($enrollments as $enrollment)
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div>
          <div style="font-weight:700;font-size:13.5px">{{ $enrollment->course->title }}</div>
          <div class="text-sm text-muted">{{ $enrollment->course->teacher->name ?? '' }}</div>
        </div>
        <a href="{{ route('lms.courses.show', $enrollment->course) }}" class="btn btn-primary btn-xs">Continue</a>
      </div>
      <div class="progress"><div class="progress-bar primary" style="width:{{ $enrollment->progress_percent }}%"></div></div>
      <div class="text-sm text-muted mt-1">{{ $enrollment->progress_percent }}% complete</div>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-laptop-code"></i><p>No subjects assigned to your class yet. They'll show up here as soon as the admin assigns them.</p></div>
    @endforelse
  </div>

  <!-- Pending Assignments -->
  <div class="card">
    <div class="card-header"><div class="card-title">Due Assignments</div></div>
    @forelse($pendingAssignments as $a)
    <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
      <div style="width:36px;height:36px;border-radius:8px;background:rgba(245,158,11,.1);display:flex;align-items:center;justify-content:center;color:var(--warning)"><i class="fas fa-tasks"></i></div>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13.5px">{{ $a->title }}</div>
        <div class="text-sm text-muted">Due: {{ $a->due_date?->format('M d') }} · {{ $a->course->title }}</div>
      </div>
      <a href="{{ route('lms.assignments.show', [$a->course, $a]) }}" class="btn btn-primary btn-xs">Submit</a>
    </div>
    @empty
    <div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending assignments!</p></div>
    @endforelse
  </div>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-chalkboard-teacher"></i> My Subjects &amp; Teachers</div>
    <span style="font-size:12px;font-weight:700;color:var(--text-muted)">{{ $student->schoolClass->display_name ?? '' }}</span>
  </div>
  @forelse($subjectAssignments as $row)
  <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
    <div style="width:40px;height:40px;border-radius:8px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:14px;flex-shrink:0">
      <i class="fas fa-book"></i>
    </div>
    <div style="flex:1">
      <div style="font-weight:700;font-size:13.5px">{{ $row->subject->name ?? '—' }}</div>
      <div class="text-sm text-muted">{{ $row->subject->code ?? '' }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      @if($row->teacher?->photo)
        <img src="{{ storage_asset($row->teacher->photo) }}" alt="{{ $row->teacher->name }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
      @else
        <div style="width:32px;height:32px;border-radius:50%;background:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
          {{ strtoupper(substr($row->teacher->name ?? '?', 0, 2)) }}
        </div>
      @endif
      <div style="text-align:left">
        <div style="font-weight:600;font-size:13px">{{ $row->teacher->name ?? 'Teacher TBA' }}</div>
        <div class="text-sm text-muted" style="font-size:11px">Subject Teacher</div>
      </div>
    </div>
  </div>
  @empty
  <div class="empty-state"><i class="fas fa-book-open"></i><p>No subjects have been assigned to your class yet.</p></div>
  @endforelse
</div>
@endif
@endsection
