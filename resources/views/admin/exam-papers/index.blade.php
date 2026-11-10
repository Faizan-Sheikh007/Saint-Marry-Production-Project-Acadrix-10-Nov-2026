@extends('layouts.app')
@section('title', 'Examination Papers')
@section('page_title', 'Examination Papers')
@section('content')

<div style="font-size:22px;font-weight:800;margin-bottom:6px">Examination Papers</div>
<p style="color:var(--text-muted);margin-bottom:20px">Grouped by teacher — a teacher covering 5 subjects will show 5 papers below, one per subject/exam.</p>

<div class="grid grid-3" style="gap:16px;margin-bottom:24px">
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:26px;font-weight:800;color:var(--danger)">{{ $totals['due'] }}</div>
    <div style="color:var(--text-muted);font-size:13px">Due</div>
  </div></div>
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:26px;font-weight:800;color:#b45309">{{ $totals['pending'] }}</div>
    <div style="color:var(--text-muted);font-size:13px">Pending</div>
  </div></div>
  <div class="card"><div class="card-body" style="text-align:center">
    <div style="font-size:26px;font-weight:800;color:#166534">{{ $totals['uploaded'] }}</div>
    <div style="color:var(--text-muted);font-size:13px">Uploaded</div>
  </div></div>
</div>

@forelse($teachers as $teacher)
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-chalkboard-user"></i> {{ $teacher->name }}
      <span style="font-weight:500;color:var(--text-muted);font-size:13px">({{ $teacher->examPapers->count() }} paper{{ $teacher->examPapers->count() === 1 ? '' : 's' }})</span>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Examination</th>
          <th>Subject</th>
          <th>Class</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($teacher->examPapers as $paper)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $paper->examination->name ?? '—' }}</td>
          <td>{{ $paper->examination->subject->name ?? '—' }}</td>
          <td>{{ $paper->examination->schoolClass->name ?? '—' }}</td>
          <td><span class="badge {{ $paper->status_color }}">{{ $paper->status_label }}</span></td>
          <td>
            @if($paper->status === 'uploaded')
              <a href="{{ route('admin.examinations.papers.print', $paper) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a>
            @else
              <span style="color:var(--text-muted);font-size:12.5px">Not submitted yet</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@empty
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted)">No examinations scheduled yet.</div></div>
@endforelse

@endsection
