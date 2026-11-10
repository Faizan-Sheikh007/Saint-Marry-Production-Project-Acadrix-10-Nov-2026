@extends('layouts.app')

@section('title', isset($parent) ? 'Edit Parent' : 'Add Parent')
@section('page_title', isset($parent) ? 'Edit Parent' : 'Add Parent')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.parents.index') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:20px;font-weight:800">{{ isset($parent) ? 'Edit Parent' : 'New Parent / Guardian' }}</div>
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

<form action="{{ isset($parent) ? route('admin.parents.update', $parent) : route('admin.parents.store') }}" method="POST">
  @csrf
  @if(isset($parent)) @method('PUT') @endif

  <div class="grid grid-2" style="gap:20px;align-items:start">

    <!-- Left column: Parent details -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header"><div class="card-title"><i class="fas fa-id-badge" style="color:var(--primary)"></i> Parent Information</div></div>
        <div class="card-body">
          <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;flex-shrink:0" id="avatarPreview">
              {{ strtoupper(substr(old('name', $parent->name ?? ''), 0, 2)) ?: '?' }}
            </div>
            <div style="font-size:12.5px;color:var(--text-muted)">
              The avatar initials update automatically from the name below.
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" id="parentName" class="form-control" required value="{{ old('name', $parent->name ?? '') }}" placeholder="e.g. Muhammad Ahsan">
            @error('name') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>

          <div class="grid grid-2" style="gap:16px">
            <div class="form-group">
              <label class="form-label">Phone *</label>
              <input type="tel" name="phone" class="form-control" required value="{{ old('phone', $parent->phone ?? '') }}" placeholder="03XX-XXXXXXX">
              @error('phone') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label class="form-label">CNIC</label>
              <input type="text" name="cnic" class="form-control" value="{{ old('cnic', $parent->cnic ?? '') }}" placeholder="XXXXX-XXXXXXX-X">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Occupation</label>
            <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $parent->occupation ?? '') }}">
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $parent->address ?? '') }}</textarea>
          </div>
        </div>
      </div>

      <!-- Portal Credentials -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-key" style="color:var(--primary)"></i> Parent Portal Login</div></div>
        <div class="card-body">
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">Email (optional contact address)</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $parent->email ?? '') }}" placeholder="parent@example.com">
            @error('email') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>

          @if(isset($parent) && $parent->user_id)
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:#166534;margin-bottom:12px">
              <i class="fas fa-circle-check"></i> Portal login active — <strong>{{ $parent->user->email }}</strong>. This is a separate, auto-generated login ID and is not affected by the contact email above.
            </div>
            <div style="font-size:12px;color:var(--text-muted);background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px">
              <i class="fas fa-circle-info"></i> Admin cannot reset a parent's portal password.
            </div>
          @else
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;font-size:12.5px;color:#1e40af">
              <i class="fas fa-circle-info"></i> A portal login is generated automatically for every parent — with or without an email above — using a random, institution-branded ID and password. The email field here is optional and only used as a contact address.
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right column: Link children -->
    <div class="card">
      <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div class="card-title"><i class="fas fa-children" style="color:var(--primary)"></i> Link Children</div>
        <span id="selectedCount" style="font-size:12px;font-weight:700;color:var(--primary);background:#eef2ff;padding:4px 10px;border-radius:20px">0 selected</span>
      </div>
      <div class="card-body">
        <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 14px">
          Select every child that belongs to this parent. One parent can be linked to multiple students.
        </p>

        <div class="form-group" style="margin-bottom:14px">
          <input type="text" id="studentSearch" class="form-control" placeholder="Search students by name, ID or class...">
        </div>

        <div id="studentList" style="display:flex;flex-direction:column;gap:8px;max-height:480px;overflow-y:auto;padding-right:4px">
          @forelse($students as $student)
            @php $checked = in_array($student->id, $linkedStudentIds); @endphp
            <label class="student-pick-row {{ $checked ? 'is-checked' : '' }}"
                   data-search="{{ strtolower($student->name.' '.$student->student_id.' '.($student->schoolClass->name ?? '')) }}"
                   style="display:flex;align-items:center;gap:12px;border:1.5px solid {{ $checked ? 'var(--primary)' : '#e2e8f0' }};background:{{ $checked ? '#eef2ff' : '#fff' }};border-radius:12px;padding:10px 14px;cursor:pointer;transition:all .15s">
              <input type="checkbox" class="student-checkbox" name="student_ids[]" value="{{ $student->id }}" {{ $checked ? 'checked' : '' }}
                     style="width:18px;height:18px;accent-color:var(--primary);flex-shrink:0">

              @if($student->photo)
                <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0">
              @else
                <div style="width:38px;height:38px;border-radius:50%;background:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0">
                  {{ strtoupper(substr($student->name, 0, 2)) }}
                </div>
              @endif

              <div style="min-width:0;flex:1">
                <div style="font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $student->name }}</div>
                <div style="font-size:11.5px;color:var(--text-muted)">
                  {{ $student->student_id }} @if($student->schoolClass) &middot; {{ $student->schoolClass->name }} @endif
                  @if($student->parent_id && (!isset($parent) || $student->parent_id != $parent->id))
                    &middot; <span style="color:#d97706">already linked to another parent</span>
                  @endif
                </div>
              </div>
            </label>
          @empty
            <div style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px">
              <i class="fas fa-user-graduate" style="font-size:26px;opacity:.3;display:block;margin-bottom:10px"></i>
              No active students available to link.
            </div>
          @endforelse
        </div>

        <div id="noResults" style="display:none;text-align:center;padding:24px;color:var(--text-muted);font-size:13px">
          No students match your search.
        </div>
      </div>
    </div>
  </div>

  <!-- Submit -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px">
    <a href="{{ route('admin.parents.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> {{ isset($parent) ? 'Update Parent' : 'Save Parent' }}
    </button>
  </div>
</form>

@push('scripts')
<script>
(function () {
  const nameInput   = document.getElementById('parentName');
  const avatar      = document.getElementById('avatarPreview');
  const searchInput = document.getElementById('studentSearch');
  const rows         = Array.from(document.querySelectorAll('.student-pick-row'));
  const checkboxes    = Array.from(document.querySelectorAll('.student-checkbox'));
  const countBadge   = document.getElementById('selectedCount');
  const noResults    = document.getElementById('noResults');

  function updateAvatar() {
    const v = nameInput.value.trim();
    avatar.textContent = v ? v.substring(0, 2).toUpperCase() : '?';
  }
  nameInput.addEventListener('input', updateAvatar);

  function updateCount() {
    const n = checkboxes.filter(cb => cb.checked).length;
    countBadge.textContent = n + ' selected';
  }

  rows.forEach(row => {
    const cb = row.querySelector('.student-checkbox');
    cb.addEventListener('change', function () {
      row.classList.toggle('is-checked', cb.checked);
      row.style.borderColor = cb.checked ? 'var(--primary)' : '#e2e8f0';
      row.style.background  = cb.checked ? '#eef2ff' : '#fff';
      updateCount();
    });
  });

  searchInput.addEventListener('input', function () {
    const term = this.value.trim().toLowerCase();
    let visible = 0;
    rows.forEach(row => {
      const match = row.dataset.search.includes(term);
      row.style.display = match ? 'flex' : 'none';
      if (match) visible++;
    });
    noResults.style.display = (visible === 0 && rows.length > 0) ? 'block' : 'none';
  });

  updateCount();
})();
</script>
@endpush

@endsection
