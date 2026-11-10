@extends('layouts.app')
@section('title', 'Teachers')
@section('content')

<div style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-chalkboard-teacher" style="color:var(--primary)"></i> Teachers <span style="color:var(--text-muted);font-size:16px">({{ $teachers->total() }})</span></div>
<div style="font-size:12px;color:var(--text-muted);margin-bottom:20px">Read-only directory — salary is view-only. Hiring and profile edits are managed by EMS.</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Name or employee ID..." value="{{ request('search') }}">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('accounts.teachers.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Teacher</th><th>Department</th><th style="text-align:right">Salary</th><th></th></tr></thead>
      <tbody>
        @forelse($teachers as $t)
        <tr>
          <td><div style="font-weight:600">{{ $t->name }}</div><div style="font-size:11px;color:var(--text-muted)">{{ $t->employee_id }}</div></td>
          <td>{{ $t->department ?? '—' }}</td>
          <td style="text-align:right">Rs.{{ number_format($t->salary ?? 0) }}</td>
          <td style="text-align:center"><a href="{{ route('accounts.teachers.show', $t) }}" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a></td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted)">No teachers found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:14px 20px">{{ $teachers->links() }}</div>
</div>

@endsection
