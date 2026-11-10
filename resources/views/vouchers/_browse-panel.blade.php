{{-- ======================================================================
     Session -> Program -> Student browse panel (Steps 1 & 2 of the
     "Browse by Program" tab). Rendered on first page load AND returned as
     a bare fragment for AJAX session-tab / program-card / breadcrumb
     navigation (see the script block in vouchers/index.blade.php), so this
     file must never reference anything outside itself.
====================================================================== --}}

{{-- Session tabs — "All Sessions" plus one tab per Academic Year. Picking a
     tab re-loads this panel scoped to that session; the Program cards (and,
     if already drilled into a program, the Student list) update to match. --}}
@php $sessionTabProgramId = $mode === 'students' ? ($program->id ?? null) : null; @endphp
<div class="session-tabs">
  <a href="{{ route('vouchers.index', array_filter(['program_id' => $sessionTabProgramId])) }}"
     data-ajax-nav
     class="session-tab {{ !$selectedYearId ? 'active' : '' }}">
    <i class="fas fa-layer-group"></i> All Sessions
  </a>
  @foreach($academicYears as $year)
  <a href="{{ route('vouchers.index', array_filter(['academic_year_id' => $year->id, 'program_id' => $sessionTabProgramId])) }}"
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
  Select a program to view its students, then a student to see their fees semester-by-semester.
  @if($selectedYear) <strong>Session: {{ $selectedYear->name }}</strong> @endif
</p>

<div class="card filter-card">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="{{ route('vouchers.index') }}" class="filter-form" data-ajax-form>
      <input type="hidden" name="all" value="1">
      <div class="form-group" style="margin-bottom:0;min-width:180px">
        <label class="form-label">Session</label>
        <select name="academic_year_id" id="voucher-browse-session" class="form-control">
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
          <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:240px">
        <label class="form-label">Program</label>
        <select name="program_id" id="voucher-browse-program" class="form-control" data-selected="{{ request('program_id') }}">
          <option value="">Select Session first</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:190px">
        <label class="form-label">Semester / Part</label>
        <select name="school_class_id" id="voucher-browse-class" class="form-control" data-selected="{{ request('school_class_id') }}">
          <option value="">Select Program first</option>
        </select>
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply Filters</button>
      </div>
    </form>
    <small style="color:var(--text-muted);display:block;margin-top:8px">Choose Session first. Program and Semester / Part only show options actually linked to that session.</small>
  </div>
</div>

<div class="card program-list-card">
  <div class="table-wrap">
    <table class="program-list-table">
      <thead>
        <tr>
          <th>Program</th>
          <th style="text-align:center">Students</th>
          <th style="text-align:center">Vouchers</th>
          <th style="text-align:right">Pending Balance</th>
          <th>Status</th>
          <th style="width:110px">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($programs as $p)
        <tr>
          <td>
            <div class="program-list-name">
              <span class="program-list-icon"><i class="fas fa-graduation-cap"></i></span>
              <div>
                <div class="program-list-title">{{ $p->name }}</div>
                @if($selectedYear)
                  <div class="program-list-session">{{ $selectedYear->name }}</div>
                @endif
              </div>
            </div>
          </td>
          <td style="text-align:center"><span class="program-list-number">{{ $p->student_count }}</span></td>
          <td style="text-align:center"><span class="program-list-number">{{ $p->voucher_count }}</span></td>
          <td style="text-align:right">
            <span class="program-list-balance {{ $p->pending_total > 0 ? 'has-due' : 'is-clear' }}">
              Rs. {{ number_format($p->pending_total, 0) }}
            </span>
          </td>
          <td>
            @if($p->pending_total > 0)
              <span class="badge danger">Pending</span>
            @else
              <span class="badge success">All clear</span>
            @endif
          </td>
          <td>
            <a href="{{ route('vouchers.index', array_filter(['program_id' => $p->id, 'academic_year_id' => $selectedYearId])) }}"
               data-ajax-nav
               class="btn btn-outline btn-sm program-view-btn"
               title="View {{ $p->name }} students">
              <i class="fas fa-eye"></i> View
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">
            No programs found{{ $selectedYear ? ' for ' . $selectedYear->name : '' }}.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endif

{{-- ====================== STEP 2 - STUDENTS IN PROGRAM ====================== --}}
@if($mode === 'students')

<div class="breadcrumb-row">
  <a href="{{ route('vouchers.index', array_filter(['academic_year_id' => $selectedYearId])) }}" data-ajax-nav><i class="fas fa-sitemap"></i> Programs</a>
  <i class="fas fa-chevron-right"></i>
  <span>{{ $program->name }}</span>
  @if($selectedYear)
    <i class="fas fa-chevron-right"></i>
    <span>{{ $selectedYear->name }}</span>
  @endif
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('vouchers.index') }}" data-ajax-form style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input type="hidden" name="program_id" value="{{ $program->id }}">
      <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
      <input type="text" name="student_search" class="form-control" placeholder="Search student name or ID..." value="{{ request('student_search') }}" style="flex:1;min-width:220px">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
      @if(request('student_search'))<a href="{{ route('vouchers.index', array_filter(['program_id' => $program->id, 'academic_year_id' => $selectedYearId])) }}" data-ajax-nav class="btn btn-outline btn-sm">Reset</a>@endif
    </form>
  </div>
</div>

{{-- Bulk-actions toolbar — hidden until at least one student is checked --}}
<div id="bulkActionsBar" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;background:var(--card);border:1px solid var(--primary);border-radius:var(--radius);box-shadow:var(--shadow);padding:12px 16px;margin-bottom:14px">
  <span style="font-weight:800;font-size:13px"><span id="bulkSelectedCount">0</span> selected</span>
  <button type="button" class="btn btn-outline btn-sm" onclick="submitBulkExport()"><i class="fas fa-file-export"></i> Export Selected</button>
  <button type="button" class="btn btn-outline btn-sm" style="margin-left:auto" onclick="clearBulkSelection()"><i class="fas fa-xmark"></i> Clear Selection</button>
</div>

<form id="bulkExportForm" method="POST" action="{{ route('vouchers.export-selected') }}" target="_blank" style="display:none">
  @csrf
  <div id="bulkExportIds"></div>
</form>

<div class="card">
  <div class="table-wrap">
   <table class="students-table">
    <thead>
      <tr>
        <th style="width:34px"><input type="checkbox" id="selectAllStudents"></th>
        <th>Photo</th>
        <th>Student</th>
        <th>Semester / Part</th>
        <th>Roll No</th>
        <th>Father Name</th>
        <th>Contact</th>
        <th style="text-align:right">Pending Balance</th>
        <th>Fee Status</th><th>Student Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($students as $s)
      <tr>
        <td><input type="checkbox" class="student-select-box" @disabled(!$s->isActiveEnrollment()) name="selected_student_ids[]" value="{{ $s->id }}"></td>
        <td>
          @if($s->photo)
            <img src="{{ storage_asset($s->photo) }}" alt="{{ $s->name }}" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
          @else
            <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px">{{ strtoupper(substr($s->name,0,2)) }}</div>
          @endif
        </td>
        <td>
          <div style="font-weight:700">{{ $s->name }}</div>
          <div style="font-size:11px;color:var(--text-muted)">{{ $s->student_id }}</div>
        </td>
        <td>{{ $s->schoolClass->section_label ?? ($s->schoolClass->name ?? '—') }}</td>
        <td>{{ $s->roll_number ?: '—' }}</td>
        <td>{{ $s->father_name ?: ($s->guardian_name ?: '—') }}</td>
        <td>{{ $s->phone }}</td>
        <td style="text-align:right">
          <span style="font-weight:700;color:{{ $s->pending_total > 0 ? 'var(--danger)' : 'var(--success)' }}">Rs. {{ number_format($s->pending_total, 0) }}</span>
        </td>
        <td>
          @if($s->pending_total > 0)
            <span class="badge danger">Due</span>
          @elseif($s->voucher_count > 0)
            <span class="badge success">Paid up</span>
          @else
            <span class="badge gray">No vouchers</span>
          @endif
        </td>
        <td>@include('partials.student-status-badge', ['statusStudent' => $s])</td>
        <td>
          <a href="{{ route('vouchers.index', ['program_id' => $program->id, 'student_id' => $s->id]) }}" class="btn btn-outline btn-sm" title="View Vouchers">
            <i class="fas fa-receipt"></i> View
          </a>
        </td>
      </tr>
      @empty
      <tr><td colspan="11" style="text-align:center;padding:40px;color:var(--text-muted)">No students found{{ $selectedYear ? ' in ' . $selectedYear->name : '' }} for this program.</td></tr>
      @endforelse
    </tbody>
   </table>
  </div>
</div>

@endif
