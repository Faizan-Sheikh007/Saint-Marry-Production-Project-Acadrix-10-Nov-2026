@extends('layouts.app')

@section('title', 'Parents')
@section('page_title', 'Parent Accounts')

@section('content')

<!-- Header Actions -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Parents <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">Guardian portal accounts &amp; the children linked to them</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('admin.parents.create') }}" class="btn btn-primary">
      <i class="fas fa-user-plus"></i> Add Parent
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
  </div>
@endif

<!-- Search -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('admin.parents.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name, phone, email, CNIC..." value="{{ request('search') }}">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Search</button>
      <a href="{{ route('admin.parents.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<!-- Parents Grid -->
<div class="grid grid-3" style="gap:18px">
  @forelse($items as $parent)
  <div class="card" style="display:flex;flex-direction:column">
    <div class="card-body" style="padding:20px">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
        <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0">
          {{ strtoupper(substr($parent->name, 0, 2)) }}
        </div>
        <div style="min-width:0">
          <div style="font-weight:700;font-size:15.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $parent->name }}</div>
          <div style="font-size:12px;color:var(--text-muted)">{{ $parent->occupation ?: 'Parent / Guardian' }}</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--text-muted);margin-bottom:14px">
        <div><i class="fas fa-phone" style="width:16px;color:var(--primary)"></i> {{ $parent->phone }}</div>
        @if($parent->email)
          <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><i class="fas fa-envelope" style="width:16px;color:var(--primary)"></i> {{ $parent->email }}</div>
        @endif
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;color:#334155">
          <i class="fas fa-child-reaching" style="color:var(--primary)"></i>
          {{ $parent->students->count() }} {{ Str::plural('Child', $parent->students->count()) }}
        </div>
        @if($parent->user_id)
          <span class="badge success" style="font-size:10.5px"><i class="fas fa-key"></i> Portal Active</span>
        @else
          <span class="badge gray" style="font-size:10.5px">No Login</span>
        @endif
      </div>

      @if($parent->students->count())
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px">
          @foreach($parent->students->take(3) as $child)
            <span style="background:#eef2ff;color:#4338ca;font-size:11px;font-weight:600;padding:4px 9px;border-radius:20px">{{ $child->name }}</span>
          @endforeach
          @if($parent->students->count() > 3)
            <span style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;padding:4px 9px;border-radius:20px">+{{ $parent->students->count() - 3 }} more</span>
          @endif
        </div>
      @else
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px;font-style:italic">No children linked yet</div>
      @endif
    </div>

    <div style="border-top:1px solid #e2e8f0;padding:12px 20px;display:flex;gap:8px;margin-top:auto">
      <a href="{{ route('admin.parents.show', $parent) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center" title="View">
        <i class="fas fa-eye"></i> View
      </a>
      <a href="{{ route('admin.parents.edit', $parent) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center" title="Edit">
        <i class="fas fa-edit"></i> Edit
      </a>
      <form action="{{ route('admin.parents.reset-password', $parent) }}" method="POST"
        data-confirm="This issues a brand-new portal password for {{ $parent->name }}. The old password will stop working immediately."
        data-confirm-title="Reset portal password?"
        data-confirm-icon="fa-key"
        data-confirm-ok-text="Yes, reset password">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm" title="Reset Password">
          <i class="fas fa-key"></i>
        </button>
      </form>
      <form action="{{ route('admin.parents.destroy', $parent) }}" method="POST"
        data-confirm="This permanently removes {{ $parent->name }} and unlinks their children. This can't be undone."
        data-confirm-title="Delete this parent?"
        data-confirm-variant="danger"
        data-confirm-icon="fa-trash"
        data-confirm-ok-text="Yes, delete">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)" title="Delete">
          <i class="fas fa-trash"></i>
        </button>
      </form>
    </div>
  </div>
  @empty
  <div class="card" style="grid-column:1/-1">
    <div class="card-body" style="text-align:center;padding:50px 20px;color:var(--text-muted)">
      <i class="fas fa-people-roof" style="font-size:36px;opacity:0.3;display:block;margin-bottom:14px"></i>
      No parent accounts found.
      <a href="{{ route('admin.parents.create') }}" style="color:var(--primary)">Add your first parent</a>
    </div>
  </div>
  @endforelse
</div>

<div style="margin-top:20px">
  {{ $items->withQueryString()->links() }}
</div>

@endsection
