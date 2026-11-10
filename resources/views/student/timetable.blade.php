@extends('layouts.app')
@section('title', 'My Timetable')
@section('content')

@if(!$student)
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Your student profile is not linked yet. Please contact admin.</div>
@else

<div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px">
  <div>
    <h1 style="font-size:20px;font-weight:800">My Timetable</h1>
    <p style="color:var(--text-muted);font-size:13.5px;margin-top:4px">
      {{ $student->schoolClass->display_name ?? $student->schoolClass->name ?? 'No class assigned' }}
    </p>
  </div>
  <a href="{{ route('student.timetable.print') }}" target="_blank" class="btn btn-outline btn-sm">
    <i class="fas fa-print"></i> Print / Download
  </a>
</div>

<div class="card">
  <div class="card-body">
    @include('timetable._grid', ['items' => $items, 'context' => 'class', 'emptyText' => 'No timetable has been set up for your class yet.'])
  </div>
</div>

@endif
@endsection
