@extends('layouts.app')

@section('title', 'Issue Book')
@section('page_title', 'Issue Book')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.library.show', $library) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">Issue Book</div>
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

<div class="grid grid-2" style="gap:20px;align-items:start">

  <!-- Book Summary (read-only reminder of what's being issued) -->
  <div class="card" style="order:2">
    <div style="background:linear-gradient(135deg,var(--primary),#7c3aed);padding:22px 20px;color:#fff;position:relative">
      <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.85;margin-bottom:6px">{{ $library->category ?? 'Uncategorized' }}</div>
      <div style="font-size:17px;font-weight:800;line-height:1.3">{{ $library->name }}</div>
      <div style="font-size:12.5px;opacity:.9;margin-top:4px">{{ $library->author ?: 'Unknown Author' }}</div>
      <i class="fas fa-book-open" style="position:absolute;right:16px;top:16px;font-size:22px;opacity:.25"></i>
    </div>
    <div class="card-body">
      <div style="display:flex;gap:12px;margin-bottom:16px">
        <div style="flex:1;text-align:center;background:#f0fdf4;border-radius:10px;padding:12px">
          <div style="font-size:20px;font-weight:800;color:#16a34a">{{ $library->available_copies }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">Copies Available</div>
        </div>
        @if($library->isbn)
        <div style="flex:1;text-align:center;background:#eff6ff;border-radius:10px;padding:12px">
          <div style="font-size:12.5px;font-weight:700;color:#2563eb;padding-top:4px">{{ $library->isbn }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">ISBN</div>
        </div>
        @endif
      </div>

      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#1e40af">
        <i class="fas fa-circle-info"></i> Type the student's ID, roll number, or name in the box on the left — their details will fill in automatically.
      </div>
    </div>
  </div>

  <!-- Issue Form -->
  <div class="card" style="order:1">
    <div class="card-header"><div class="card-title"><i class="fas fa-hand-holding" style="color:var(--primary)"></i> Issue Details</div></div>
    <div class="card-body">
      <form action="{{ route('admin.library.issue', $library) }}" method="POST" id="issueForm">
        @csrf

        <!-- Student Search -->
        <div class="form-group" style="position:relative">
          <label class="form-label">Find Student (Student ID / Roll Number / Name) *</label>
          <input type="text" id="studentSearch" class="form-control" placeholder="Start typing to search..." autocomplete="off" required>
          <div id="studentResults" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);z-index:20;max-height:280px;overflow-y:auto;margin-top:4px"></div>
        </div>

        <input type="hidden" name="student_id" id="student_id">

        <!-- Auto-filled student card -->
        <div id="studentCard" style="display:none;background:#eef2ff;border:1.5px solid var(--primary);border-radius:12px;padding:14px 16px;margin-bottom:18px;gap:12px;align-items:center">
          <div id="studentAvatar" style="width:42px;height:42px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0"></div>
          <div style="min-width:0;flex:1">
            <div id="studentCardName" style="font-weight:700;font-size:14px"></div>
            <div id="studentCardMeta" style="font-size:11.5px;color:var(--text-muted)"></div>
          </div>
          <button type="button" id="clearStudent" class="btn btn-outline btn-sm" title="Clear"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Student Name *</label>
            <input type="text" name="student_name" id="student_name" class="form-control" required value="{{ old('student_name') }}">
          </div>
          <div class="form-group">
            <label class="form-label">Roll Number</label>
            <input type="text" name="student_roll_no" id="student_roll_no" class="form-control" value="{{ old('student_roll_no') }}">
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Class</label>
            <input type="text" name="class_name" id="class_name" class="form-control" value="{{ old('class_name') }}">
          </div>
          <div class="form-group">
            <label class="form-label">Program</label>
            <input type="text" name="program_name" id="program_name" class="form-control" value="{{ old('program_name') }}">
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Semester</label>
            <input type="text" name="semester" id="semester" class="form-control" value="{{ old('semester') }}">
          </div>
          <div class="form-group">
            <label class="form-label">Contact Number *</label>
            <input type="tel" name="contact_number" id="contact_number" class="form-control" required value="{{ old('contact_number') }}" placeholder="03XX-XXXXXXX">
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Issue For (Days) *</label>
            <input type="number" name="days" id="days" class="form-control" required min="1" max="180" value="{{ old('days', 14) }}">
          </div>
          <div class="form-group">
            <label class="form-label">Fine (Rs. per day late)</label>
            <input type="number" name="fine_per_day" id="fine_per_day" class="form-control" min="0" step="0.5" value="{{ old('fine_per_day', 10) }}">
          </div>
        </div>

        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 14px;margin-bottom:18px;display:flex;align-items:center;gap:10px">
          <i class="fas fa-calendar-check" style="color:#ea580c"></i>
          <div style="font-size:13px;color:#9a3412">
            Deadline to return: <strong id="dueDatePreview">{{ now()->addDays(14)->format('d M, Y') }}</strong>
          </div>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end">
          <a href="{{ route('admin.library.show', $library) }}" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="fas fa-hand-holding"></i> Issue Book</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const searchInput   = document.getElementById('studentSearch');
  const resultsBox    = document.getElementById('studentResults');
  const studentIdInp  = document.getElementById('student_id');
  const studentCard   = document.getElementById('studentCard');
  const studentAvatar = document.getElementById('studentAvatar');
  const cardName      = document.getElementById('studentCardName');
  const cardMeta      = document.getElementById('studentCardMeta');
  const clearBtn      = document.getElementById('clearStudent');

  const nameInp    = document.getElementById('student_name');
  const rollInp    = document.getElementById('student_roll_no');
  const classInp   = document.getElementById('class_name');
  const programInp = document.getElementById('program_name');
  const semInp     = document.getElementById('semester');
  const phoneInp   = document.getElementById('contact_number');

  const lookupUrl = "{{ route('admin.library.students.lookup') }}";
  let debounceTimer = null;

  searchInput.addEventListener('input', function () {
    const q = this.value.trim();
    clearTimeout(debounceTimer);
    if (q.length < 2) {
      resultsBox.style.display = 'none';
      resultsBox.innerHTML = '';
      return;
    }
    debounceTimer = setTimeout(() => {
      fetch(lookupUrl + '?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(students => {
          resultsBox.innerHTML = '';
          if (!students.length) {
            resultsBox.innerHTML = '<div style="padding:14px;font-size:12.5px;color:#94a3b8;text-align:center">No matching students found.</div>';
            resultsBox.style.display = 'block';
            return;
          }
          students.forEach(s => {
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9';
            row.onmouseenter = () => row.style.background = '#f8fafc';
            row.onmouseleave = () => row.style.background = '#fff';
            row.innerHTML = `
              <div style="width:34px;height:34px;border-radius:50%;background:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">${(s.name || '?').substring(0,2).toUpperCase()}</div>
              <div style="min-width:0">
                <div style="font-weight:600;font-size:13px">${s.name}</div>
                <div style="font-size:11px;color:#64748b">${s.student_id || ''}${s.roll_number ? ' · Roll ' + s.roll_number : ''}${s.class_name ? ' · ' + s.class_name : ''}</div>
              </div>`;
            row.addEventListener('click', () => selectStudent(s));
            resultsBox.appendChild(row);
          });
          resultsBox.style.display = 'block';
        })
        .catch(() => {
          resultsBox.innerHTML = '<div style="padding:14px;font-size:12.5px;color:#dc2626;text-align:center">Search failed. Try again.</div>';
          resultsBox.style.display = 'block';
        });
    }, 300);
  });

  document.addEventListener('click', function (e) {
    if (!resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.style.display = 'none';
    }
  });

  function selectStudent(s) {
    studentIdInp.value = s.id;
    nameInp.value    = s.name || '';
    rollInp.value    = s.roll_number || '';
    classInp.value   = s.class_name || '';
    programInp.value = s.program_name || '';
    semInp.value     = s.semester || '';
    phoneInp.value   = s.phone || '';

    searchInput.value = s.name;
    resultsBox.style.display = 'none';

    studentAvatar.textContent = (s.name || '?').substring(0, 2).toUpperCase();
    cardName.textContent = s.name;
    cardMeta.textContent = [s.student_id, s.roll_number ? 'Roll ' + s.roll_number : null, s.class_name].filter(Boolean).join(' · ');
    studentCard.style.display = 'flex';
  }

  clearBtn.addEventListener('click', function () {
    studentIdInp.value = '';
    searchInput.value = '';
    studentCard.style.display = 'none';
    [nameInp, rollInp, classInp, programInp, semInp, phoneInp].forEach(inp => inp.value = '');
  });

  // Live deadline preview
  const daysInput = document.getElementById('days');
  const dueDatePreview = document.getElementById('dueDatePreview');
  function updateDueDate() {
    const days = parseInt(daysInput.value, 10) || 0;
    const d = new Date();
    d.setDate(d.getDate() + days);
    dueDatePreview.textContent = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  daysInput.addEventListener('input', updateDueDate);
})();
</script>
@endpush

@endsection
