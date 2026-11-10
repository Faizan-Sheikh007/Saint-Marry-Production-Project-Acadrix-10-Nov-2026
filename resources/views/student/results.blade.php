@extends('layouts.app')
@section('title', 'My Results')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-award" style="color:var(--primary)"></i> My Results</div>
    <div class="page-subtitle">{{ $student->schoolClass->display_name ?? $student->schoolClass->name ?? '' }} — Roll No. {{ $student->roll_number }}</div>
  </div>
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
            <a href="{{ route('student.results.show', $pub->batch_id) }}" target="_blank" class="btn btn-primary btn-sm">
              <i class="fas fa-file-arrow-down"></i> View / Download Marksheet
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="3"><div class="empty-state"><i class="fas fa-award"></i><p>No results have been published for your class yet. Check back once your teachers finish grading and the admin publishes it.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
