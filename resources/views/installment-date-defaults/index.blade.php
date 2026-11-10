@extends('layouts.app')
@section('title', 'Default Installment Dates')
@section('page_title', 'Default Installment Dates')
@section('content')

@push('styles')
<style>
  .default-date-page .setup-card{overflow:hidden;border-radius:16px}
  .default-date-page .setup-card .card-header{padding:16px 20px;background:linear-gradient(135deg,rgba(137,12,37,.055),rgba(137,12,37,.015));border-bottom:1px solid var(--border,#e2e8f0)}
  .default-date-page .setup-card .card-body{padding:20px}
  .default-date-page .structure-note{border-radius:11px!important;padding:12px 14px!important;background:rgba(137,12,37,.035)!important}
  .default-date-page .schedule-toolbar{padding:12px 14px;border:1px solid var(--border,#e2e8f0);border-radius:12px;background:var(--surface,#fff)}
  .default-date-page .semester-schedule-card{position:relative;border:1px solid var(--border,#e2e8f0)!important;border-radius:14px!important;padding:16px!important;background:var(--surface,#fff)!important;box-shadow:0 5px 16px rgba(15,23,42,.045)}
  .default-date-page .semester-schedule-card:before{content:'';position:absolute;left:0;top:16px;bottom:16px;width:3px;border-radius:0 6px 6px 0;background:var(--primary,#890c25)}
  .default-date-page .semester-field{min-width:220px;flex:0 0 245px;padding-left:4px}
  .default-date-page .semester-field .field-kicker,.default-date-page .dates-section-title{font-size:11px;letter-spacing:.03em;text-transform:uppercase;color:var(--text-muted,#64748b);font-weight:800}
  .default-date-page .installment-date-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(205px,1fr));gap:10px}
  .default-date-page .default-date-item{border:1px solid var(--border,#e2e8f0);border-radius:11px;padding:9px;background:var(--bg,#f8fafc);transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
  .default-date-page .default-date-item:focus-within{border-color:var(--primary,#890c25);box-shadow:0 0 0 3px rgba(137,12,37,.08);transform:translateY(-1px)}
  .default-date-page .installment-date-label{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px}
  .default-date-page .installment-number{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;color:var(--primary,#890c25)}
  .default-date-page .installment-number:before{content:'\f073';font-family:'Font Awesome 6 Free';font-weight:900;font-size:10px}
  .default-date-page .installment-date-caption{font-size:10.5px;color:var(--text-muted,#64748b)}
  .default-date-page .date-input-row{display:flex;align-items:center;gap:6px}
  .default-date-page .date-input-row .form-control{min-width:0}
  .default-date-page .remove-date{flex:0 0 auto;width:34px;height:34px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:9px}
  .default-date-page .saved-date-pill{display:inline-flex;gap:6px;align-items:center;padding:6px 9px;border:1px solid var(--border,#e2e8f0);border-radius:999px;font-size:11.5px;background:var(--bg,#f8fafc)}
  .default-date-page .saved-date-pill strong{color:var(--primary,#890c25)}
  @media (max-width:820px){
    .default-date-page .setup-card .card-body{padding:14px}
    .default-date-page .semester-field{flex:1 1 100%;min-width:100%}
    .default-date-page .installment-date-grid{grid-template-columns:1fr}
  }
</style>
@endpush

<div class="default-date-page">
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px">
  <div>
    <div style="font-size:22px;font-weight:800">Default Installment Dates</div>
    <div style="font-size:13px;color:var(--text-muted);margin-top:4px;max-width:820px">
      Flow: <strong>Academic Session → linked Program scope → linked Semester/Part → installment dates</strong>. Choose <strong>All Programs</strong> to apply a term's dates to every linked Program that offers that term in the selected session. A Program-specific schedule overrides the All Programs schedule. Student dates can still be changed later without changing these defaults.
    </div>
  </div>
</div>

<div style="margin-bottom:16px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg);font-size:11.5px;color:var(--text-muted)">
  <i class="fas fa-diagram-project" style="color:var(--primary);margin-right:5px"></i>
  The academic structure is shared by Admin and Accounts. Session → Program → Semester links are
  @if(auth('web')->check())
    managed under <a href="{{ route('academic-years.index') }}" style="color:var(--primary);font-weight:700">Academic Sessions</a>.
  @else
    managed by Admin under <strong>Academic Sessions</strong>; Accounts uses those same links here.
  @endif
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
  <strong>Please fix the following:</strong>
  <ul style="margin:6px 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="card setup-card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-plus" style="color:var(--primary);margin-right:8px"></i>Create / Replace Session Defaults</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('installment-date-defaults.store') }}" id="default-date-form">
      @csrf
      <div class="grid grid-2" style="gap:14px;align-items:end">
        <div class="form-group">
          <label class="form-label">Academic Session *</label>
          <select name="academic_year_id" id="default-session-select" class="form-control" required>
            <option value="">Select session</option>
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}" {{ old('academic_year_id', request('academic_year_id')) == $year->id ? 'selected' : '' }}>{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Program Scope *</label>
          <select name="program_id" id="default-program-select" class="form-control">
            <option value="">Select session first</option>
          </select>
          <small style="color:var(--text-muted)">Only Programs linked to the selected session are available.</small>
        </div>
      </div>

      <div id="session-structure-note" class="structure-note" style="display:none;margin:2px 0 14px;border:1px solid var(--border);font-size:11.5px;color:var(--text-muted)"></div>

      <div class="schedule-toolbar" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:6px 0 12px">
        <div>
          <strong style="font-size:13px">Specific Semester / Part schedules</strong>
          <div style="font-size:11.5px;color:var(--text-muted)">Add the Semester / Part schedules you need. Installments are clearly numbered and each one can have its own exact default due date.</div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="add-semester-row"><i class="fas fa-plus"></i> Add Semester / Part</button>
      </div>

      <div id="schedule-rows" style="display:flex;flex-direction:column;gap:10px"></div>

      <div style="display:flex;justify-content:flex-end;margin-top:14px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Default Dates</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div class="card-title"><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:8px"></i>Saved Defaults</div>
    <form method="GET" action="{{ route('installment-date-defaults.index') }}" style="display:flex;gap:8px;flex-wrap:wrap" data-ajax-filter>
      <select name="academic_year_id" class="form-control" style="min-width:150px">
        <option value="">All sessions</option>
        @foreach($academicYears as $year)<option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach
      </select>
      <select name="program_id" class="form-control" style="min-width:160px">
        <option value="">All scopes</option>
        <option value="all" {{ request('program_id') === 'all' ? 'selected' : '' }}>All Programs only</option>
        @foreach($programs as $program)<option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>@endforeach
      </select>
      <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-filter"></i> Filter</button>
    </form>
  </div>
  <div class="card-body" style="padding:0;overflow-x:auto">
    <table class="table" style="margin:0;min-width:760px">
      <thead><tr><th>Session</th><th>Program Scope</th><th>Semester / Part</th><th>Default Installment Dates</th><th style="width:130px"></th></tr></thead>
      <tbody>
      @forelse($defaults as $default)
        <tr>
          <td><strong>{{ $default->academicYear->name ?? '—' }}</strong></td>
          <td>
            @if($default->program)
              {{ $default->program->name }}
            @else
              <span class="badge gray">All Linked Programs</span>
            @endif
          </td>
          <td><span class="badge warning">Semester / Part {{ $default->cycle_no }}</span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              @foreach($default->dates ?? [] as $i => $date)
                <span class="saved-date-pill">
                  <strong>{{ ordinal($i + 1) }} Installment</strong> {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                </span>
              @endforeach
            </div>
          </td>
          <td>
            <div style="display:flex;gap:6px;justify-content:flex-end">
              <a href="{{ route('installment-date-defaults.edit', $default) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
              <form method="POST" action="{{ route('installment-date-defaults.destroy', $default) }}" onsubmit="return confirm('Delete this default installment schedule?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;padding:34px;color:var(--text-muted)">No default installment dates have been configured yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

</div>

@push('scripts')
<script>
(() => {
  const structure = @json($sessionStructure);
  const sessionSelect = document.getElementById('default-session-select');
  const programSelect = document.getElementById('default-program-select');
  const note = document.getElementById('session-structure-note');
  const wrap = document.getElementById('schedule-rows');
  const addSemesterBtn = document.getElementById('add-semester-row');
  const oldProgram = @json((string) old('program_id', ''));
  let semesterIndex = 0;

  function currentSession() {
    return structure[String(sessionSelect.value)] || null;
  }

  function currentTerms() {
    const session = currentSession();
    if (!session) return [];
    if (!programSelect.value) return session.all_terms || [];
    const program = (session.programs || []).find(p => String(p.id) === String(programSelect.value));
    return program ? (program.terms || []) : [];
  }

  function populatePrograms(selected = '') {
    const session = currentSession();
    programSelect.innerHTML = '';
    if (!session) {
      programSelect.innerHTML = '<option value="">Select session first</option>';
      note.style.display = 'none';
      refreshTermSelects();
      return;
    }

    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All Programs linked to this session';
    programSelect.appendChild(all);

    (session.programs || []).forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.selected = String(program.id) === String(selected);
      programSelect.appendChild(option);
    });

    const programCount = (session.programs || []).length;
    const termLabels = (session.all_terms || []).map(t => t.label).join(', ');
    note.style.display = '';
    if (!programCount) {
      note.innerHTML = '<i class="fas fa-triangle-exclamation" style="color:#d97706;margin-right:5px"></i>No Programs / Semesters are linked to this session yet. Admin must open <strong>Academic Sessions</strong> and manage its links before default dates can be created.';
    } else {
      note.innerHTML = `<i class="fas fa-link" style="color:var(--primary);margin-right:5px"></i><strong>${programCount}</strong> linked Program(s). Available terms: ${termLabels || 'none'}.`;
    }
    refreshTermSelects();
  }

  function populateTermSelect(select, selected = '') {
    const terms = currentTerms();
    select.innerHTML = '<option value="">Select Semester / Part</option>';
    terms.forEach(term => {
      const option = document.createElement('option');
      option.value = term.cycle_no;
      option.textContent = term.label;
      option.selected = String(term.cycle_no) === String(selected);
      select.appendChild(option);
    });
    if (!terms.length) select.innerHTML = '<option value="">No linked Semester / Part for this scope</option>';
  }

  function refreshTermSelects() {
    wrap.querySelectorAll('.term-select').forEach(select => {
      const previous = select.value;
      populateTermSelect(select, previous);
    });
  }

  function ordinalInstallment(n) {
    const mod100 = n % 100;
    const suffix = (mod100 >= 11 && mod100 <= 13) ? 'th' : ({1:'st',2:'nd',3:'rd'}[n % 10] || 'th');
    return `${n}${suffix} Installment`;
  }

  function dateInputHtml(i) {
    return `<div class="default-date-item">
      <div class="installment-date-label">
        <span class="installment-number">Installment</span>
        <span class="installment-date-caption">Due date</span>
      </div>
      <div class="date-input-row">
        <input type="date" name="schedules[${i}][dates][]" class="form-control" required>
        <button type="button" class="btn btn-outline btn-sm remove-date" title="Remove installment date" aria-label="Remove installment date"><i class="fas fa-xmark"></i></button>
      </div>
    </div>`;
  }

  function renumberDates(row) {
    row.querySelectorAll('.default-date-item').forEach((item, index) => {
      const label = item.querySelector('.installment-number');
      if (label) label.textContent = ordinalInstallment(index + 1);
    });
  }

  function addDate(row, i, value = '') {
    const list = row.querySelector('.date-list');
    const holder = document.createElement('div');
    holder.innerHTML = dateInputHtml(i);
    const item = holder.firstElementChild;
    if (value) item.querySelector('input').value = value;
    item.querySelector('.remove-date').addEventListener('click', () => {
      if (list.querySelectorAll('.default-date-item').length > 1) {
        item.remove();
        renumberDates(row);
      }
    });
    list.appendChild(item);
    renumberDates(row);
  }

  function addSemesterRow(cycle = '', dates = ['']) {
    const i = semesterIndex++;
    const row = document.createElement('div');
    row.className = 'semester-schedule-card';
    row.innerHTML = `
      <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap">
        <div class="semester-field">
          <div class="field-kicker">Semester / Part</div>
          <select name="schedules[${i}][cycle_no]" class="form-control term-select" required style="margin-top:7px"></select>
        </div>
        <div style="flex:1;min-width:280px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px">
            <div>
              <div class="dates-section-title">Installment Schedule</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Each installment has its own default due date.</div>
            </div>
            <button type="button" class="btn btn-outline btn-sm add-date"><i class="fas fa-plus"></i> Add Installment</button>
          </div>
          <div class="date-list installment-date-grid"></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm remove-semester" title="Remove semester schedule" aria-label="Remove semester schedule"><i class="fas fa-trash"></i></button>
      </div>`;

    populateTermSelect(row.querySelector('.term-select'), cycle);
    row.querySelector('.add-date').addEventListener('click', () => addDate(row, i));
    row.querySelector('.remove-semester').addEventListener('click', () => {
      if (wrap.children.length > 1) row.remove();
    });
    wrap.appendChild(row);
    dates.forEach(date => addDate(row, i, date));
  }

  sessionSelect.addEventListener('change', () => populatePrograms(''));
  programSelect.addEventListener('change', refreshTermSelects);
  addSemesterBtn.addEventListener('click', () => addSemesterRow());

  populatePrograms(oldProgram);

  const oldSchedules = @json(old('schedules', []));
  if (Array.isArray(oldSchedules) && oldSchedules.length) {
    oldSchedules.forEach(s => addSemesterRow(s.cycle_no || '', Array.isArray(s.dates) && s.dates.length ? s.dates : ['']));
  } else {
    addSemesterRow('', ['']);
  }
})();
</script>
@endpush
@endsection
