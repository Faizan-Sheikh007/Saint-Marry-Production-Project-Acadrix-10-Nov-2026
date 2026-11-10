{{-- ======================================================================
     Students list panel — session tabs, filter card, and the results table.
     Rendered on first page load AND returned as a bare fragment for AJAX
     session-tab / filter-form / pagination navigation (see the script block
     in students/index.blade.php), so this file must never reference
     anything outside itself. Everything filter-dependent (Print/Export
     links, the results count, session-tab badges) lives in here rather than
     the parent page so it stays in sync after an AJAX swap.
====================================================================== --}}

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:12px">
  <div style="font-size:13px;color:var(--text-muted);font-weight:700">
    @php $resultCount = method_exists($students, 'total') ? $students->total() : $students->count(); @endphp
    {{ number_format($resultCount) }} student{{ $resultCount === 1 ? '' : 's' }} found
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('students.print', request()->query()) }}" target="_blank" class="btn btn-outline btn-sm">
      <i class="fas fa-print"></i> Print
    </a>
    <a href="{{ route('students.export', request()->query()) }}" class="btn btn-outline btn-sm">
      <i class="fas fa-file-excel"></i> Export
    </a>
  </div>
</div>

{{-- Session tabs — jump straight to a session without opening the dropdown.
     "All Sessions" clears the session filter but keeps Search/Program/
     Class/Status/Grouped as-is; each tab's badge respects those too. --}}
<div class="session-tabs">
  <a href="{{ route('students.index', array_filter(request()->except(['academic_year', 'page']))) }}"
     data-ajax-nav
     class="session-tab {{ !request('academic_year') ? 'active' : '' }}">
    <i class="fas fa-layer-group"></i> All Sessions
    <span class="session-tab-count">{{ $allSessionsCount }}</span>
  </a>
  @foreach($academicYears as $year)
  <a href="{{ route('students.index', array_filter(array_merge(request()->except(['academic_year', 'page']), ['academic_year' => $year->id]))) }}"
     data-ajax-nav
     class="session-tab {{ request('academic_year') == $year->id ? 'active' : '' }}">
    {{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}
    <span class="session-tab-count">{{ $year->student_count }}</span>
  </a>
  @endforeach
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('students.index') }}" id="student-filter-form" data-ajax-form style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:190px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name, Roll No, ID..." value="{{ request('search') }}">
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:170px;flex:0.8">
        <label class="form-label">Session</label>
        <select name="academic_year" id="student-filter-session" class="form-control">
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}" {{ request('academic_year') == $year->id ? 'selected' : '' }}>{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:260px;flex:1.35">
        <label class="form-label">Program</label>
        <select name="program" id="student-filter-program" class="form-control">
          <option value="">Select Session first</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:170px;flex:0.8">
        <label class="form-label">Semester / Part</label>
        <select name="class" id="student-filter-class" class="form-control">
          <option value="">Select Program first</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:120px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
          <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
          <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
          <option value="dropped" {{ request('status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
          <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transferred</option>
          <option value="left" {{ request('status') == 'left' ? 'selected' : '' }}>Left</option>
          <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0">
        <label class="form-label" style="display:block">&nbsp;</label>
        <label style="display:flex;align-items:center;gap:6px;height:38px;font-size:13px;font-weight:600;white-space:nowrap">
          <input type="checkbox" name="grouped" value="1" {{ request()->boolean('grouped') ? 'checked' : '' }} onchange="this.form.requestSubmit()">
          Group by Program &amp; Session
        </label>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('students.index') }}" data-ajax-nav class="btn btn-outline">Reset</a>
    </form>

    <div id="student-filter-hint" style="font-size:11.5px;color:var(--text-muted);margin-top:9px">
      Choose a Session first; Program and Semester / Part will only show options actually linked to that session.
    </div>
  </div>
</div>

<!-- Bulk selection bar (shown when at least one student is ticked) -->
<div id="student-bulk-bar" class="no-print" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;padding:10px 16px;border-radius:12px;background:var(--card);border:1px solid var(--primary)">
  <div style="font-size:13px;font-weight:800;color:var(--primary)">
    <i class="fas fa-check-square"></i> <span id="student-bulk-count">0</span> selected
  </div>
  <div style="margin-left:auto;display:flex;gap:8px">
    <button type="button" class="btn btn-outline btn-sm" id="student-bulk-clear">Clear</button>
    <button type="button" class="btn btn-sm" id="student-bulk-delete" style="background:var(--danger);color:#fff;border:none">
      <i class="fas fa-trash"></i> Delete Selected
    </button>
  </div>
</div>

<!-- Students Table -->
<div class="card">
  <div class="table-wrap">
   <table class="students-table">
    <thead>
      <tr>
        <th style="text-align:center">SR #</th>
        <th>
          <label style="display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer" title="Select all students on this page">
            <input type="checkbox" id="student-select-all" aria-label="Select all students" style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer">
            Student ID
          </label>
        </th>
        <th>Photo</th>
        <th>Roll Number</th>
        <th>Name</th>
        <th>Father Name</th>
        <th>Program</th>
        <th>Contact</th>
        <th style="text-align:right">Pending Balance</th>
        <th>Student Status</th>
        <th>Fees Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      @php
        $studentSerial = (!isset($grouped) && method_exists($students, 'firstItem'))
          ? ($students->firstItem() ?? 1)
          : 1;
      @endphp
      @if(isset($grouped))
        @forelse($grouped as $groupKey => $groupStudents)
          @php [$groupProgram, $groupYear] = explode('||', $groupKey, 2); @endphp
          <tr class="group-header-row" style="background:var(--bg)">
            <td colspan="12" style="font-weight:800;padding:10px 16px">
              <i class="fas fa-sitemap" style="color:var(--primary);margin-right:6px"></i>
              {{ $groupProgram }} <span style="color:var(--text-muted);font-weight:600">&middot; {{ $groupYear }}</span>
              <span style="color:var(--text-muted);font-weight:600;font-size:12px">({{ $groupStudents->count() }} student{{ $groupStudents->count() === 1 ? '' : 's' }})</span>
            </td>
          </tr>
          @foreach($groupStudents as $student)
            @include('students._row', ['student' => $student, 'sr' => $studentSerial++])
          @endforeach
        @empty
          <tr>
            <td colspan="12" style="text-align:center;padding:40px;color:var(--text-muted)">
              <i class="fas fa-user-graduate" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
              No students found.
              <a href="{{ route('students.create') }}" style="color:var(--primary)">Add your first student</a>
            </td>
          </tr>
        @endforelse
      @else
        @forelse($students as $student)
          @include('students._row', ['student' => $student, 'sr' => $studentSerial++])
        @empty
        <tr>
          <td colspan="12" style="text-align:center;padding:40px;color:var(--text-muted)">
            <i class="fas fa-user-graduate" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
            No students found.
            <a href="{{ route('students.create') }}" style="color:var(--primary)">Add your first student</a>
          </td>
        </tr>
        @endforelse
      @endif
    </tbody>
</table>
  </div>
  <div style="padding:16px 20px">
    @if(!isset($grouped))
      {{ $students->withQueryString()->links() }}
    @endif
  </div>
</div>
