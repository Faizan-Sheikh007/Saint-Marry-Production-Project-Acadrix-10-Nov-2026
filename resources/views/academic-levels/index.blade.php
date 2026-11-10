@extends('layouts.app')
@section('title', 'Academic Levels')
@section('page_title', 'Academic Levels')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Academic Levels <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">
      Shown as a dropdown on the admission form (replaces the old fixed College/University choice). Mark a level
      "Requires Institution" to show the Institution picker on the admission form when it's selected.
    </div>
  </div>
  <a href="{{ route('academic-levels.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Academic Level</a>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:20px"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th style="text-align:center">Requires Institution</th>
          <th style="text-align:center">Students</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $level)
        <tr>
          <td style="font-weight:700">{{ $level->name }}</td>
          <td style="text-align:center">
            @if($level->requires_institution)
              <span class="badge success">Yes</span>
            @else
              <span class="badge gray">No</span>
            @endif
          </td>
          <td style="text-align:center">{{ $level->students_count }}</td>
          <td style="text-align:center">
            @if($level->is_active)
              <span class="badge success">Active</span>
            @else
              <span class="badge gray">Inactive</span>
            @endif
          </td>
          <td style="text-align:center">
            <a href="{{ route('academic-levels.edit', $level) }}" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
            <form action="{{ route('academic-levels.destroy', $level) }}" method="POST" style="display:inline"
              data-confirm="This permanently removes {{ $level->name }}." data-confirm-title="Delete this academic level?"
              data-confirm-variant="danger" data-confirm-icon="fa-trash" data-confirm-ok-text="Yes, delete">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">No academic levels added yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top:20px">{{ $items->links() }}</div>

@endsection
