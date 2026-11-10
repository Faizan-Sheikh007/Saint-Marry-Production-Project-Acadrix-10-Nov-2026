@extends('layouts.app')
@section('title', $schoolClass->name)
@section('page_title', 'Class Details')
@section('content')
<div style="display:flex;gap:12px;margin-bottom:24px">
  <a href="{{ route('classes.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('classes.edit', $schoolClass) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
</div>
<div class="grid grid-2" style="gap:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">{{ $schoolClass->name }} — Section {{ $schoolClass->section }}</div></div>
    <div class="card-body">
      <p><strong>Program / Class:</strong> @if($schoolClass->program)<a href="{{ route('programs.show', $schoolClass->program) }}">{{ $schoolClass->program->name }}</a>@else — @endif</p>
      <p><strong>Level:</strong> {{ ucfirst($schoolClass->level) }}</p>
      <p><strong>Capacity:</strong> {{ $schoolClass->capacity }}</p>
      <p><strong>Students:</strong> {{ $schoolClass->students()->count() }}</p>
      <div style="margin-top:16px;display:flex;gap:10px">
        <a href="{{ route('classes.id-cards.bulk', $schoolClass) }}" class="btn btn-outline btn-sm"><i class="fas fa-id-card"></i> Bulk ID Cards</a>
      </div>
      @if($schoolClass->feeTemplates->count())
      <div style="margin-top:16px">
        <strong>Fee Templates:</strong>
        <ul style="margin:6px 0 0 18px">
          @foreach($schoolClass->feeTemplates as $t)
          <li>@if(auth()->user()?->isAccountant())<a href="{{ route('fee-templates.show', $t) }}">{{ $t->name }}</a>@else{{ $t->name }}@endif</li>
          @endforeach
        </ul>
      </div>
      @endif
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Students</div></div>
    <div class="table-wrap">
      <table><thead><tr><th>#</th><th>Name</th><th>Roll No</th></tr></thead>
      <tbody>
        @forelse($schoolClass->students as $s)
        <tr><td>{{ $loop->iteration }}</td><td>{{ $s->name }} @include('partials.student-status-badge', ['statusStudent' => $s])</td><td>{{ $s->roll_number }}</td></tr>
        @empty
        <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--text-muted)">No students</td></tr>
        @endforelse
      </tbody></table>
    </div>
  </div>
</div>
@endsection
