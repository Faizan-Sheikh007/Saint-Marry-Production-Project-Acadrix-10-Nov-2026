@extends('layouts.app')
@section('title', 'Edit Default Installment Dates')
@section('page_title', 'Edit Default Installment Dates')
@section('content')

@push('styles')
<style>
  .edit-default-date-page .edit-card{overflow:hidden;border-radius:16px;max-width:980px}
  .edit-default-date-page .edit-card .card-header{padding:16px 20px;background:linear-gradient(135deg,rgba(137,12,37,.055),rgba(137,12,37,.015));border-bottom:1px solid var(--border,#e2e8f0)}
  .edit-default-date-page .edit-card .card-body{padding:20px}
  .edit-default-date-page .dates-panel{padding:14px;border:1px solid var(--border,#e2e8f0);border-radius:13px;background:var(--surface,#fff);margin-top:4px}
  .edit-default-date-page .installment-date-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
  .edit-default-date-page .default-date-item{border:1px solid var(--border,#e2e8f0);border-radius:11px;padding:9px;background:var(--bg,#f8fafc);transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
  .edit-default-date-page .default-date-item:focus-within{border-color:var(--primary,#890c25);box-shadow:0 0 0 3px rgba(137,12,37,.08);transform:translateY(-1px)}
  .edit-default-date-page .installment-date-label{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px}
  .edit-default-date-page .installment-number{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:800;color:var(--primary,#890c25)}
  .edit-default-date-page .installment-number:before{content:'\f073';font-family:'Font Awesome 6 Free';font-weight:900;font-size:10px}
  .edit-default-date-page .installment-date-caption{font-size:10.5px;color:var(--text-muted,#64748b)}
  .edit-default-date-page .date-input-row{display:flex;align-items:center;gap:6px}
  .edit-default-date-page .date-input-row .form-control{min-width:0}
  .edit-default-date-page .remove-date{flex:0 0 auto;width:34px;height:34px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:9px}
  @media (max-width:720px){
    .edit-default-date-page .edit-card .card-body{padding:14px}
    .edit-default-date-page .installment-date-grid{grid-template-columns:1fr}
  }
</style>
@endpush

<div class="edit-default-date-page">
<div style="margin-bottom:16px">
  <a href="{{ route('installment-date-defaults.index', ['academic_year_id' => $installmentDateDefault->academic_year_id]) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Default Dates</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
  <strong>Please fix the following:</strong>
  <ul style="margin:6px 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="card edit-card">
  <div class="card-header"><div class="card-title"><i class="fas fa-calendar-pen" style="color:var(--primary);margin-right:8px"></i>Edit Session-Linked Schedule</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('installment-date-defaults.update', $installmentDateDefault) }}" id="edit-default-form">
      @csrf @method('PUT')

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Academic Session *</label>
          <select name="academic_year_id" id="edit-default-session" class="form-control" required>
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}" {{ old('academic_year_id', $installmentDateDefault->academic_year_id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Program Scope</label>
          <select name="program_id" id="edit-default-program" class="form-control"></select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Linked Semester / Part *</label>
        <select name="cycle_no" id="edit-default-term" class="form-control" required></select>
        <small style="color:var(--text-muted)">Only semesters linked to the selected session and Program scope are allowed.</small>
      </div>

      <div class="form-group dates-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap">
          <div>
            <label class="form-label" style="margin:0">Default Installment Schedule *</label>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">Installments are numbered automatically. Set the default due date for each installment.</div>
          </div>
          <button type="button" class="btn btn-outline btn-sm" id="add-date"><i class="fas fa-plus"></i> Add Installment</button>
        </div>
        <div id="dates-list" class="installment-date-grid"></div>
      </div>

      <div style="padding:10px 12px;border-radius:8px;background:var(--bg);border:1px solid var(--border);font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
        These are defaults for future/generated installments. A student's own due dates can be changed later from Vouchers &amp; Installments without changing this schedule.
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px">
        <a href="{{ route('installment-date-defaults.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

</div>

@push('scripts')
<script>
(() => {
  const structure = @json($sessionStructure);
  const sessionSelect = document.getElementById('edit-default-session');
  const programSelect = document.getElementById('edit-default-program');
  const termSelect = document.getElementById('edit-default-term');
  const initialProgram = @json((string) old('program_id', $installmentDateDefault->program_id ?? ''));
  const initialTerm = @json((string) old('cycle_no', $installmentDateDefault->cycle_no));
  let firstLoad = true;

  function sessionData() { return structure[String(sessionSelect.value)] || null; }
  function terms() {
    const session = sessionData();
    if (!session) return [];
    if (!programSelect.value) return session.all_terms || [];
    const program = (session.programs || []).find(p => String(p.id) === String(programSelect.value));
    return program ? (program.terms || []) : [];
  }

  function fillPrograms(selected = '') {
    const session = sessionData();
    programSelect.innerHTML = '<option value="">All Programs linked to this session</option>';
    (session?.programs || []).forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.selected = String(program.id) === String(selected);
      programSelect.appendChild(option);
    });
    fillTerms(firstLoad ? initialTerm : '');
  }

  function fillTerms(selected = '') {
    termSelect.innerHTML = '<option value="">Select Semester / Part</option>';
    terms().forEach(term => {
      const option = document.createElement('option');
      option.value = term.cycle_no;
      option.textContent = term.label;
      option.selected = String(term.cycle_no) === String(selected);
      termSelect.appendChild(option);
    });
    if (!terms().length) termSelect.innerHTML = '<option value="">No linked Semester / Part for this scope</option>';
  }

  sessionSelect.addEventListener('change', () => { firstLoad = false; fillPrograms(''); });
  programSelect.addEventListener('change', () => { firstLoad = false; fillTerms(''); });
  fillPrograms(initialProgram);
  firstLoad = false;

  const list = document.getElementById('dates-list');

  function ordinalInstallment(n) {
    const mod100 = n % 100;
    const suffix = (mod100 >= 11 && mod100 <= 13) ? 'th' : ({1:'st',2:'nd',3:'rd'}[n % 10] || 'th');
    return `${n}${suffix} Installment`;
  }

  function renumberDates() {
    list.querySelectorAll('.default-date-item').forEach((item, index) => {
      const label = item.querySelector('.installment-number');
      if (label) label.textContent = ordinalInstallment(index + 1);
    });
  }

  function addDate(value = '') {
    const item = document.createElement('div');
    item.className = 'default-date-item';
    item.innerHTML = `
      <div class="installment-date-label">
        <span class="installment-number">Installment</span>
        <span class="installment-date-caption">Due date</span>
      </div>
      <div class="date-input-row">
        <input type="date" name="dates[]" class="form-control" required>
        <button type="button" class="btn btn-outline btn-sm remove-date" title="Remove installment date" aria-label="Remove installment date"><i class="fas fa-xmark"></i></button>
      </div>`;
    item.querySelector('input').value = value;
    item.querySelector('button').addEventListener('click', () => {
      if (list.children.length > 1) {
        item.remove();
        renumberDates();
      }
    });
    list.appendChild(item);
    renumberDates();
  }
  document.getElementById('add-date').addEventListener('click', () => addDate());
  const initialDates = @json(old('dates', $installmentDateDefault->dates ?? []));
  (Array.isArray(initialDates) && initialDates.length ? initialDates : ['']).forEach(addDate);
})();
</script>
@endpush
@endsection
