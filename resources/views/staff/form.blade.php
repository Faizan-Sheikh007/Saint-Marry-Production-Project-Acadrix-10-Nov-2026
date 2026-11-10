@extends('layouts.app')
@section('title', isset($staff) ? 'Edit Staff Member' : 'Add Staff Member')
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.staff.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">{{ isset($staff) ? 'Edit Staff Member' : 'New Staff Member' }}</div>
  @if(!isset($staff))
    <span style="margin-left:auto;font-size:12px;color:var(--text-muted)"><i class="fas fa-shield-halved"></i> CNIC & bank account are encrypted at rest (RSA+AES).</span>
  @endif
</div>

<form action="{{ isset($staff) ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" method="POST" enctype="multipart/form-data">
  @csrf
  @if(isset($staff)) @method('PUT') @endif

  <div class="grid grid-2" style="gap:20px;align-items:start">

    {{-- LEFT --}}
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-camera"></i> Photo</div></div>
        <div class="card-body" style="text-align:center">
          <img id="photo-preview"
               src="{{ isset($staff) && $staff->photo ? asset('storage/'.$staff->photo) : '' }}"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);
                      margin:0 auto 14px;display:{{ isset($staff) && $staff->photo ? 'block' : 'none' }}" alt="Preview">
          <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewPhoto(this)">
          @error('photo')<div style="color:var(--danger);font-size:12px;margin-top:6px">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-id-card"></i> Identity</div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $staff->name ?? '') }}">
            @error('name')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Father's Name</label>
              <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $staff->father_name ?? '') }}"></div>
            <div class="form-group"><label class="form-label">CNIC <i class="fas fa-lock" style="color:var(--text-muted);font-size:10px" title="Encrypted at rest"></i></label>
              <input type="text" name="cnic" class="form-control" placeholder="XXXXX-XXXXXXX-X" value="{{ old('cnic', $staff->cnic ?? '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Gender</label>
              <select name="gender" class="form-control">
                <option value="">—</option>
                @foreach(['male','female','other'] as $g)
                <option value="{{ $g }}" {{ old('gender', $staff->gender ?? '') == $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
                @endforeach
              </select></div>
            <div class="form-group"><label class="form-label">Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', isset($staff) && $staff->date_of_birth ? $staff->date_of_birth->format('Y-m-d') : '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Blood Group</label>
              <input type="text" name="blood_group" class="form-control" placeholder="e.g. O+" value="{{ old('blood_group', $staff->blood_group ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Marital Status</label>
              <input type="text" name="marital_status" class="form-control" value="{{ old('marital_status', $staff->marital_status ?? '') }}"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-address-book"></i> Contact</div></div>
        <div class="card-body">
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" required value="{{ old('email', $staff->email ?? '') }}">
              @error('email')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
            </div>
            <div class="form-group"><label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone ?? '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Emergency Contact</label>
              <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $staff->emergency_contact ?? '') }}"></div>
            <div class="form-group"><label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="{{ old('city', $staff->city ?? '') }}"></div>
          </div>
          <div class="form-group"><label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $staff->address ?? '') }}</textarea></div>
        </div>
      </div>
    </div>

    {{-- RIGHT --}}
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-briefcase"></i> Employment</div></div>
        <div class="card-body">
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Designation *</label>
              <input type="text" name="designation" class="form-control" required placeholder="e.g. Accountant, Security Guard, Driver" value="{{ old('designation', $staff->designation ?? '') }}">
              @error('designation')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
            </div>
            <div class="form-group"><label class="form-label">Department</label>
              <input type="text" name="department" class="form-control" value="{{ old('department', $staff->department ?? '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Employment Type *</label>
              <select name="employment_type" class="form-control" required>
                @foreach(['full_time'=>'Full-time','part_time'=>'Part-time','contract'=>'Contract','daily_wage'=>'Daily Wage'] as $val => $label)
                <option value="{{ $val }}" {{ old('employment_type', $staff->employment_type ?? 'full_time') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select></div>
            <div class="form-group"><label class="form-label">Joining Date</label>
              <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', isset($staff) && $staff->joining_date ? $staff->joining_date->format('Y-m-d') : '') }}"></div>
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Qualification</label>
              <input type="text" name="qualification" class="form-control" value="{{ old('qualification', $staff->qualification ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Experience (years)</label>
              <input type="number" min="0" max="60" name="experience" class="form-control" value="{{ old('experience', $staff->experience ?? '') }}"></div>
          </div>
          <div class="form-group"><label class="form-label">Status *</label>
            <select name="status" class="form-control" required>
              @foreach(['active','inactive','terminated'] as $st)
              <option value="{{ $st }}" {{ old('status', $staff->status ?? 'active') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
              @endforeach
            </select></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-money-bill-wave"></i> Salary & Bank Details</div></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Base Monthly Salary (Rs.) *</label>
            <input type="number" step="0.01" min="0" name="salary" class="form-control" required value="{{ old('salary', $staff->salary ?? '') }}">
            @error('salary')<div style="color:var(--danger);font-size:12px">{{ $message }}</div>@enderror
          </div>
          <div class="grid grid-2" style="gap:12px">
            <div class="form-group"><label class="form-label">Bank Name</label>
              <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $staff->bank_name ?? '') }}"></div>
            <div class="form-group"><label class="form-label">Account Title</label>
              <input type="text" name="bank_account_title" class="form-control" value="{{ old('bank_account_title', $staff->bank_account_title ?? '') }}"></div>
          </div>
          <div class="form-group"><label class="form-label">Account Number <i class="fas fa-lock" style="color:var(--text-muted);font-size:10px" title="Encrypted at rest"></i></label>
            <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $staff->bank_account_number ?? '') }}"></div>
          <p style="font-size:11px;color:var(--text-muted);margin-top:6px"><i class="fas fa-shield-halved"></i> CNIC and account number are protected with hybrid RSA+AES encryption before they ever touch the database.</p>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
        <i class="fas fa-check"></i> {{ isset($staff) ? 'Update Staff Member' : 'Add Staff Member' }}
      </button>
    </div>
  </div>
</form>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('photo-preview');
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
@endsection
