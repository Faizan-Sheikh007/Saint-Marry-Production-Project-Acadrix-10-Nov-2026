@extends('layouts.app')
@section('title', 'My Timetable')
@section('content')

<div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px">
  <div>
    <h1 style="font-size:20px;font-weight:800">My Timetable</h1>
    <p style="color:var(--text-muted);font-size:13.5px;margin-top:4px">Your weekly teaching schedule across all classes</p>
  </div>
  <a href="{{ route('teacher.timetable.print') }}" target="_blank" class="btn btn-outline btn-sm">
    <i class="fas fa-print"></i> Print / Download
  </a>
</div>

<div class="card">
  <div class="card-body">
    @include('timetable._grid', ['items' => $items, 'context' => 'teacher', 'emptyText' => 'No periods have been assigned to you yet.'])
  </div>
</div>

@endsection
