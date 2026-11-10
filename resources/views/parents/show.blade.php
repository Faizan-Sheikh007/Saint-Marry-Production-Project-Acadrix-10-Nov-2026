@extends('layouts.app')

@section('title', $parent->name)
@section('page_title', 'Parent Profile')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="{{ route('admin.parents.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('admin.parents.edit', $parent) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>

  @if($parent->user_id)
    <span class="badge success" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-key"></i> Portal Active</span>
  @else
    <span class="badge gray" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-triangle-exclamation"></i> No Portal Login</span>
  @endif
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
  </div>
@endif

<div class="grid grid-2" style="gap:20px">
  <!-- Profile Card -->
  <div class="card">
    <div class="card-body" style="text-align:center;padding:32px 24px">
      <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px">
        {{ strtoupper(substr($parent->name, 0, 2)) }}
      </div>
      <div style="font-size:20px;font-weight:800">{{ $parent->name }}</div>
      <div style="color:var(--text-muted);margin-bottom:16px">{{ $parent->occupation ?: 'Parent / Guardian' }}</div>
      <span class="badge {{ $parent->user_id ? 'success' : 'gray' }}">{{ $parent->user_id ? 'Portal Login Active' : 'No Portal Login' }}</span>
    </div>

    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
      <div style="display:flex;flex-wrap:wrap;gap:12px">
        <div style="flex:1;min-width:80px;text-align:center;background:#eff6ff;border-radius:10px;padding:12px">
          <div style="font-size:20px;font-weight:800;color:#2563eb">{{ $parent->students->count() }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">{{ Str::plural('Child', $parent->students->count()) }}</div>
        </div>
        <div style="flex:1;min-width:80px;text-align:center;background:#f0fdf4;border-radius:10px;padding:12px">
          <div style="font-size:14px;font-weight:800;color:#16a34a">{{ $parent->created_at?->format('d M, Y') }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">Registered</div>
        </div>
      </div>
    </div>

    <!-- Portal Login -->
    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
      <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">
        <i class="fas fa-key"></i> Portal Login
      </div>
      @if($parent->user_id)
        <div style="font-size:13px;word-break:break-all;margin-bottom:12px"><i class="fas fa-user" style="width:18px;color:var(--primary)"></i> {{ $parent->user->email }}</div>
        <form action="{{ route('admin.parents.reset-password', $parent) }}" method="POST"
          data-confirm="This issues a brand-new portal password for {{ $parent->name }}. The old password will stop working immediately."
          data-confirm-title="Reset portal password?"
          data-confirm-icon="fa-key"
          data-confirm-ok-text="Yes, reset password">
          @csrf
          <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center">
            <i class="fas fa-rotate"></i> Reset Password
          </button>
        </form>
      @else
        <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px">No portal login yet.</div>
        <form action="{{ route('admin.parents.reset-password', $parent) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
            <i class="fas fa-key"></i> Issue Portal Login
          </button>
        </form>
      @endif
    </div>

    <!-- Contact Details -->
    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
      <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">
        <i class="fas fa-address-card"></i> Contact Details
      </div>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:13.5px">
        <div><i class="fas fa-phone" style="width:18px;color:var(--primary)"></i> {{ $parent->phone }}</div>
        @if($parent->email)
          <div style="word-break:break-all"><i class="fas fa-envelope" style="width:18px;color:var(--primary)"></i> {{ $parent->email }}</div>
        @endif
        @if($parent->cnic)
          <div><i class="fas fa-id-card" style="width:18px;color:var(--primary)"></i> {{ $parent->cnic }}</div>
        @endif
        @if($parent->address)
          <div><i class="fas fa-location-dot" style="width:18px;color:var(--primary)"></i> {{ $parent->address }}</div>
        @endif
      </div>
    </div>

    <!-- Portal Access -->
    <div style="border-top:1px solid #e2e8f0;padding:16px 20px">
      <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">
        <i class="fas fa-key"></i> Portal Access
      </div>
      @if($parent->user_id)
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin-bottom:4px">
          <div style="font-size:10.5px;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:3px">Login Email</div>
          <div style="font-weight:600;font-size:13.5px;word-break:break-all">{{ $parent->email }}</div>
        </div>
      @else
        <div style="font-size:12.5px;color:var(--text-muted)">
          No login yet. Add an email on the <a href="{{ route('admin.parents.edit', $parent) }}" style="color:var(--primary)">edit page</a> to create portal access.
        </div>
      @endif
    </div>
  </div>

  <!-- Linked Children -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
      <div class="card-title"><i class="fas fa-children" style="color:var(--primary)"></i> Linked Children</div>
      <a href="{{ route('admin.parents.edit', $parent) }}" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Manage</a>
    </div>
    <div class="card-body" style="padding:0">
      @forelse($parent->students as $child)
        <div style="display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid #f1f5f9">
          @if($child->photo)
            <img src="{{ storage_asset($child->photo) }}" alt="{{ $child->name }}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0">
          @else
            <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0">
              {{ strtoupper(substr($child->name, 0, 2)) }}
            </div>
          @endif
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;font-size:14px">{{ $child->name }}</div>
            <div style="font-size:12px;color:var(--text-muted)">
              {{ $child->student_id }} @if($child->schoolClass) &middot; {{ $child->schoolClass->name }} @endif
            </div>
          </div>
          <span class="badge {{ $child->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($child->status) }}</span>
          <a href="{{ route('students.show', $child) }}" class="btn btn-outline btn-sm" title="View Student">
            <i class="fas fa-eye"></i>
          </a>
        </div>
      @empty
        <div style="text-align:center;padding:40px 20px;color:var(--text-muted)">
          <i class="fas fa-user-graduate" style="font-size:30px;opacity:.3;display:block;margin-bottom:12px"></i>
          No children linked to this parent yet.
          <div style="margin-top:8px">
            <a href="{{ route('admin.parents.edit', $parent) }}" style="color:var(--primary)">Link a child now</a>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>

@endsection
