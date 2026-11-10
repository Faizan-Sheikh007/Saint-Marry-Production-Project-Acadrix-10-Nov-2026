@extends('layouts.app')
@section('title', isset($teacher) ? 'Edit Teacher' : 'Add Teacher')
@section('page_title', isset($teacher) ? 'Edit Teacher' : 'Add Teacher')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">{{ isset($teacher) ? 'Edit Teacher' : 'New Teacher' }}</div>
  @if(!isset($teacher))
    <span style="margin-left:auto;font-size:12px;color:var(--text-muted)"><i class="fas fa-circle-info"></i> A login ID & password will be generated automatically on save.</span>
  @endif
</div>

<form action="{{ isset($teacher) ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @if(isset($teacher)) @method('PUT') @endif

  <div class="grid grid-2" style="gap:20px;align-items:start">

    {{-- LEFT COLUMN --}}
    <div style="display:flex;flex-direction:column;gap:20px">

      {{-- Photo --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-camera"></i> Profile Photo</div></div>
        <div class="card-body" style="text-align:center">
          <img id="photo-preview"
               src="{{ isset($teacher) && $teacher->photo ? storage_asset($teacher->photo) : '' }}"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);
                      margin:0 auto 14px;display:{{ isset($teacher) && $teacher->photo ? 'block' : 'none' }}" alt="Preview">
          <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewPhoto(this)">
          @error('photo')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Signature --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-signature"></i> Signature</div></div>
        <div class="card-body">

          <div id="sig-existing" style="display:{{ isset($teacher) && $teacher->signature ? 'flex' : 'none' }};align-items:center;gap:14px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:14px;background:var(--bg)">
            <img id="sig-existing-img" src="{{ isset($teacher) && $teacher->signature ? storage_asset($teacher->signature) : '' }}"
                 style="height:44px;max-width:160px;object-fit:contain;background:#fff;border-radius:4px;padding:4px">
            <div style="flex:1;font-size:12px;color:var(--text-muted)">Signature on file — used on the ID card and printed documents.</div>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--danger);cursor:pointer;white-space:nowrap">
              <input type="checkbox" name="remove_signature" value="1" id="sig-remove-check"> Remove
            </label>
          </div>

          <div class="sig-tabs" style="display:flex;gap:8px;margin-bottom:10px">
            <button type="button" class="btn btn-outline btn-sm sig-tab-btn active" data-tab="draw" onclick="switchSigTab('draw')"><i class="fas fa-pen-nib"></i> Draw</button>
            <button type="button" class="btn btn-outline btn-sm sig-tab-btn" data-tab="upload" onclick="switchSigTab('upload')"><i class="fas fa-upload"></i> Upload Image</button>
          </div>

          <div id="sig-tab-draw">
            <div style="border:2px dashed var(--border);border-radius:8px;overflow:hidden;background:#fff">
              <canvas id="sig-pad" width="500" height="160" style="width:100%;height:160px;cursor:crosshair;touch-action:none;display:block"></canvas>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
              <span style="font-size:11px;color:var(--text-muted)">Sign above with mouse / touch / stylus.</span>
              <button type="button" class="btn btn-outline btn-sm" onclick="clearSigPad()"><i class="fas fa-eraser"></i> Clear</button>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
          </div>

          <div id="sig-tab-upload" style="display:none">
            <input type="file" name="signature" class="form-control" accept="image/png,image/jpeg" onchange="previewSigUpload(this)">
            <img id="sig-upload-preview" style="display:none;height:50px;margin-top:10px;background:#fff;border:1px solid var(--border);border-radius:4px;padding:4px">
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px">PNG with a transparent background works best.</div>
          </div>

          @error('signature')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- Personal Information --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Personal Information</div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $teacher->name ?? '') }}">
            @error('name')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror</div>

          <div class="form-group"><label class="form-label">Father / Guardian Name</label>
            <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $teacher->father_name ?? '') }}"></div>

          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Gender *</label>
              <select name="gender" class="form-control" required>
                <option value="male"   {{ old('gender', $teacher->gender ?? '') == 'male'   ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $teacher->gender ?? '') == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other"  {{ old('gender', $teacher->gender ?? '') == 'other'  ? 'selected' : '' }}>Other</option>
              </select></div>
            <div class="form-group"><label class="form-label">Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control"
                     value="{{ old('date_of_birth', isset($teacher) ? $teacher->date_of_birth?->format('Y-m-d') : '') }}"></div>
          </div>

          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Blood Group</label>
              <select name="blood_group" class="form-control">
                <option value="">—</option>
                @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                  <option value="{{ $bg }}" {{ old('blood_group', $teacher->blood_group ?? '') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                @endforeach
              </select></div>
            <div class="form-group"><label class="form-label">Marital Status</label>
              <select name="marital_status" class="form-control">
                <option value="">—</option>
                <option value="single"  {{ old('marital_status', $teacher->marital_status ?? '') == 'single'  ? 'selected' : '' }}>Single</option>
                <option value="married" {{ old('marital_status', $teacher->marital_status ?? '') == 'married' ? 'selected' : '' }}>Married</option>
                <option value="other"   {{ old('marital_status', $teacher->marital_status ?? '') == 'other'   ? 'selected' : '' }}>Other</option>
              </select></div>
          </div>

          <div class="form-group"><label class="form-label">CNIC</label>
            <input type="text" name="cnic" class="form-control" value="{{ old('cnic', $teacher->cnic ?? '') }}" placeholder="XXXXX-XXXXXXX-X"></div>
        </div>
      </div>

      {{-- Contact --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-address-book"></i> Contact & Emergency</div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required value="{{ old('email', $teacher->email ?? '') }}">
            @error('email')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
            @if(!isset($teacher))<div style="font-size:11px;color:var(--text-muted);margin-top:4px">This becomes the teacher's login email.</div>@endif
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Emergency Contact</label>
              <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $teacher->emergency_contact ?? '') }}"></div>
          </div>
          <div class="form-group"><label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $teacher->address ?? '') }}</textarea></div>
          <div class="form-group"><label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="{{ old('city', $teacher->city ?? '') }}"></div>
        </div>
      </div>
    </div>

    {{-- RIGHT COLUMN --}}
    <div style="display:flex;flex-direction:column;gap:20px">

      {{-- Employment --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-briefcase"></i> Employment Details</div></div>
        <div class="card-body">
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Designation</label>
              <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Lecturer"
                     value="{{ old('designation', $teacher->designation ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Department</label>
              <input type="text" name="department" class="form-control" placeholder="e.g. Computer Science"
                     value="{{ old('department', $teacher->department ?? '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Employment Type</label>
              <select name="employment_type" class="form-control">
                @foreach(['permanent' => 'Permanent', 'contract' => 'Contract', 'visiting' => 'Visiting', 'probation' => 'Probation'] as $val => $label)
                  <option value="{{ $val }}" {{ old('employment_type', $teacher->employment_type ?? 'permanent') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select></div>
            <div class="form-group"><label class="form-label">Joining Date</label>
              <input type="date" name="joining_date" class="form-control"
                     value="{{ old('joining_date', isset($teacher) ? $teacher->joining_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"></div>
          </div>
          <div class="form-group"><label class="form-label">Qualification</label>
            <input type="text" name="qualification" class="form-control" value="{{ old('qualification', $teacher->qualification ?? '') }}"></div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Experience (years)</label>
              <input type="number" min="0" name="experience" class="form-control" value="{{ old('experience', $teacher->experience ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value="active"   {{ old('status', $teacher->status ?? 'active') == 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $teacher->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
              </select></div>
          </div>
          <div class="form-group"><label class="form-label">Primary Subject</label>
            <select name="subject_id" class="form-control">
              <option value="">None</option>
              @foreach($subjects as $subject)
              <option value="{{ $subject->id }}" {{ old('subject_id', $teacher->subject_id ?? '') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select></div>
        </div>
      </div>

      {{-- Salary & Bank --}}
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-money-check-dollar"></i> Salary & Bank Details</div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Salary (Rs.)</label>
            <input type="number" step="0.01" name="salary" class="form-control" value="{{ old('salary', $teacher->salary ?? '') }}"></div>
          <div class="form-group"><label class="form-label">Bank Name</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $teacher->bank_name ?? '') }}"></div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Account Title</label>
              <input type="text" name="bank_account_title" class="form-control" value="{{ old('bank_account_title', $teacher->bank_account_title ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Account Number / IBAN</label>
              <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $teacher->bank_account_number ?? '') }}"></div>
          </div>
        </div>
        <div style="padding:16px 20px;display:flex;gap:12px;justify-content:flex-end;border-top:1px solid var(--border)">
          <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ isset($teacher) ? 'Update' : 'Save & Generate Login' }}</button>
        </div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewSigUpload(input) {
    const preview = document.getElementById('sig-upload-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'inline-block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function switchSigTab(tab) {
    document.getElementById('sig-tab-draw').style.display   = tab === 'draw'   ? 'block' : 'none';
    document.getElementById('sig-tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.querySelectorAll('.sig-tab-btn').forEach(b => {
        const active = b.dataset.tab === tab;
        b.classList.toggle('active', active);
        b.style.background = active ? 'var(--primary)' : '';
        b.style.color      = active ? '#fff' : '';
        b.style.borderColor = active ? 'var(--primary)' : '';
    });
}

// ── Signature pad (draw with mouse / touch / stylus) ──
(function () {
    const canvas = document.getElementById('sig-pad');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let drawing = false, hasDrawn = false, ratio = 1;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        ratio = window.devicePixelRatio || 1;
        canvas.width  = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1a0608';
    }
    resize();
    window.addEventListener('resize', resize);

    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }
    function start(e) { drawing = true; hasDrawn = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e)  { if (!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end()     { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    window.clearSigPad = function () {
        ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        hasDrawn = false;
        document.getElementById('signature_data').value = '';
    };

    // Capture the drawing into the hidden input right before submit
    const form = canvas.closest('form');
    form.addEventListener('submit', function () {
        if (hasDrawn) {
            document.getElementById('signature_data').value = canvas.toDataURL('image/png');
        }
    });
})();
</script>
@endpush
@endsection
