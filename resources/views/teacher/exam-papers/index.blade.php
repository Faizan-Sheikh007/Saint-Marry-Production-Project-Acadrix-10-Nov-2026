@extends('layouts.app')
@section('title', 'My Exam Papers')
@section('page_title', 'My Exam Papers')
@section('content')

<div style="font-size:22px;font-weight:800;margin-bottom:6px">Exam Papers</div>
<p style="color:var(--text-muted);margin-bottom:20px">One paper is required per subject/exam assigned to you. Build your questions, save a draft, or submit when ready.</p>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Examination</th>
          <th>Subject</th>
          <th>Class</th>
          <th>Exam Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($papers as $paper)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><strong>{{ $paper->examination->name ?? '—' }}</strong></td>
          <td>{{ $paper->examination->subject->name ?? '—' }}</td>
          <td>{{ $paper->examination->schoolClass->name ?? '—' }}</td>
          <td>{{ optional($paper->examination->exam_date)->format('d M, Y') ?? '—' }}</td>
          <td><span class="badge {{ $paper->status_color }}">{{ $paper->status_label }}</span></td>
          <td>
            <a href="{{ route('teacher.examinations.papers.build', $paper) }}" class="btn btn-primary btn-sm">
              <i class="fas fa-pen"></i> {{ $paper->status === 'due' ? 'Create Paper' : 'Edit Paper' }}
            </a>
            @if($paper->status === 'uploaded')
              <a href="{{ route('teacher.examinations.papers.print', $paper) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No exam papers assigned to you yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
