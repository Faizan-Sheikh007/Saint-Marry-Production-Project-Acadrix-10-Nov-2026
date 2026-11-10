@extends('layouts.app')
@section('title', isset($schoolClass) ? 'Edit Part/Semester' : 'Add Part/Semester')
@section('page_title', isset($schoolClass) ? 'Edit Part/Semester' : 'Add Part/Semester')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap');

  :root{
    --acx-gold:#c9982b;
    --acx-gold-dark:#9c7419;
    --acx-gold-soft:rgba(201,152,43,.12);
  }

  .pf-wrap{ max-width:1180px; }

  .pf-hero{ margin-bottom:26px; }
  .pf-eyebrow{
    font-size:11px; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:var(--acx-gold-dark); display:flex; align-items:center; gap:8px; margin-bottom:8px;
  }
  .pf-eyebrow::before{ content:""; width:22px; height:1.5px; background:var(--acx-gold); display:inline-block; }
  .pf-heading{
    font-family:'Fraunces', serif; font-weight:600; font-size:32px; line-height:1.15;
    color:var(--text); letter-spacing:-.01em;
  }
  .pf-sub{ color:var(--text-muted); font-size:13.5px; margin-top:6px; max-width:520px; }

  .pf-grid{ display:grid; grid-template-columns:1.55fr 1fr; gap:24px; align-items:start; }
  @media (max-width: 880px){ .pf-grid{ grid-template-columns:1fr; } }

  .pf-card{ background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
  .pf-card-head{ padding:20px 26px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
  .pf-card-head .pf-num{
    width:30px; height:30px; border-radius:9px; flex-shrink:0;
    background:linear-gradient(145deg, var(--primary), var(--primary-dark));
    color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px;
  }
  .pf-card-head strong{ font-size:15px; }
  .pf-card-head span{ display:block; font-size:12px; color:var(--text-muted); margin-top:1px; }
  .pf-card-body{ padding:26px; }

  .pf-section-label{
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:var(--acx-gold-dark); margin:22px 0 12px; display:flex; align-items:center; gap:8px;
  }
  .pf-section-label:first-child{ margin-top:0; }
  .pf-section-label::after{ content:""; flex:1; height:1px; background:var(--border); }

  .pf-actions{ display:flex; gap:12px; justify-content:flex-end; margin-top:26px; }

  /* ── Preview panel ── */
  .pf-preview{
    background:
      radial-gradient(circle at 85% -10%, rgba(201,152,43,.16), transparent 55%),
      linear-gradient(165deg, var(--primary-dark) 0%, var(--primary) 60%, #7a0b20 100%);
    color:#fff; border-radius:16px; padding:28px 24px; position:sticky; top:88px;
    box-shadow:var(--shadow-lg); overflow:hidden;
  }
  .pf-preview::before{
    content:""; position:absolute; inset:0;
    background-image:repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 2px, transparent 2px 26px);
    pointer-events:none;
  }
  .pf-preview-tag{
    font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
    color:var(--acx-gold); opacity:.9; margin-bottom:16px; position:relative;
  }
  .pf-breadcrumb{
    font-size:12px; color:rgba(255,255,255,.6); position:relative; margin-bottom:6px;
    display:flex; align-items:center; gap:6px; min-height:16px;
  }
  .pf-breadcrumb i{ font-size:9px; opacity:.7; }

  .pf-preview-name{
    font-family:'Fraunces', serif; font-weight:600; font-size:25px; line-height:1.2;
    position:relative; word-break:break-word; min-height:31px; display:flex; align-items:center; gap:10px;
  }
  .pf-label-avatar{
    width:38px; height:38px; border-radius:10px; flex-shrink:0;
    background:radial-gradient(circle at 35% 30%, #ffe8b0, var(--acx-gold) 55%, var(--acx-gold-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:600; font-size:15px; color:#4a2f05;
    border:2px solid rgba(255,255,255,.5);
  }

  .pf-chips{ display:flex; gap:8px; margin:16px 0 4px; flex-wrap:wrap; position:relative; }
  .pf-chip{
    padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.03em;
    background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
  }
  .pf-divider{ height:1px; background:rgba(255,255,255,.16); margin:18px 0; position:relative; }

  .pf-capacity-label{
    display:flex; justify-content:space-between; font-size:11px; text-transform:uppercase;
    letter-spacing:.08em; color:rgba(255,255,255,.6); margin-bottom:8px; position:relative;
  }
  .pf-gauge{ height:8px; border-radius:6px; background:rgba(255,255,255,.14); overflow:hidden; position:relative; }
  .pf-gauge-fill{ height:100%; background:linear-gradient(90deg, var(--acx-gold-dark), var(--acx-gold)); width:0%; transition:.2s; border-radius:6px; }

  .pf-desc-preview{
    font-size:12.5px; color:rgba(255,255,255,.72); line-height:1.6; margin-top:18px;
    position:relative; font-style:italic;
  }
  .pf-desc-preview.empty{ color:rgba(255,255,255,.35); }

  .pf-tips{ margin-top:18px; }
  .pf-tips .card-body{ padding:18px 20px; }
  .pf-tip{ display:flex; gap:10px; font-size:12.5px; color:var(--text-muted); line-height:1.5; }
  .pf-tip + .pf-tip{ margin-top:12px; }
  .pf-tip i{ color:var(--acx-gold-dark); margin-top:2px; }
</style>
@endpush

@section('content')
<div class="pf-wrap">
  <div class="pf-hero">
    <a href="{{ route('classes.index') }}" class="btn btn-outline btn-sm" style="margin-bottom:16px"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="pf-eyebrow">{{ isset($schoolClass) ? 'Editing Part/Semester' : 'Step 2 — Academic Structure' }}</div>
    <div class="pf-heading">{{ isset($schoolClass) ? 'Edit Part/Semester' : 'New Part/Semester' }}</div>
    <div class="pf-sub">Parts/Semesters live under a Program — this is where students actually get enrolled.</div>
  </div>

  @if($programs->isEmpty())
    <div class="pf-card"><div class="pf-card-body">
      <p style="color:var(--text-muted)">You need to create a Program / Class first.
        <a href="{{ route('programs.create') }}" style="color:var(--primary);font-weight:600">Add one now &rarr;</a>
      </p>
    </div></div>
  @else
  <div class="pf-grid">
    <!-- FORM -->
    <div class="pf-card">
      <div class="pf-card-head">
        <div class="pf-num"><i class="fas fa-layer-group"></i></div>
        <div>
          <strong>Part/Semester details</strong>
          <span>Fields marked * are required</span>
        </div>
      </div>
      <div class="pf-card-body">
        <form action="{{ isset($schoolClass) ? route('classes.update', $schoolClass) : route('classes.store') }}" method="POST" id="sectionForm">
          @csrf
          @if(isset($schoolClass)) @method('PUT') @endif

          <div class="pf-section-label">Belongs to</div>

          <div class="form-group">
            <label class="form-label">Program / Class *</label>
            <select name="program_id" id="sf_program" class="form-control" required>
              <option value="">Select Program / Class</option>
              @foreach($programs as $program)
              <option value="{{ $program->id }}"
                {{ old('program_id', $schoolClass->program_id ?? ($selectedProgramId ?? '')) == $program->id ? 'selected' : '' }}>
                {{ $program->name }}
              </option>
              @endforeach
            </select>
            @error('program_id')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
          </div>

          <div class="pf-section-label">Identity</div>

          <div class="form-group">
            <label class="form-label">Part/Semester Name *</label>
            <input type="text" name="name" id="sf_name" class="form-control" list="sf_name_options" required
                   value="{{ old('name', $schoolClass->name ?? '') }}" placeholder="e.g. Semester 3 or Part 1">
            <datalist id="sf_name_options"></datalist>
            <small style="color:var(--text-muted)" id="sf_name_hint">Select a Program above to see its valid parts/semesters (Part 1/Part 2 for annual programs, or Semester 1..N for semester programs).</small>
            @error('name')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
          </div>

          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Section Label</label>
              <input type="text" name="section" id="sf_label" class="form-control" value="{{ old('section', $schoolClass->section ?? '') }}" placeholder="e.g. A"></div>
            <div class="form-group"><label class="form-label">Capacity</label>
              <input type="number" name="capacity" id="sf_capacity" class="form-control" value="{{ old('capacity', $schoolClass->capacity ?? 40) }}" min="1"></div>
          </div>

          <div class="form-group"><label class="form-label">Level *</label>
            <select name="level" id="sf_level" class="form-control" required>
              <option value="college" {{ old('level', $schoolClass->level ?? 'college') == 'college' ? 'selected' : '' }}>College</option>
              <option value="university" {{ old('level', $schoolClass->level ?? '') == 'university' ? 'selected' : '' }}>University</option>
            </select></div>

          <div class="form-group"><label class="form-label">Description</label>
            <textarea name="description" id="sf_desc" class="form-control" rows="2" placeholder="Optional note about this section or semester.">{{ old('description', $schoolClass->description ?? '') }}</textarea></div>

          <div class="pf-actions">
            <a href="{{ route('classes.index') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Part/Semester</button>
          </div>
        </form>
      </div>
    </div>

    <!-- LIVE PREVIEW -->
    <div>
      <div class="pf-preview">
        <div class="pf-preview-tag"><i class="fas fa-layer-group"></i> Live preview</div>
        <div class="pf-breadcrumb" id="pv_program"><i class="fas fa-circle"></i> No program selected</div>
        <div class="pf-preview-name">
          <span class="pf-label-avatar" id="pv_avatar">?</span>
          <span id="pv_name">Untitled Part/Semester</span>
        </div>
        <div class="pf-chips">
          <span class="pf-chip" id="pv_level">College</span>
        </div>
        <div class="pf-divider"></div>
        <div class="pf-capacity-label"><span>Capacity</span><span id="pv_capacity_num">40 seats</span></div>
        <div class="pf-gauge"><div class="pf-gauge-fill" id="pv_gauge"></div></div>
        <div class="pf-desc-preview empty" id="pv_desc">No description added yet.</div>
      </div>

      <div class="card pf-tips">
        <div class="card-body">
          <div class="pf-tip"><i class="fas fa-circle-info"></i><span>Once saved, this Part/Semester becomes selectable when admitting a student under its Program.</span></div>
          <div class="pf-tip"><i class="fas fa-circle-info"></i><span>Examinations, attendance, timetable and fee templates all connect through this Part/Semester.</span></div>
        </div>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
  const $ = id => document.getElementById(id);
  const programSelect = $('sf_program'), nameInput = $('sf_name'), labelInput = $('sf_label'),
        capacityInput = $('sf_capacity'), levelSelect = $('sf_level'), descInput = $('sf_desc');

  function update() {
    const programText = programSelect.value ? programSelect.options[programSelect.selectedIndex].text : '';
    const programEl = $('pv_program');
    programEl.innerHTML = programText
      ? '<i class="fas fa-circle" style="color:var(--acx-gold, #c9982b)"></i> ' + programText
      : '<i class="fas fa-circle"></i> No program selected';

    const name = nameInput.value.trim();
    $('pv_name').textContent = name || 'Untitled Part/Semester';

    const label = labelInput.value.trim();
    $('pv_avatar').textContent = label ? label.slice(0, 2).toUpperCase() : (name ? name[0].toUpperCase() : '?');

    $('pv_level').textContent = levelSelect.options[levelSelect.selectedIndex].text;

    const cap = Math.max(parseInt(capacityInput.value) || 0, 0);
    $('pv_capacity_num').textContent = cap ? (cap + ' seats') : 'not set';
    const pct = cap ? Math.min((cap / 80) * 100, 100) : 0;
    $('pv_gauge').style.width = pct + '%';

    const desc = descInput.value.trim();
    const descEl = $('pv_desc');
    descEl.textContent = desc || 'No description added yet.';
    descEl.classList.toggle('empty', !desc);
  }

  [programSelect, nameInput, labelInput, capacityInput, levelSelect, descInput].forEach(el =>
    el.addEventListener('input', update)
  );
  programSelect.addEventListener('change', update);
  levelSelect.addEventListener('change', update);
  update();

  // Fetch the selected Program's valid part/semester labels (Part 1/Part 2 for
  // annual, Semester 1..N for semester system) to power the suggestion list.
  function loadProgramInfo() {
    const hint = $('sf_name_hint'), list = $('sf_name_options');
    if (!programSelect.value) { list.innerHTML = ''; return; }

    fetch('{{ url('/admin/programs') }}/' + programSelect.value + '/info')
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        if (!data) return;
        list.innerHTML = (data.term_labels || []).map(l => '<option value="' + l + '">').join('');
        hint.textContent = data.system_type === 'annual'
          ? 'Annual program — choose Part 1 or Part 2.'
          : 'Semester program — choose Semester 1 through ' + (data.term_labels || []).length + '.';
      })
      .catch(() => {});
  }
  programSelect.addEventListener('change', loadProgramInfo);
  loadProgramInfo();
})();
</script>
@endpush
