@extends('layouts.app')
@section('title', $teacher->name)
@section('page_title', 'Teacher Profile')
@section('content')

<style>
  .th-hero{
    position:relative; border-radius:16px; overflow:hidden; margin-bottom:22px;
    background:linear-gradient(120deg,#c4213f 0%,var(--primary) 48%,#4a0513 100%);
    box-shadow:0 14px 30px -10px rgba(137,12,37,.35);
  }
  .th-hero .pattern{ position:absolute; inset:0; opacity:.12;
    background-image:radial-gradient(circle,#fff 1.4px,transparent 1.4px); background-size:18px 18px; }
  .th-hero-inner{ position:relative; z-index:1; padding:26px 28px; display:flex; align-items:center; gap:22px; flex-wrap:wrap; color:#fff; }
  .th-hero-actions{ display:flex; gap:10px; margin-left:auto; flex-wrap:wrap; }
  .th-hero-actions a{
    display:inline-flex; align-items:center; gap:7px; font-size:13px; font-weight:700; padding:9px 16px;
    border-radius:9px; text-decoration:none; transition:all .18s;
  }
  .th-hero-actions .a-solid{ background:#fff; color:var(--primary); }
  .th-hero-actions .a-solid:hover{ transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.18); }
  .th-hero-actions .a-ghost{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.4); }
  .th-hero-actions .a-ghost:hover{ background:rgba(255,255,255,.28); }
  .th-avatar-wrap{ position:relative; flex-shrink:0; }
  .th-avatar{
    width:92px; height:92px; border-radius:50%; object-fit:cover; border:4px solid rgba(255,255,255,.85);
    box-shadow:0 8px 18px rgba(0,0,0,.2); display:block;
  }
  .th-avatar-initials{
    width:92px; height:92px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:30px; font-weight:800; color:#fff; background:rgba(255,255,255,.22);
    border:4px solid rgba(255,255,255,.85); box-shadow:0 8px 18px rgba(0,0,0,.2);
  }
  .th-status-dot{
    position:absolute; right:2px; bottom:4px; width:18px; height:18px; border-radius:50%;
    border:3px solid #fff; background:{{ $teacher->status === 'active' ? '#10b981' : '#ef4444' }};
  }
  .th-name{ font-size:24px; font-weight:800; letter-spacing:-.2px; }
  .th-sub{ font-size:13.5px; opacity:.9; margin-top:3px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .th-pill{ background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.35); padding:3px 11px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
  .th-stats{ display:flex; gap:0; border-top:1px solid rgba(255,255,255,.18); position:relative; z-index:1; }
  .th-stat{ flex:1; text-align:center; padding:14px 8px; border-right:1px solid rgba(255,255,255,.18); color:#fff; }
  .th-stat:last-child{ border-right:none; }
  .th-stat b{ display:block; font-size:20px; font-weight:800; }
  .th-stat span{ font-size:10.5px; opacity:.85; text-transform:uppercase; letter-spacing:.4px; font-weight:600; }

  /* ── Rebalanced body grid: narrower left rail, wider details column ── */
  .th-grid{ display:grid; grid-template-columns:340px 1fr; gap:20px; align-items:start; }
  @media (max-width:900px){ .th-grid{ grid-template-columns:1fr; } }

  .th-qa-btn{
    display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px;
    text-decoration:none; color:var(--text); transition:background .15s;
  }
  .th-qa-btn:hover{ background:var(--bg,#f7f3f4); }
  .th-qa-icon{ width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
  .th-qa-label{ font-size:13px; font-weight:600; }

  .th-chip{
    display:inline-flex; align-items:center; padding:6px 13px; border-radius:20px; margin:0 8px 8px 0;
    background:rgba(139,12,34,.07); color:var(--primary); font-size:12px; font-weight:700;
  }

  /* Two sub-columns of detail rows on wide screens so the tall list doesn't
     stretch the page unnecessarily far past the left rail. */
  .th-details{ column-count:2; column-gap:24px; }
  @media (max-width:1100px){ .th-details{ column-count:1; } }
  .th-detail-row{
    display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid var(--border);
    break-inside:avoid;
  }
  .th-detail-icon{
    width:32px; height:32px; border-radius:8px; background:#f3f0ff; color:var(--primary);
    display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:13px; margin-top:1px;
  }
</style>

<div class="th-hero">
  <div class="pattern"></div>
  <div class="th-hero-inner">
    <div class="th-avatar-wrap">
      @if($teacher->photo)
        <img src="{{ storage_asset($teacher->photo) }}" alt="{{ $teacher->name }}" class="th-avatar">
      @else
        <div class="th-avatar-initials">{{ strtoupper(substr($teacher->name, 0, 2)) }}</div>
      @endif
      <div class="th-status-dot" title="{{ ucfirst($teacher->status) }}"></div>
    </div>
    <div>
      <div class="th-name">{{ $teacher->name }}</div>
      <div class="th-sub">
        <span>{{ $teacher->designation ?: ($teacher->subject->name ?? 'Faculty Member') }}</span>
        <span class="th-pill">{{ $teacher->employee_id }}</span>
      </div>
    </div>
    <div class="th-hero-actions">
      <a href="{{ route('admin.teachers.index') }}" class="a-ghost"><i class="fas fa-arrow-left"></i> Back</a>
      <a href="{{ route('admin.teachers.edit', $teacher) }}" class="a-ghost"><i class="fas fa-pen"></i> Edit</a>
      <a href="{{ route('admin.teachers.idcard', $teacher) }}" target="_blank" class="a-solid"><i class="fas fa-id-card"></i> ID Card</a>
      <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST"
            data-confirm="Delete {{ $teacher->name }}? This removes their profile and login account permanently — this can't be undone."
            data-confirm-title="Delete Teacher?" data-confirm-variant="danger" data-confirm-icon="fas fa-trash"
            data-confirm-ok-text="Delete">
        @csrf @method('DELETE')
        <button type="submit" class="a-ghost" style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;padding:9px 16px;border-radius:9px;cursor:pointer;font-family:inherit;transition:all .18s">
          <i class="fas fa-trash"></i> Delete
        </button>
      </form>
    </div>
  </div>
  <div class="th-stats">
    <div class="th-stat"><b>{{ $teacher->experience ?? 0 }}</b><span>Yrs Experience</span></div>
    <div class="th-stat"><b>{{ $teacher->courses->count() }}</b><span>Courses</span></div>
    <div class="th-stat"><b>{{ $teacher->classes->count() }}</b><span>Classes</span></div>
  </div>
</div>

<div class="th-grid">

  {{-- Left column --}}
  <div style="display:flex;flex-direction:column;gap:20px">

  {{-- Quick Actions --}}
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div></div>
    <div class="card-body" style="padding:14px;display:flex;flex-direction:column;gap:8px">
      <a href="{{ route('admin.messages.show', $teacher) }}" class="th-qa-btn">
        <span class="th-qa-icon" style="background:rgba(37,99,235,.1);color:#2563eb"><i class="fas fa-comments"></i></span>
        <span class="th-qa-label">Message Teacher</span>
        @if($unreadFromTeacher > 0)<span class="badge danger" style="margin-left:auto">{{ $unreadFromTeacher }} new</span>@endif
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:11px;{{ $unreadFromTeacher > 0 ? '' : 'margin-left:auto' }}"></i>
      </a>
      <a href="{{ route('admin.activities.index') }}" class="th-qa-btn">
        <span class="th-qa-icon" style="background:rgba(13,158,110,.1);color:#0d9e6e"><i class="fas fa-diagram-project"></i></span>
        <span class="th-qa-label">Assign Activity</span>
        @if($pendingActivities > 0)<span class="badge warning" style="margin-left:auto">{{ $pendingActivities }} pending</span>@endif
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:11px;{{ $pendingActivities > 0 ? '' : 'margin-left:auto' }}"></i>
      </a>
      <a href="{{ route('admin.teachers.timetable.print', $teacher) }}" target="_blank" class="th-qa-btn">
        <span class="th-qa-icon" style="background:rgba(124,58,237,.1);color:#7c3aed"><i class="fas fa-calendar-alt"></i></span>
        <span class="th-qa-label">Print Timetable</span>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:11px;margin-left:auto"></i>
      </a>
      <a href="{{ route('admin.teachers.edit', $teacher) }}" class="th-qa-btn">
        <span class="th-qa-icon" style="background:rgba(217,119,6,.1);color:#d97706"><i class="fas fa-pen"></i></span>
        <span class="th-qa-label">Edit Profile</span>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:11px;margin-left:auto"></i>
      </a>
    </div>
  </div>

  {{-- Signature --}}
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <div class="card-title"><i class="fas fa-signature"></i> Signature</div>
      <a href="{{ route('admin.teachers.edit', $teacher) }}#sig-tab-draw" class="btn btn-outline btn-sm">
        <i class="fas fa-pen"></i> {{ $teacher->signature ? 'Update' : 'Add' }}
      </a>
    </div>
    <div class="card-body" style="padding:18px 20px">
      @if($teacher->signature)
        <div style="display:flex;align-items:center;gap:16px">
          <div style="flex-shrink:0;background:#fff;border:1px solid var(--border);border-radius:8px;padding:8px 14px">
            <img src="{{ storage_asset($teacher->signature) }}" alt="Signature" style="height:46px;max-width:170px;object-fit:contain">
          </div>
          <div style="font-size:12px;color:var(--text-muted)">On file — appears on this teacher's printed ID card.</div>
        </div>
      @else
        <div style="display:flex;align-items:center;gap:12px;color:var(--text-muted);font-size:13px">
          <i class="fas fa-triangle-exclamation" style="color:#f59e0b"></i>
          No signature on file yet. <a href="{{ route('admin.teachers.edit', $teacher) }}#sig-tab-draw" style="color:var(--primary);font-weight:600">Draw or upload one</a>.
        </div>
      @endif
    </div>
  </div>

  {{-- Login Credentials --}}
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <div class="card-title"><i class="fas fa-key"></i> Login Credentials</div>
      <form action="{{ route('admin.teachers.reset-password', $teacher) }}" method="POST"
            onsubmit="return confirm('Generate a new password for {{ $teacher->name }}? The old one will stop working immediately.')">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Reset Password</button>
      </form>
    </div>
    <div class="card-body" style="padding:16px 20px;display:flex;flex-direction:column;gap:14px">
      <div>
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Employee ID (Username)</div>
        <div style="font-weight:700;font-family:monospace;margin-top:4px;font-size:14px">{{ $teacher->employee_id }}</div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Login Email</div>
        <div style="font-weight:700;font-family:monospace;margin-top:4px;font-size:14px;word-break:break-all">{{ $teacher->email }}</div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Password</div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
          <span id="teacherPwMask" style="font-weight:700;font-family:monospace;font-size:14px">••••••••</span>
          <span id="teacherPwValue" style="display:none;font-weight:700;font-family:monospace;font-size:14px">{{ $teacher->login_password ?? '—' }}</span>
          @if($teacher->login_password)
            <button type="button" class="btn btn-outline btn-sm" style="padding:4px 8px" onclick="
              document.getElementById('teacherPwMask').style.display='none';
              document.getElementById('teacherPwValue').style.display='inline';
              this.style.display='none';"><i class="fas fa-eye"></i></button>
          @endif
        </div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Last Password Reset</div>
        <div style="font-weight:500;margin-top:4px;font-size:14px">{{ $teacher->password_reset_at?->format('M d, Y g:i A') ?? '—' }}</div>
      </div>
    </div>
  </div>

  {{-- Assigned Classes --}}
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chalkboard"></i> Assigned Classes</div></div>
    <div class="card-body" style="padding:16px 20px">
      @forelse($teacher->classes as $cls)
        <span class="th-chip">{{ $cls->display_name ?? $cls->name }}</span>
      @empty
        <div style="display:flex;align-items:center;gap:10px;color:var(--text-muted);font-size:13px">
          <i class="fas fa-circle-info"></i> No classes assigned yet.
        </div>
      @endforelse
    </div>
  </div>

  </div>{{-- /left column --}}

  {{-- Right: Details Card --}}
  <div class="card">
    <div class="card-header"><div class="card-title">Personal & Professional Details</div></div>
    <div class="card-body th-details" style="padding:0 20px">
@foreach([
    ['fas fa-envelope',       'Email',              $teacher->email],
    ['fas fa-phone',          'Phone',              $teacher->phone],
    ['fas fa-triangle-exclamation', 'Emergency Contact', $teacher->emergency_contact],
    ['fas fa-venus-mars',     'Gender',             ucfirst($teacher->gender ?? '—')],
    ['fas fa-cake-candles',   'Date of Birth',      $teacher->date_of_birth?->format('M d, Y')],
    ['fas fa-droplet',        'Blood Group',        $teacher->blood_group],
    ['fas fa-ring',           'Marital Status',     $teacher->marital_status ? ucfirst($teacher->marital_status) : null],
    ['fas fa-id-card',        'CNIC',               $teacher->cnic],
    ['fas fa-people-roof',    "Father / Guardian",  $teacher->father_name],
    ['fas fa-graduation-cap', 'Qualification',      $teacher->qualification],
    ['fas fa-briefcase',      'Experience',         ($teacher->experience ?? '—') . ' years'],
    ['fas fa-user-tie',       'Designation',        $teacher->designation],
    ['fas fa-building',       'Department',         $teacher->department],
    ['fas fa-file-signature', 'Employment Type',    $teacher->employment_type ? ucfirst($teacher->employment_type) : null],
    ['fas fa-money-bill',     'Salary',             'Rs. ' . number_format($teacher->salary ?? 0)],
    ['fas fa-building-columns','Bank',              $teacher->bank_name],
    ['fas fa-credit-card',    'Bank Account',       $teacher->bank_account_number ? "{$teacher->bank_account_title} — {$teacher->bank_account_number}" : null],
    ['fas fa-calendar',       'Joining Date',       $teacher->joining_date?->format('M d, Y')],
    ['fas fa-book',           'Subject',            $teacher->subject->name ?? '—'],
    ['fas fa-map-marker-alt', 'Address',            trim(($teacher->address ?? '') . ($teacher->city ? ', ' . $teacher->city : ''), ', ')],
] as [$icon, $label, $value])
<div class="th-detail-row">

    <div class="th-detail-icon">
        <i class="{{ $icon }}"></i>
    </div>

    <div style="flex:1;min-width:0">
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px">{{ $label }}</div>
        <div style="font-weight:600;margin-top:2px;font-size:13.5px;{{ $value ? '' : 'color:var(--text-muted);font-weight:400' }}">{{ $value ?: 'Not provided' }}</div>
    </div>
</div>
@endforeach

    </div>
  </div>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <div class="card-title"><i class="fas fa-calendar-alt"></i> Teaching Schedule</div>
    <a href="{{ route('admin.teachers.timetable.print', $teacher) }}" target="_blank" class="btn btn-outline btn-sm">
      <i class="fas fa-print"></i> Print
    </a>
  </div>
  <div class="card-body">
    @include('timetable._agenda', [
      'items'     => $timetable,
      'context'   => 'teacher',
      'emptyText' => 'No periods have been assigned to this teacher yet.',
    ])
  </div>
</div>

@endsection
