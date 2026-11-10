@extends('layouts.app')

@section('title', isset($student) ? 'Edit Student' : 'Add Student')
@section('page_title', isset($student) ? 'Edit Student' : 'Add Student')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('students.index') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:20px;font-weight:800">{{ isset($student) ? 'Edit Student' : 'New Student Enrollment' }}</div>
</div>
@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form action="{{ isset($student) ? route('students.update', $student) : route('students.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($student)) @method('PUT') @endif

  <div class="grid grid-2" style="gap:20px">

    <!-- Personal Information -->
    <div class="card">
      <div class="card-header"><div class="card-title"><span class="section-icon"><i class="fas fa-user"></i></span>Personal Information</div></div>
      <div class="card-body">
        <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px">
          <label class="photo-upload" for="photo-input">
            <i class="fas fa-camera" id="photo-upload-icon"></i>
            <img id="photo-preview" style="display:none" alt="">
          </label>
          <div>
            <div style="font-weight:600;margin-bottom:4px">Profile Photo</div>
            <input type="file" id="photo-input" name="photo" accept="image/*" class="form-control" style="font-size:12px">
            <small style="color:var(--text-muted);display:block;margin-top:4px">Max size 100KB (JPG/PNG).</small>
            <small id="photo-size-error" style="color:var(--danger);display:none;margin-top:4px"></small>
            @error('photo') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Applicant Name *</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $student->name ?? '') }}">
            @error('name') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>
          <div class="form-group">
            <label class="form-label">Father Name *</label>
            <input type="text" name="father_name" class="form-control" required value="{{ old('father_name', $student->father_name ?? '') }}">
            @error('father_name') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Date of Birth *</label>
            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required
                   autocomplete="bday" max="{{ now()->toDateString() }}"
                   value="{{ old('date_of_birth', isset($student) ? $student->date_of_birth?->format('Y-m-d') : '') }}">
            <small style="color:var(--text-muted);display:block;margin-top:4px">Choose the date from the calendar.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Gender *</label>
            <select name="gender" class="form-control" required>
              <option value="male" {{ old('gender', $student->gender ?? '') == 'male' ? 'selected' : '' }}>Male</option>
              <option value="female" {{ old('gender', $student->gender ?? '') == 'female' ? 'selected' : '' }}>Female</option>
              <option value="other" {{ old('gender', $student->gender ?? '') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Marital Status *</label>
            <select name="marital_status" class="form-control" required>
              <option value="">Select</option>
              @foreach(['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'] as $val => $label)
              <option value="{{ $val }}" {{ old('marital_status', $student->marital_status ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">CNIC / B-Form Number <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
            <input type="text" name="cnic" class="form-control" value="{{ old('cnic', $student->cnic ?? '') }}" placeholder="XXXXX-XXXXXXX-X">
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Religion *</label>
            @php
              $religionOptions = ['Muslim', 'Christian', 'Hindu', 'Sikh'];
              $selectedReligion = old('religion', $student->religion ?? '');
            @endphp
            <select name="religion" class="form-control" required>
              <option value="">Select Religion</option>
              {{-- Preserve any legacy value already stored on an existing record. --}}
              @if($selectedReligion && !in_array($selectedReligion, $religionOptions, true))
                <option value="{{ $selectedReligion }}" selected>{{ $selectedReligion }}</option>
              @endif
              @foreach($religionOptions as $religion)
                <option value="{{ $religion }}" {{ $selectedReligion === $religion ? 'selected' : '' }}>{{ $religion }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number</label>
            <input type="tel" name="phone" class="form-control" value="{{ old('phone', $student->phone ?? '') }}" placeholder="e.g. 03XX-XXXXXXX">
          </div>
        </div>

        <div class="form-group" style="width:100%">
          <label class="form-label">Permanent Address *</label>
          <textarea name="permanent_address" class="form-control" rows="4" required style="width:100%;resize:vertical">{{ old('permanent_address', $student->permanent_address ?? '') }}</textarea>
          @error('permanent_address') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    <!-- Academic Information -->
    <div class="card">
      <div class="card-header"><div class="card-title"><span class="section-icon"><i class="fas fa-graduation-cap"></i></span>Academic Information</div></div>
      <div class="card-body">
        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Academic Level *</label>
            <select name="academic_level_id" id="academic_level_id" class="form-control" required>
              <option value="">Select Academic Level</option>
              @foreach($academicLevels as $al)
              <option value="{{ $al->id }}" data-requires-institution="{{ $al->requires_institution ? 1 : 0 }}"
                {{ old('academic_level_id', $student->academic_level_id ?? '') == $al->id ? 'selected' : '' }}>{{ $al->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group" id="institution_field" style="display:none">
            <label class="form-label">Institution / University *</label>
            <select name="institution_id" id="institution_id" class="form-control">
              <option value="">Select Institution</option>
              @foreach($institutions as $inst)
              <option value="{{ $inst->id }}" {{ old('institution_id', $student->institution_id ?? '') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
              @endforeach
            </select>
            <small style="color:var(--text-muted)">Programs will be filtered to this institution.</small>
          </div>
        </div>
        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Academic Session *</label>
            <select name="academic_year_id" id="academic_year_id" class="form-control" required>
              <option value="">Select Academic Session</option>
              @foreach($academicYears as $ay)
              <option value="{{ $ay->id }}" {{ old('academic_year_id', $student->academic_year_id ?? ($ay->is_default ? $ay->id : '')) == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
              @endforeach
            </select>
            <small style="color:var(--text-muted)">Programs and semesters below are limited to those linked to this session.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Shift *</label>
            <div style="display:flex;gap:20px;padding-top:8px">
              <label style="display:flex;align-items:center;gap:6px;font-weight:400">
                <input type="radio" name="shift" value="morning" required {{ old('shift', $student->shift ?? '') == 'morning' ? 'checked' : '' }}> Morning
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-weight:400">
                <input type="radio" name="shift" value="evening" {{ old('shift', $student->shift ?? '') == 'evening' ? 'checked' : '' }}> Evening
              </label>
            </div>
          </div>
        </div>
        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Program Application For *</label>
            <select name="program_id" id="program_id" class="form-control" required>
              <option value="">Select Academic Session first</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Part/Semester *</label>
            <select name="school_class_id" id="school_class_id" class="form-control" required>
              <option value="">Select Program first for Part/Semester</option>
            </select>
          </div>
        </div>
        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Student ID</label>
            <input type="text" class="form-control" readonly value="{{ isset($student) ? $student->student_id : 'Auto-generated after enrollment' }}">
            <small style="color:var(--text-muted);display:block;margin-top:4px">Student ID is generated automatically by the system and cannot be entered manually.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', isset($student) ? $student->admission_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
          </div>
        </div>
        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              @foreach(\App\Support\StudentStatus::VALUES as $st)
              <option value="{{ $st }}" {{ old('status', $student->status ?? 'active') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Attendance % <span style="font-weight:400;color:var(--text-muted)">(exam-fee hold)</span></label>
            <input type="number" step="0.01" min="0" max="100" name="attendance_percentage" class="form-control" value="{{ old('attendance_percentage', $student->attendance_percentage ?? '') }}">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Guardian/parent, emergency contact, financial aid and educational qualification fields intentionally removed from the admission form. -->

  <!-- Institution Disclaimer (shown only for University-level programs with an institution set) -->
  <div class="card" id="institution_disclaimer_card" style="display:none;margin-top:20px;border:1px solid var(--warning);background:rgba(245,158,11,0.06)">
    <div class="card-body" style="padding:16px 20px">
      <div style="font-weight:700;font-size:13px;color:var(--warning);margin-bottom:6px">
        <i class="fas fa-circle-info"></i> Disclaimer — <span id="institution_disclaimer_name"></span>
      </div>
      <div style="font-size:12.5px;color:var(--text-muted);white-space:pre-line" id="institution_disclaimer_text"></div>
    </div>
  </div>

  <!-- Submit -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px">
    <a href="{{ route('students.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> {{ isset($student) ? 'Update Student' : 'Enroll Student' }}
    </button>
  </div>
</form>

<script>
(function () {
  var input = document.getElementById('photo-input');
  var icon = document.getElementById('photo-upload-icon');
  var preview = document.getElementById('photo-preview');
  var sizeError = document.getElementById('photo-size-error');
  var MAX_BYTES = 100 * 1024; // 100KB — must match the server-side 'max:100' rule
  if (!input) return;
  input.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;

    if (file.size > MAX_BYTES) {
      sizeError.textContent = 'The photo is too large (' + Math.ceil(file.size / 1024) + 'KB) — please upload an image under 100KB.';
      sizeError.style.display = 'block';
      preview.style.display = 'none';
      icon.style.display = '';
      input.value = '';
      return;
    }
    sizeError.style.display = 'none';

    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
})();
</script>


@push('scripts')
<script>
(() => {
  const yearSelect    = document.getElementById('academic_year_id');
  const levelSelect   = document.getElementById('academic_level_id');
  const instField     = document.getElementById('institution_field');
  const instSelect    = document.getElementById('institution_id');
  const programSelect = document.getElementById('program_id');
  const sectionSelect = document.getElementById('school_class_id');

  const disclaimerCard = document.getElementById('institution_disclaimer_card');
  const disclaimerName = document.getElementById('institution_disclaimer_name');
  const disclaimerText = document.getElementById('institution_disclaimer_text');

  if (!yearSelect || !programSelect || !sectionSelect) return;

  const programsUrlTemplate = "{{ route('academic-years.programs', ['academicYear' => '__YEAR_ID__']) }}";
  const initialProgram = "{{ old('program_id', $student->program_id ?? '') }}";
  const initialSection = "{{ old('school_class_id', $student->school_class_id ?? '') }}";
  let initialSelectionPending = true;
  let sessionPrograms = [];

  function requiresInstitution() {
    if (!levelSelect) return false;
    const option = levelSelect.options[levelSelect.selectedIndex];
    return !!option && option.getAttribute('data-requires-institution') === '1';
  }

  function updateDisclaimer() {
    const option = programSelect.options[programSelect.selectedIndex];
    const institution = option?.getAttribute('data-institution') || '';
    const disclaimer = option?.getAttribute('data-disclaimer') || '';

    if (disclaimerCard && institution && disclaimer) {
      if (disclaimerName) disclaimerName.textContent = institution;
      if (disclaimerText) disclaimerText.textContent = disclaimer;
      disclaimerCard.style.display = '';
    } else if (disclaimerCard) {
      disclaimerCard.style.display = 'none';
    }
  }

  function populateSections(selectedId = '') {
    sectionSelect.innerHTML = '<option value="">Select Part/Semester</option>';
    const selectedProgram = sessionPrograms.find(p => String(p.id) === String(programSelect.value));

    if (!selectedProgram) {
      sectionSelect.innerHTML = '<option value="">Select Program first</option>';
      updateDisclaimer();
      return;
    }

    (selectedProgram.sections || []).forEach(section => {
      const option = document.createElement('option');
      option.value = section.id;
      option.textContent = section.label;
      option.selected = String(section.id) === String(selectedId);
      sectionSelect.appendChild(option);
    });

    if (!(selectedProgram.sections || []).length) {
      sectionSelect.innerHTML = '<option value="">No linked Part/Semester in this session</option>';
    }
    updateDisclaimer();
  }

  function populatePrograms(programId = '', sectionId = '') {
    programSelect.innerHTML = '<option value="">Select Program</option>';

    sessionPrograms.forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.setAttribute('data-institution-id', program.institution_id || '');
      option.setAttribute('data-institution', program.institution || '');
      option.setAttribute('data-disclaimer', program.disclaimer || '');
      option.selected = String(program.id) === String(programId);
      programSelect.appendChild(option);
    });

    if (!sessionPrograms.length) {
      programSelect.innerHTML = '<option value="">No Programs linked to this Academic Session</option>';
      sectionSelect.innerHTML = '<option value="">Link Programs/Semesters under Academic Sessions first</option>';
      updateDisclaimer();
      return;
    }

    populateSections(sectionId);
  }

  function syncInstitutionField(preserveInstitution = true) {
    if (!instField || !instSelect) return;
    const required = requiresInstitution();
    instField.style.display = required ? '' : 'none';
    instSelect.required = required;
    if (!required && !preserveInstitution) instSelect.value = '';
  }

  function loadSessionPrograms(preserveInitial = false) {
    syncInstitutionField(preserveInitial);
    const yearId = yearSelect.value;
    if (!yearId) {
      programSelect.innerHTML = '<option value="">Select Academic Session first</option>';
      sectionSelect.innerHTML = '<option value="">Select Academic Session first</option>';
      sessionPrograms = [];
      updateDisclaimer();
      return;
    }

    if (requiresInstitution() && instSelect && !instSelect.value) {
      programSelect.innerHTML = '<option value="">Select Institution first</option>';
      sectionSelect.innerHTML = '<option value="">Select Institution first</option>';
      sessionPrograms = [];
      updateDisclaimer();
      return;
    }

    programSelect.innerHTML = '<option value="">Loading linked Programs…</option>';
    sectionSelect.innerHTML = '<option value="">Loading…</option>';

    let url = programsUrlTemplate.replace('__YEAR_ID__', yearId);
    if (requiresInstitution() && instSelect?.value) {
      url += '?institution_id=' + encodeURIComponent(instSelect.value);
    }

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(response => {
        if (!response.ok) throw new Error('Could not load session Programs');
        return response.json();
      })
      .then(programs => {
        sessionPrograms = Array.isArray(programs) ? programs : [];
        const useInitial = preserveInitial && initialSelectionPending;
        populatePrograms(useInitial ? initialProgram : '', useInitial ? initialSection : '');
        if (useInitial) initialSelectionPending = false;
      })
      .catch(() => {
        sessionPrograms = [];
        programSelect.innerHTML = '<option value="">Could not load linked Programs</option>';
        sectionSelect.innerHTML = '<option value="">Could not load Parts/Semesters</option>';
        updateDisclaimer();
      });
  }

  programSelect.addEventListener('change', () => populateSections(''));
  yearSelect.addEventListener('change', () => {
    initialSelectionPending = false;
    loadSessionPrograms(false);
  });

  levelSelect?.addEventListener('change', () => {
    initialSelectionPending = false;
    syncInstitutionField(false);
    loadSessionPrograms(false);
  });

  instSelect?.addEventListener('change', () => {
    initialSelectionPending = false;
    loadSessionPrograms(false);
  });

  syncInstitutionField(true);
  loadSessionPrograms(true);
})();
</script>
@endpush

@endsection
