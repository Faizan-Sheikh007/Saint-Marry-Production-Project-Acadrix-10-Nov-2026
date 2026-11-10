{{-- ======================================================================
     Session -> Program -> Student browse panel ("Browse by Session" tab,
     Steps 1 & 2). Rendered on first page load AND returned as a bare
     fragment for AJAX session-tab / program-card / breadcrumb navigation
     (see the script block in accounts/students-index.blade.php), so this
     file must never reference anything outside itself.
====================================================================== --}}

@php $sessionTabProgramId = $mode === 'students' ? ($program->id ?? null) : null; @endphp
<div class="session-tabs">
  <a href="{{ route('accounts.students.index', array_filter(['program_id' => $sessionTabProgramId])) }}"
     data-ajax-nav
     class="session-tab {{ !$selectedYearId ? 'active' : '' }}">
    <i class="fas fa-layer-group"></i> All Sessions
  </a>
  @foreach($academicYears as $year)
  <a href="{{ route('accounts.students.index', array_filter(['academic_year_id' => $year->id, 'program_id' => $sessionTabProgramId])) }}"
     data-ajax-nav
     class="session-tab {{ $selectedYearId == $year->id ? 'active' : '' }}">
    {{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}
    <span class="session-tab-count">{{ $year->student_count }}</span>
  </a>
  @endforeach
</div>

{{-- ====================== STEP 1 - PROGRAMS ====================== --}}
@if($mode === 'programs')

<p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">
  Select a program to view its students.
  @if($selectedYear) <strong>Session: {{ $selectedYear->name }}</strong> @endif
</p>

<div class="grid grid-3 browse-grid">
  @forelse($programs as $p)
  <a href="{{ route('accounts.students.index', array_filter(['program_id' => $p->id, 'academic_year_id' => $selectedYearId])) }}" data-ajax-nav class="browse-card">
    <div class="browse-card-icon"><i class="fas fa-graduation-cap"></i></div>
    <div class="browse-card-title">{{ $p->name }}</div>
    <div class="browse-card-sub">{{ $p->student_count }} student{{ $p->student_count == 1 ? '' : 's' }}</div>
    <div class="browse-card-footer">
      @if($p->pending_total > 0)
        <span class="badge danger">Rs. {{ number_format($p->pending_total, 0) }} pending</span>
      @else
        <span class="badge success">All clear</span>
      @endif
      <i class="fas fa-chevron-right" style="margin-left:auto;color:var(--text-muted)"></i>
    </div>
  </a>
  @empty
  <div class="card"><div class="card-body" style="text-align:center;color:var(--text-muted);padding:40px">No programs found{{ $selectedYear ? ' for ' . $selectedYear->name : '' }}.</div></div>
  @endforelse
</div>

@endif

{{-- ====================== STEP 2 - STUDENTS IN PROGRAM ====================== --}}
@if($mode === 'students')

<div class="breadcrumb-row">
  <a href="{{ route('accounts.students.index', array_filter(['academic_year_id' => $selectedYearId])) }}" data-ajax-nav><i class="fas fa-sitemap"></i> Programs</a>
  <i class="fas fa-chevron-right"></i>
  <span>{{ $program->name }}</span>
  @if($selectedYear)
    <i class="fas fa-chevron-right"></i>
    <span>{{ $selectedYear->name }}</span>
  @endif
  <span style="color:var(--text-muted)">({{ $students->count() }})</span>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('accounts.students.index') }}" data-ajax-form style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input type="hidden" name="program_id" value="{{ $program->id }}">
      <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
      <input type="text" name="student_search" class="form-control" placeholder="Search student name, ID or roll no..." value="{{ request('student_search') }}" style="flex:1;min-width:220px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
      @if(request('student_search'))<a href="{{ route('accounts.students.index', array_filter(['program_id' => $program->id, 'academic_year_id' => $selectedYearId])) }}" data-ajax-nav class="btn btn-outline btn-sm">Reset</a>@endif
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
   <table class="students-table">
    <thead>
      <tr>
        <th style="text-align:center">SR #</th>
        <th>Student ID</th>
        <th>Ledger No.</th>
        <th>Photo</th>
        <th>Student</th>
        <th>Program / Part</th>
        <th>Roll No</th>
        <th>Father Name</th>
        <th>Contact</th>
        <th style="text-align:right">Pending Balance</th>
        <th>Fees Status</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @php $studentSerial = 1; @endphp
      @forelse($students as $student)
        @include('accounts._students-row', ['student' => $student, 'sr' => $studentSerial++])
      @empty
        <tr>
          <td colspan="12" style="text-align:center;padding:40px;color:var(--text-muted)">
            <i class="fas fa-user-graduate" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
            No students found{{ $selectedYear ? ' in ' . $selectedYear->name : '' }} for this program.
          </td>
        </tr>
      @endforelse
    </tbody>
   </table>
  </div>
</div>

@endif
