@extends('layouts.app')
@section('title', 'Students')
@section('content')

@php $mode = $mode ?? 'programs'; @endphp

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">
      <i class="fas fa-user-graduate" style="color:var(--primary)"></i> Students
      @if($mode === 'table')
        <span style="color:var(--text-muted);font-size:16px">({{ method_exists($students, 'total') ? $students->total() : $students->count() }})</span>
      @elseif($mode === 'students')
        <span style="color:var(--text-muted);font-size:16px">({{ $students->count() }})</span>
      @endif
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Read-only directory for financial lookups. Admissions and profile edits are managed by EMS.</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button type="button" class="btn btn-primary" onclick="openStudentBulkInstallments()"><i class="fas fa-file-invoice"></i> Bulk Installment Vouchers</button>
    <a href="{{ route('accounts.students.exit-report') }}" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Drop / Left Report</a>
    <a href="{{ route('accounts.students.print', request()->query()) }}" target="_blank" class="btn btn-outline">
      <i class="fas fa-print"></i> Print
    </a>
    <a href="{{ route('accounts.students.export', request()->query()) }}" class="btn btn-outline">
      <i class="fas fa-file-excel"></i> Export
    </a>
  </div>
</div>

{{-- Students: bulk-print one *saved* installment for the selected cohort. --}}
<div id="studentBulkInstallmentModal" role="dialog" aria-modal="true" aria-labelledby="studentBulkInstallmentTitle"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);align-items:center;justify-content:center;z-index:500;padding:16px">
  <div class="card" style="width:100%;max-width:580px;max-height:calc(100vh - 32px);overflow-y:auto">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div class="card-title" id="studentBulkInstallmentTitle"><i class="fas fa-file-invoice" style="color:var(--primary)"></i> Bulk Installment Vouchers</div>
      <button type="button" class="btn btn-outline btn-sm" onclick="closeStudentBulkInstallments()" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <form id="studentBulkInstallmentForm" class="card-body" method="GET" target="_blank" action="{{ route('accounts.students.bulk-installments.print') }}">
      <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 16px">
        Print the exact installment vouchers already stored in each student's fee plan. No new fees, dates or voucher numbers are created.
      </p>
      <div class="form-group">
        <label class="form-label" for="bulk-installment-session">Session *</label>
        <select id="bulk-installment-session" name="academic_year_id" class="form-control" required>
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
            <option value="{{ $year->id }}">{{ $year->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="bulk-installment-program">Program *</label>
        <select id="bulk-installment-program" name="program_id" class="form-control" required disabled>
          <option value="">Select Session first</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="bulk-installment-class">Semester / Part *</label>
        <select id="bulk-installment-class" name="school_class_id" class="form-control" required disabled>
          <option value="">Select Program first</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="bulk-installment-number">Installment to print *</label>
        <select id="bulk-installment-number" name="installment_no" class="form-control" required disabled>
          <option value="">Select Semester / Part first</option>
        </select>
      </div>
      <div id="bulk-installment-info" aria-live="polite" style="font-size:12.5px;color:var(--text-muted);background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:11px 12px;margin:4px 0 16px">
        Choose a Session, Program and Semester to load the installment numbers from saved fee plans.
      </div>
      <button type="submit" class="btn btn-primary" id="bulk-installment-print" disabled style="width:100%;justify-content:center">
        <i class="fas fa-print"></i> Open Bulk Print / Download
      </button>
      <p style="font-size:11.5px;color:var(--text-muted);margin:12px 0 0">
        Example: choosing installment 5 prints only students whose saved plan contains a fifth voucher. Students with four installments are skipped. Split challans for the same installment are all included.
      </p>
    </form>
  </div>
</div>

{{-- Tabs: Browse (Session -> Program -> Student) is the default; All Students is the classic filterable table --}}
<div class="voucher-tabs">
  <a href="{{ route('accounts.students.index') }}" class="voucher-tab {{ $mode !== 'table' ? 'active' : '' }}"><i class="fas fa-sitemap"></i> Browse by Session</a>
  <a href="{{ route('accounts.students.index', ['all' => 1]) }}" class="voucher-tab {{ $mode === 'table' ? 'active' : '' }}"><i class="fas fa-table-list"></i> All Students</a>
</div>

{{-- ============ BROWSE BY SESSION - SESSION -> PROGRAM -> STUDENTS (AJAX) ============
     Session-tab clicks, program-card clicks, and the Step-2 breadcrumb/search all
     swap this panel in place via fetch() — see the script block below. No full
     page reload once you're on this tab. --}}
@if($mode === 'programs' || $mode === 'students')
<div id="browse-panel">
  @include('accounts.students._browse-panel')
</div>
@endif

{{-- ====================== ALL STUDENTS - CLASSIC FILTERABLE TABLE ====================== --}}
@if($mode === 'table')

<!-- Filters -->
<div class="card" style="margin:20px 0">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('accounts.students.index') }}" id="student-filter-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <input type="hidden" name="all" value="1">
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
        <select name="program_id" id="student-filter-program" class="form-control">
          <option value="">Select Session first</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:170px;flex:0.8">
        <label class="form-label">Semester / Part</label>
        <select name="school_class_id" id="student-filter-class" class="form-control">
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
      <a href="{{ route('accounts.students.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>

    <div id="student-filter-hint" style="font-size:11.5px;color:var(--text-muted);margin-top:9px">
      Choose a Session first; Program and Semester / Part will only show options actually linked to that session.
    </div>
  </div>
</div>

<!-- Students Table -->
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
            @include('accounts._students-row', ['student' => $student, 'sr' => $studentSerial++])
          @endforeach
        @empty
          <tr>
            <td colspan="12" style="text-align:center;padding:40px;color:var(--text-muted)">
              <i class="fas fa-user-graduate" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
              No students found.
            </td>
          </tr>
        @endforelse
      @else
        @forelse($students as $student)
          @include('accounts._students-row', ['student' => $student, 'sr' => $studentSerial++])
        @empty
        <tr>
          <td colspan="12" style="text-align:center;padding:40px;color:var(--text-muted)">
            <i class="fas fa-user-graduate" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
            No students found.
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

@endif
{{-- /ALL STUDENTS - CLASSIC FILTERABLE TABLE --}}

<style>
  .students-table th { font-size: 11.5px; padding: 10px 12px; white-space: nowrap; }
  .students-table td { font-size: 12.5px; padding: 10px 12px; vertical-align: middle; }
  .students-table td, .students-table td div { line-height: 1.35; }

  /* Tabs + Session tabs + browse cards — shared look with Vouchers' Browse by Program */
  .voucher-tabs { display:flex; gap:4px; border-bottom:1px solid var(--border); margin-bottom:20px; }
  .voucher-tab { padding:10px 18px; font-size:13px; font-weight:700; color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-1px; display:flex; align-items:center; gap:7px; border-radius:8px 8px 0 0; transition:background .12s ease, color .12s ease; }
  .voucher-tab.active { color:var(--primary); border-bottom-color:var(--primary); background:rgba(59,130,246,0.06); }
  .voucher-tab:hover:not(.active) { color:var(--text); background:var(--bg); }

  .session-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
  .session-tab { display:flex; align-items:center; gap:7px; padding:7px 14px; font-size:12.5px; font-weight:700; color:var(--text-muted); background:var(--card); border:1px solid var(--border); border-radius:999px; transition:background .12s ease, color .12s ease, border-color .12s ease; }
  .session-tab:hover:not(.active) { color:var(--text); border-color:var(--primary); }
  .session-tab.active { color:#fff; background:var(--primary); border-color:var(--primary); }
  .session-tab-count { font-size:10.5px; font-weight:800; background:rgba(0,0,0,0.12); border-radius:999px; padding:1px 7px; }
  .session-tab.active .session-tab-count { background:rgba(255,255,255,0.25); }
  #browse-panel.is-loading { opacity:0.5; pointer-events:none; transition:opacity .1s ease; }

  .breadcrumb-row { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-muted); margin-bottom:16px; flex-wrap:wrap; }
  .breadcrumb-row a { color:var(--primary); font-weight:600; }
  .breadcrumb-row i { font-size:9px; }

  .browse-grid { margin-bottom:8px; }
  .browse-card { display:flex; flex-direction:column; gap:6px; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:18px; transition:transform .15s ease, border-color .15s ease, box-shadow .15s ease; }
  .browse-card:hover { transform:translateY(-3px); border-color:var(--primary); box-shadow:0 8px 20px rgba(0,0,0,0.08); }
  .browse-card-icon { width:40px; height:40px; border-radius:10px; background:var(--bg); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:17px; }
  .browse-card-title { font-weight:800; font-size:15px; margin-top:4px; }
  .browse-card-sub { font-size:12px; color:var(--text-muted); }
  .browse-card-footer { display:flex; align-items:center; gap:8px; margin-top:8px; }

  @media print {
    .table-wrap { overflow: visible !important; }
    table { table-layout: auto; font-size: 11px; }
    th, td { padding: 6px 8px !important; }
    .no-print { display: none !important; }
  }
</style>

<script>
// ── Session -> Program -> Student browse panel, fully AJAX ────────────
// Session tabs, program cards, and the Step-2 breadcrumb/search all swap
// #browse-panel's contents via fetch() instead of reloading the page.
// Delegated on `document` (not #browse-panel itself) since the panel's
// own innerHTML gets replaced on every navigation.
(function () {
  const panel = document.getElementById('browse-panel');
  if (!panel) return; // on the "All Students" tab

  async function loadPanel(url, push = true) {
    panel.classList.add('is-loading');
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('Request failed');
      const html = await res.text();
      panel.innerHTML = html;
      if (push) history.pushState({ browsePanel: true }, '', url);
      panel.scrollIntoView({ block: 'nearest' });
    } catch (err) {
      window.location.href = url; // AJAX failed — fall back to a normal navigation
    } finally {
      panel.classList.remove('is-loading');
    }
  }

  document.addEventListener('click', function (e) {
    const link = e.target.closest('[data-ajax-nav]');
    if (!link || !panel.contains(link)) return;
    e.preventDefault();
    loadPanel(link.getAttribute('href'));
  });

  document.addEventListener('submit', function (e) {
    const form = e.target.closest('[data-ajax-form]');
    if (!form || !panel.contains(form)) return;
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    loadPanel(form.getAttribute('action') + '?' + params.toString());
  });

  window.addEventListener('popstate', function () {
    loadPanel(window.location.href, false);
  });
})();
</script>

<script>
// Students-only bulk printing. Use the SAME backend-linked Session > Program
// > Semester tree as the student directory. The installment options and counts
// are fetched from actual saved fee plans, not hard-coded or user-created.
(() => {
  const structure = @json($bulkInstallmentStructure ?? $filterStructure ?? []);
  const optionsUrl = @json(route('accounts.students.bulk-installments.options'));
  const modal = document.getElementById('studentBulkInstallmentModal');
  const form = document.getElementById('studentBulkInstallmentForm');
  const year = document.getElementById('bulk-installment-session');
  const program = document.getElementById('bulk-installment-program');
  const semester = document.getElementById('bulk-installment-class');
  const installment = document.getElementById('bulk-installment-number');
  const info = document.getElementById('bulk-installment-info');
  const print = document.getElementById('bulk-installment-print');
  let options = null;
  let requestSequence = 0;

  function option(value, label, disabled = false) {
    const el = document.createElement('option');
    el.value = value;
    el.textContent = label;
    el.disabled = disabled;
    return el;
  }
  function ordinal(n) {
    const suffix = n % 100 >= 11 && n % 100 <= 13 ? 'th'
      : (n % 10 === 1 ? 'st' : n % 10 === 2 ? 'nd' : n % 10 === 3 ? 'rd' : 'th');
    return n + suffix;
  }
  function reset(select, hint) {
    select.replaceChildren(option('', hint));
    select.disabled = true;
    select.value = '';
  }
  function resetInstallments(hint) {
    requestSequence++;
    options = null;
    reset(installment, hint);
    print.disabled = true;
    info.textContent = 'Choose a Session, Program and Semester to load the saved installment options.';
  }
  function fillPrograms() {
    reset(program, year.value ? 'Select Program' : 'Select Session first');
    reset(semester, 'Select Program first');
    resetInstallments('Select Semester / Part first');
    const programs = structure[String(year.value)]?.programs || [];
    programs.forEach(p => program.appendChild(option(p.id, p.name)));
    program.disabled = programs.length === 0;
  }
  function fillSemesters() {
    reset(semester, program.value ? 'Select Semester / Part' : 'Select Program first');
    resetInstallments('Select Semester / Part first');
    const p = (structure[String(year.value)]?.programs || []).find(p => String(p.id) === String(program.value));
    (p?.terms || []).forEach(t => semester.appendChild(option(t.id, t.name)));
    semester.disabled = !p || !p.terms?.length;
  }
  function updateSelectionCount() {
    const selected = options?.installments?.find(x => String(x.number) === String(installment.value));
    print.disabled = !selected || selected.student_count < 1;
    if (!options) return;
    if (!selected) {
      info.textContent = `${options.students_with_plans} of ${options.total_students} students have saved installment plans. Choose the installment to print.`;
      return;
    }
    info.textContent = `${ordinal(selected.number)} installment: ${selected.student_count} eligible student(s), ${selected.voucher_count} printable challan(s). Only this installment will print. Other students are skipped.`;
  }
  async function loadInstallments() {
    resetInstallments('Loading saved installment plans...');
    if (!year.value || !program.value || !semester.value) return;
    const thisRequest = ++requestSequence;
    info.textContent = 'Checking saved installment plans and eligible student vouchers...';
    const params = new URLSearchParams({
      academic_year_id: year.value, program_id: program.value, school_class_id: semester.value,
    });
    try {
      const response = await fetch(optionsUrl + '?' + params, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!response.ok) throw new Error('Unable to load saved plans for this selection.');
      const data = await response.json();
      if (thisRequest !== requestSequence) return; // ignore late responses for an older class
      options = data;
      reset(installment, data.max_installments ? 'Select Installment' : 'No saved installment plan');
      (data.installments || []).forEach(part => {
        installment.appendChild(option(part.number,
          `${ordinal(part.number)} Installment — ${part.student_count} student(s), ${part.voucher_count} challan(s)`,
          part.student_count === 0));
      });
      installment.disabled = !data.max_installments;
      info.textContent = data.max_installments
        ? `${data.students_with_plans} of ${data.total_students} students have saved plans; installments 1–${data.max_installments} are configured. Select which one to print.`
        : 'No installment plans are saved for students in this semester.';
    } catch (error) {
      if (thisRequest !== requestSequence) return;
      reset(installment, 'Unable to load installments');
      info.textContent = error.message;
    }
  }

  year.addEventListener('change', fillPrograms);
  program.addEventListener('change', fillSemesters);
  semester.addEventListener('change', loadInstallments);
  installment.addEventListener('change', updateSelectionCount);
  form.addEventListener('submit', e => {
    if (print.disabled || !options) e.preventDefault();
  });

  window.openStudentBulkInstallments = function () {
    modal.style.display = 'flex';
    const search = new URLSearchParams(window.location.search);
    const selectedYear = search.get('academic_year_id') || search.get('academic_year');
    const selectedProgram = search.get('program_id') || search.get('program');
    const selectedClass = search.get('school_class_id') || search.get('class');
    if (selectedYear && structure[String(selectedYear)]) {
      year.value = selectedYear;
      fillPrograms();
      if (selectedProgram && [...program.options].some(o => o.value === selectedProgram)) {
        program.value = selectedProgram;
        fillSemesters();
        if (selectedClass && [...semester.options].some(o => o.value === selectedClass)) {
          semester.value = selectedClass;
          loadInstallments();
        }
      }
    }
    year.focus();
  };
  window.closeStudentBulkInstallments = function () { modal.style.display = 'none'; };
  modal.addEventListener('click', e => { if (e.target === modal) closeStudentBulkInstallments(); });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.style.display === 'flex') closeStudentBulkInstallments();
  });
  if (new URLSearchParams(window.location.search).get('bulk_installments') === '1') {
    openStudentBulkInstallments();
  }
})();
</script>

@if($mode === 'table')
@push('scripts')
<script>
(() => {
  const structure = @json($filterStructure);
  const sessionSelect = document.getElementById('student-filter-session');
  const programSelect = document.getElementById('student-filter-program');
  const classSelect = document.getElementById('student-filter-class');
  const selectedProgram = @json((string) request('program_id', ''));
  const selectedClass = @json((string) request('school_class_id', ''));

  function currentSession() {
    return structure[String(sessionSelect.value)] || null;
  }

  function currentProgram() {
    const session = currentSession();
    if (!session) return null;
    return (session.programs || []).find(program => String(program.id) === String(programSelect.value)) || null;
  }

  function populateClasses(selected = '') {
    const program = currentProgram();
    classSelect.innerHTML = '';

    if (!program) {
      classSelect.innerHTML = `<option value="">${sessionSelect.value ? 'Select Program first' : 'Select Session first'}</option>`;
      classSelect.disabled = true;
      return;
    }

    classSelect.disabled = false;
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All linked Semester / Parts';
    classSelect.appendChild(all);

    (program.terms || []).forEach(term => {
      const option = document.createElement('option');
      option.value = term.id;
      option.textContent = term.name;
      option.selected = String(term.id) === String(selected);
      classSelect.appendChild(option);
    });

    if (!(program.terms || []).length) {
      classSelect.innerHTML = '<option value="">No linked Semester / Part</option>';
      classSelect.disabled = true;
    }
  }

  function populatePrograms(selected = '', selectedTerm = '') {
    const session = currentSession();
    programSelect.innerHTML = '';

    if (!session) {
      programSelect.innerHTML = '<option value="">Select Session first</option>';
      programSelect.disabled = true;
      populateClasses('');
      return;
    }

    programSelect.disabled = false;
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All Programs in this Session';
    programSelect.appendChild(all);

    (session.programs || []).forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.selected = String(program.id) === String(selected);
      programSelect.appendChild(option);
    });

    if (!(session.programs || []).length) {
      programSelect.innerHTML = '<option value="">No Programs linked to this Session</option>';
      programSelect.disabled = true;
    }

    populateClasses(selectedTerm);
  }

  sessionSelect.addEventListener('change', () => populatePrograms('', ''));
  programSelect.addEventListener('change', () => populateClasses(''));
  populatePrograms(selectedProgram, selectedClass);
})();
</script>
@endpush
@endif

@endsection
