@extends('layouts.app')
@section('title', $program->name)
@section('page_title', $program->name)
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="{{ route('programs.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('classes.create', ['program_id' => $program->id]) }}" class="btn btn-primary btn-sm"><i class="fas fa-add"></i> Add Section / Semester</a>
  <a href="{{ route('programs.edit', $program) }}" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit Program</a>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">{{ $program->name }} @if($program->code) <span style="color:var(--text-muted)">({{ $program->code }})</span>@endif</div></div>
  <div class="card-body">
    <div class="grid grid-2" style="gap:16px">
      <div><strong>Level:</strong> {{ ucfirst($program->level) }}</div>
      <div><strong>Duration:</strong> {{ $program->duration_terms ?? '—' }} term(s)</div>
    </div>
    @if($program->description)<p style="margin-top:12px;color:var(--text-muted)">{{ $program->description }}</p>@endif
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Sections / Semesters</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Section</th><th>Capacity</th><th>Students</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse($program->sections as $section)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><strong><a href="{{ route('classes.show', $section) }}">{{ $section->name }}</a></strong></td>
          <td>{{ $section->section ?? '—' }}</td>
          <td>{{ $section->capacity }}</td>
          <td>{{ $section->students_count ?? $section->students->count() }}</td>
          <td>
            <a href="{{ route('students.index', ['class' => $section->id]) }}" class="btn btn-outline btn-sm" title="View students"><i class="fas fa-user-graduate"></i></a>
            <a href="{{ route('classes.edit', $section) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">No sections/semesters yet under this program.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
