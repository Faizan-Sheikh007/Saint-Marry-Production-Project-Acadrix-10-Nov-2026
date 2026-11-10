@extends('layouts.app')
@section('title', $student->name . ' — Results')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-award" style="color:var(--primary)"></i> {{ $student->name }}'s Results</div>
    <div class="page-subtitle">{{ $student->schoolClass->display_name ?? $student->schoolClass->name ?? '' }} &middot; {{ $student->student_id }}</div>
  </div>
  <a href="{{ route('parent.child', $student) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to {{ $student->name }}</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Examination</th><th>Published</th><th></th></tr></thead>
      <tbody>
        @forelse($publications as $pub)
        <tr>
          <td style="font-weight:600">{{ $pub->exam_name }}</td>
          <td>{{ $pub->published_at->format('d M Y') }}</td>
          <td>
            <a href="{{ route('parent.child.results.show', [$student, $pub->batch_id]) }}" target="_blank" class="btn btn-primary btn-sm">
              <i class="fas fa-file-arrow-down"></i> View / Download Marksheet
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="3"><div class="empty-state"><i class="fas fa-award"></i><p>No results have been published for {{ $student->name }}'s class yet.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
