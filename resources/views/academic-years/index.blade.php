@extends('layouts.app')
@section('title', 'Academic Sessions')
@section('page_title', 'Academic Sessions')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Academic Sessions <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px;max-width:760px">
      This is now the source of truth for <strong>Session → Program → Part/Semester</strong>. Link each session to the Programs and semesters offered in it; admissions and Accounts default installment dates use the same structure.
    </div>
  </div>
  <a href="{{ route('academic-years.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Academic Session</a>
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
          <th>Session</th>
          <th style="text-align:center">Programs Linked</th>
          <th style="text-align:center">Parts / Semesters</th>
          <th style="text-align:center">Students</th>
          <th style="text-align:center">Default</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $year)
        @php $programCount = $year->schoolClasses->pluck('program_id')->filter()->unique()->count(); @endphp
        <tr>
          <td style="font-weight:700">{{ $year->name }}</td>
          <td style="text-align:center"><span class="badge gray">{{ $programCount }}</span></td>
          <td style="text-align:center"><span class="badge gray">{{ $year->school_classes_count }}</span></td>
          <td style="text-align:center">{{ $year->students_count }}</td>
          <td style="text-align:center">
            @if($year->is_default)<span class="badge success">Default</span>@else — @endif
          </td>
          <td style="text-align:center">
            @if($year->is_active)<span class="badge success">Active</span>@else<span class="badge gray">Inactive</span>@endif
          </td>
          <td style="text-align:center">
            <a href="{{ route('academic-years.edit', $year) }}" class="btn btn-sm btn-outline"><i class="fas fa-link"></i> Manage Links</a>
            <form action="{{ route('academic-years.destroy', $year) }}" method="POST" style="display:inline"
              data-confirm="This permanently removes {{ $year->name }}." data-confirm-title="Delete this academic session?"
              data-confirm-variant="danger" data-confirm-icon="fa-trash" data-confirm-ok-text="Yes, delete">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted)">No academic sessions added yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top:20px">{{ $items->links() }}</div>

@endsection
