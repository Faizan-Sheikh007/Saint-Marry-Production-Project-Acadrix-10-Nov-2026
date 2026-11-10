@extends('layouts.app')
@section('title', isset($program) ? 'Edit Program' : 'Add Program')
@section('page_title', isset($program) ? 'Edit Program / Class' : 'Add Program / Class')

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

  .pf-hero{
    display:flex; align-items:flex-end; justify-content:space-between; gap:20px;
    margin-bottom:26px; flex-wrap:wrap;
  }
  .pf-eyebrow{
    font-size:11px; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:var(--acx-gold-dark); display:flex; align-items:center; gap:8px; margin-bottom:8px;
  }
  .pf-eyebrow::before{ content:""; width:22px; height:1.5px; background:var(--acx-gold); display:inline-block; }
  .pf-heading{
    font-family:'Fraunces', serif; font-weight:600; font-size:32px; line-height:1.15;
    color:var(--text); letter-spacing:-.01em;
  }
  .pf-sub{ color:var(--text-muted); font-size:13.5px; margin-top:6px; max-width:480px; }

  .pf-grid{
    display:grid; grid-template-columns:1.55fr 1fr; gap:24px; align-items:start;
  }
  @media (max-width: 880px){ .pf-grid{ grid-template-columns:1fr; } }

  .pf-card{
    background:var(--card); border:1px solid var(--border); border-radius:16px;
    box-shadow:var(--shadow); overflow:hidden;
  }
  .pf-card-head{
    padding:20px 26px; border-bottom:1px solid var(--border);
    display:flex; align-items:center; gap:12px;
  }
  .pf-card-head .pf-num{
    width:30px; height:30px; border-radius:9px; flex-shrink:0;
    background:linear-gradient(145deg, var(--primary), var(--primary-dark));
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:600; font-size:14px;
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

  .pf-toggle-row{
    display:flex; align-items:center; justify-content:space-between; gap:16px;
    padding:14px 16px; border:1.5px solid var(--border); border-radius:10px; margin-top:6px;
  }
  .pf-toggle-row .t-title{ font-weight:600; font-size:13.5px; }
  .pf-toggle-row .t-sub{ font-size:12px; color:var(--text-muted); margin-top:2px; }
  .switch{ position:relative; width:42px; height:24px; flex-shrink:0; }
  .switch input{ opacity:0; width:0; height:0; }
  .switch .slider{
    position:absolute; inset:0; background:var(--border); border-radius:999px; cursor:pointer; transition:.2s;
  }
  .switch .slider::before{
    content:""; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff;
    border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.25);
  }
  .switch input:checked + .slider{ background:var(--success); }
  .switch input:checked + .slider::before{ transform:translateX(18px); }

  .pf-actions{ display:flex; gap:12px; justify-content:flex-end; margin-top:26px; }

  /* ── Preview / seal panel ── */
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
    color:var(--acx-gold); opacity:.9; margin-bottom:18px; position:relative;
  }
  .pf-seal{
    width:74px; height:74px; border-radius:50%; margin:0 auto 16px;
    background:radial-gradient(circle at 35% 30%, #ffe8b0, var(--acx-gold) 55%, var(--acx-gold-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:600; font-size:24px; color:#4a2f05;
    border:3px solid rgba(255,255,255,.5); position:relative;
    box-shadow:0 8px 20px rgba(0,0,0,.25);
  }
  .pf-preview-name{
    font-family:'Fraunces', serif; font-weight:600; font-size:23px; text-align:center;
    line-height:1.25; position:relative; word-break:break-word; min-height:29px;
  }
  .pf-preview-code{
    text-align:center; font-size:12px; letter-spacing:.08em; color:rgba(255,255,255,.65);
    margin-top:4px; font-family:monospace; min-height:16px;
  }
  .pf-chips{ display:flex; gap:8px; justify-content:center; margin:16px 0 4px; flex-wrap:wrap; position:relative; }
  .pf-chip{
    padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.03em;
    background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
  }
  .pf-divider{ height:1px; background:rgba(255,255,255,.16); margin:18px 0; position:relative; }

  .pf-duration-label{
    font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,.6);
    margin-bottom:8px; position:relative;
  }
  .pf-dots{ display:flex; gap:5px; flex-wrap:wrap; position:relative; }
  .pf-dot{ width:9px; height:9px; border-radius:50%; background:rgba(255,255,255,.18); transition:.15s; }
  .pf-dot.filled{ background:var(--acx-gold); box-shadow:0 0 0 2px rgba(201,152,43,.25); }

  .pf-desc-preview{
    font-size:12.5px; color:rgba(255,255,255,.72); line-height:1.6; margin-top:16px;
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
    <div>
      <a href="{{ route('programs.index') }}" class="btn btn-outline btn-sm" style="margin-bottom:16px"><i class="fas fa-arrow-left"></i> Back</a>
      <div class="pf-eyebrow">{{ isset($program) ? 'Editing Program' : 'Step 1 — Academic Structure' }}</div>
      <div class="pf-heading">{{ isset($program) ? 'Edit Program / Class' : 'New Program / Class' }}</div>
      <div class="pf-sub">This is the top level of your academic structure — e.g. <em>BSCS</em> or <em>Grade 10</em>. Sections and semesters live underneath it.</div>
    </div>
  </div>

  <div class="pf-grid">
    <!-- FORM -->
    <div class="pf-card">
      <div class="pf-card-head">
        <div class="pf-num"><i class="fas fa-sitemap" style="font-size:13px"></i></div>
        <div>
          <strong>{{ isset($program) ? 'Program details' : 'Program details' }}</strong>
          <span>Fields marked * are required</span>
        </div>
      </div>
      <div class="pf-card-body">
        <form action="{{ isset($program) ? route('programs.update', $program) : route('programs.store') }}" method="POST" id="programForm">
          @csrf
          @if(isset($program)) @method('PUT') @endif

          <div class="pf-section-label">Identity</div>

          <div class="form-group">
            <label class="form-label">Program / Class Name *</label>
            <input type="text" name="name" id="pf_name" class="form-control" required value="{{ old('name', $program->name ?? '') }}" placeholder="e.g. BSCS or Grade 10">
            @error('name')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
          </div>

          <div class="grid grid-2" style="gap:12px">
            <div class="form-group">
              <label class="form-label">Code</label>
              <input type="text" name="code" id="pf_code" class="form-control" value="{{ old('code', $program->code ?? '') }}" placeholder="e.g. BSCS">
              @error('code')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="form-label">Level *</label>
              <select name="level" id="pf_level" class="form-control" required>
                <option value="college" {{ old('level', $program->level ?? 'college') == 'college' ? 'selected' : '' }}>College</option>
                <option value="university" {{ old('level', $program->level ?? '') == 'university' ? 'selected' : '' }}>University</option>
              </select>
            </div>
          </div>

          <div id="pf_university_fields" style="{{ old('level', $program->level ?? 'college') == 'university' ? '' : 'display:none' }}">
            <div class="form-group">
              <label class="form-label">Affiliated Institution / Facility *</label>
              <select name="institution_id" id="pf_institution" class="form-control">
                <option value="">— Select institution —</option>
                @foreach($institutions ?? [] as $institution)
                  <option value="{{ $institution->id }}"
                    data-disclaimer="{{ $institution->disclaimer }}"
                    {{ old('institution_id', $program->institution_id ?? '') == $institution->id ? 'selected' : '' }}>
                    {{ $institution->name }}
                  </option>
                @endforeach
              </select>
              <small style="color:var(--text-muted)">Manage the list under Admin &rsaquo; Institutions.</small>
            </div>
            <div class="form-group">
              <label class="form-label">Department Disclaimer <span style="font-weight:400;color:var(--text-muted)">(overrides the institution's default, optional)</span></label>
              <textarea name="department_disclaimer" id="pf_disclaimer" class="form-control" rows="4"
                        placeholder="Auto-filled from the institution's default disclaimer — edit freely for this specific department.">{{ old('department_disclaimer', $program->department_disclaimer ?? '') }}</textarea>
            </div>
          </div>

          <div class="pf-section-label">Structure</div>

          <div class="form-group">
            <label class="form-label">Academic System *</label>
            <select name="system_type" id="pf_system_type" class="form-control" required>
              <option value="semester" {{ old('system_type', $program->system_type ?? 'semester') == 'semester' ? 'selected' : '' }}>Semester system</option>
              <option value="annual" {{ old('system_type', $program->system_type ?? '') == 'annual' ? 'selected' : '' }}>Annual system (Part 1 / Part 2)</option>
            </select>
          </div>

          <div class="form-group" id="pf_duration_wrap">
            <label class="form-label">Number of Semesters</label>
            <select name="duration_terms" id="pf_duration" class="form-control">
              <option value="8" {{ old('duration_terms', $program->duration_terms ?? 8) == 8 ? 'selected' : '' }}>8 Semesters</option>
              <option value="10" {{ old('duration_terms', $program->duration_terms ?? 8) == 10 ? 'selected' : '' }}>10 Semesters</option>
            </select>
            <small style="color:var(--text-muted)" id="pf_duration_hint">Annual programs are automatically split into Part 1 and Part 2 — no need to set this.</small>
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" id="pf_desc" class="form-control" rows="3" placeholder="A short note about this program — audience, focus, or intake.">{{ old('description', $program->description ?? '') }}</textarea>
          </div>

          <div class="pf-toggle-row">
            <div>
              <div class="t-title">Active</div>
              <div class="t-sub">Inactive programs are hidden from the admission form.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="is_active" id="pf_active" value="1" {{ old('is_active', $program->is_active ?? true) ? 'checked' : '' }}>
              <span class="slider"></span>
            </label>
          </div>

          <div class="pf-actions">
            <a href="{{ route('programs.index') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Program</button>
          </div>
        </form>
      </div>
    </div>

    <!-- LIVE PREVIEW -->
    <div>
      <div class="pf-preview">
        <div class="pf-preview-tag"><i class="fas fa-graduation-cap"></i> Live preview</div>
        <div class="pf-seal" id="pv_seal">—</div>
        <div class="pf-preview-name" id="pv_name">Untitled Program</div>
        <div class="pf-preview-code" id="pv_code">&nbsp;</div>
        <div class="pf-chips">
          <span class="pf-chip" id="pv_level">College</span>
          <span class="pf-chip" id="pv_status">Active</span>
        </div>
        <div class="pf-divider"></div>
        <div class="pf-duration-label" id="pv_duration_label">Duration — not set</div>
        <div class="pf-dots" id="pv_dots"></div>
        <div class="pf-desc-preview empty" id="pv_desc">No description added yet.</div>
      </div>

      <div class="card pf-tips">
        <div class="card-body">
          <div class="pf-tip"><i class="fas fa-circle-info"></i><span>Its <strong>Parts/Semesters</strong> are generated automatically from the Academic System and count you choose above &mdash; no need to add them one by one.</span></div>
          <div class="pf-tip"><i class="fas fa-circle-info"></i><span>Students are enrolled by picking this Program first, then one of its Parts/Semesters.</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const $ = id => document.getElementById(id);
  const nameInput = $('pf_name'), codeInput = $('pf_code'), levelSelect = $('pf_level'),
        durationInput = $('pf_duration'), descInput = $('pf_desc'), activeInput = $('pf_active'),
        universityFields = $('pf_university_fields'), institutionSelect = $('pf_institution'),
        disclaimerInput = $('pf_disclaimer'), systemTypeSelect = $('pf_system_type'), durationWrap = $('pf_duration_wrap');

  function toggleLevelFields() {
    const isUniversity = levelSelect.value === 'university';
    universityFields.style.display = isUniversity ? '' : 'none';
    institutionSelect.required = isUniversity;
  }

  function toggleSystemType() {
    const isAnnual = systemTypeSelect.value === 'annual';
    durationWrap.style.display = isAnnual ? 'none' : '';
  }

  levelSelect.addEventListener('change', toggleLevelFields);
  systemTypeSelect.addEventListener('change', toggleSystemType);
  toggleLevelFields();
  toggleSystemType();

  // Auto-fill the department disclaimer from the selected institution's
  // default — only when the textarea is still empty, so we never clobber
  // an admin's custom department-specific wording.
  institutionSelect.addEventListener('change', function () {
    if (disclaimerInput.value.trim() !== '') return;
    const opt = institutionSelect.options[institutionSelect.selectedIndex];
    disclaimerInput.value = opt ? (opt.getAttribute('data-disclaimer') || '') : '';
  });

  function initials(str) {
    const words = str.trim().split(/\s+/).filter(Boolean);
    if (!words.length) return '—';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return (words[0][0] + words[1][0]).toUpperCase();
  }

  function renderDots(total) {
    const wrap = $('pv_dots');
    wrap.innerHTML = '';
    const n = Math.min(Math.max(parseInt(total) || 0, 0), 16);
    if (!n) { $('pv_duration_label').textContent = 'Duration — not set'; return; }
    $('pv_duration_label').textContent = n + ' term' + (n === 1 ? '' : 's') + ' total';
    for (let i = 0; i < n; i++) {
      const d = document.createElement('span');
      d.className = 'pf-dot filled';
      wrap.appendChild(d);
    }
  }

  function update() {
    const name = nameInput.value.trim();
    $('pv_name').textContent = name || 'Untitled Program';
    $('pv_seal').textContent = initials(name);
    $('pv_code').textContent = codeInput.value.trim() ? codeInput.value.trim().toUpperCase() : '';
    $('pv_level').textContent = levelSelect.options[levelSelect.selectedIndex].text;
    $('pv_status').textContent = activeInput.checked ? 'Active' : 'Inactive';

    const desc = descInput.value.trim();
    const descEl = $('pv_desc');
    descEl.textContent = desc || 'No description added yet.';
    descEl.classList.toggle('empty', !desc);

    renderDots(durationInput.value);
  }

  [nameInput, codeInput, levelSelect, durationInput, descInput, activeInput].forEach(el =>
    el.addEventListener('input', update)
  );
  levelSelect.addEventListener('change', update);
  activeInput.addEventListener('change', update);
  update();
})();
</script>
@endpush
