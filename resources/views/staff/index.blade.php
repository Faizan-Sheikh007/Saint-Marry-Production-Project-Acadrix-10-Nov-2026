@extends('layouts.app')
@section('title', 'Other Staff')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Other Staff <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
    <div style="font-size:13px;color:var(--text-muted)">Non-teaching employees — accountants, guards, drivers, librarians, and more.</div>
  </div>
  <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Staff Member</a>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('admin.staff.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name, employee ID, designation..." value="{{ request('search') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:150px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          @foreach(['active','inactive','terminated'] as $st)
          <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
      </div>
      @if($departments->isNotEmpty())
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Department</label>
        <select name="department" class="form-control">
          <option value="">All</option>
          @foreach($departments as $d)
          <option value="{{ $d }}" {{ request('department') == $d ? 'selected' : '' }}>{{ $d }}</option>
          @endforeach
        </select>
      </div>
      @endif
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('admin.staff.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Employee</th><th>Designation</th><th>Department</th><th>Employment</th><th>Base Salary</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($items as $s)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;overflow:hidden">
                @if($s->photo)
                  <img src="{{ asset('storage/'.$s->photo) }}" style="width:100%;height:100%;object-fit:cover">
                @else
                  {{ strtoupper(substr($s->name,0,2)) }}
                @endif
              </div>
              <div>
                <div style="font-weight:600">{{ $s->name }}</div>
                <div style="font-size:11px;color:var(--text-muted)">{{ $s->employee_id }}</div>
              </div>
            </div>
          </td>
          <td>{{ $s->designation }}</td>
          <td>{{ $s->department ?: '—' }}</td>
          <td>{{ ucfirst(str_replace('_',' ', $s->employment_type)) }}</td>
          <td>Rs. {{ number_format($s->salary, 2) }}</td>
          <td>
            @php $st = ['active'=>'success','inactive'=>'gray','terminated'=>'danger'][$s->status] ?? 'gray'; @endphp
            <span class="badge {{ $st }}">{{ ucfirst($s->status) }}</span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="{{ route('admin.staff.show', $s) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
              <a href="{{ route('admin.staff.edit', $s) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
              <form action="{{ route('admin.staff.destroy', $s) }}" method="POST" onsubmit="return confirm('Delete this staff member? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm" title="Delete" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No staff members yet. <a href="{{ route('admin.staff.create') }}">Add one</a></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $items->withQueryString()->links() }}</div>
</div>

@endsection
