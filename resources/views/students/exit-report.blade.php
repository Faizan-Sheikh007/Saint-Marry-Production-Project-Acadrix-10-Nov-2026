@extends('layouts.app')
@section('title', 'Dropped / Left Students Report')
@section('page_title', 'Dropped / Left Students')
@section('content')
<div class="card" style="margin-bottom:18px"><div class="card-body">
 <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
  <label>Status <select class="form-control" name="status"><option value="">Drop &amp; Left</option><option value="dropped" @selected(request('status')==='dropped')>Dropped</option><option value="left" @selected(request('status')==='left')>Left</option></select></label>
  <label>Program <select class="form-control" name="program_id"><option value="">All</option>@foreach($programs as $p)<option value="{{ $p->id }}" @selected(request('program_id')==$p->id)>{{ $p->name }}</option>@endforeach</select></label>
  <label>From <input class="form-control" type="date" name="from" value="{{ request('from') }}"></label>
  <label>To <input class="form-control" type="date" name="to" value="{{ request('to') }}"></label>
  <label>Search <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name / Student ID"></label>
  <button class="btn btn-primary">Apply filters</button><button class="btn btn-outline" type="submit" name="export" value="csv"><i class="fas fa-file-csv"></i> Generate CSV</button><button type="button" class="btn btn-outline" onclick="window.print()">Print report</button>
 </form>
 <small style="color:var(--text-muted)">Date range uses the last student record update, not a separate exit event date.</small>
</div></div>
<div class="card"><div class="card-header"><div class="card-title">Drop / Left Students — {{ $students->total() }} record(s)</div></div><div class="table-wrap"><table><thead><tr><th>Student ID</th><th>Name</th><th>Program</th><th>Semester</th><th>Session</th><th>Student Status</th><th>Last updated</th></tr></thead><tbody>
@forelse($students as $s)<tr><td>{{ $s->student_id }}</td><td>{{ $s->name }}</td><td>{{ $s->program?->name ?? '—' }}</td><td>{{ $s->schoolClass?->name ?? '—' }}</td><td>{{ $s->session }}</td><td>{{ $s->statusLabel() }}</td><td>{{ $s->updated_at?->format('d M Y') }}</td></tr>
@empty<tr><td colspan="7" style="text-align:center">No students match the filters.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $students->links() }}</div></div>
@endsection
