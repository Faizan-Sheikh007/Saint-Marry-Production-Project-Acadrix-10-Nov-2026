@extends('layouts.app')
@section('title', $student->name . ' — Exams')
@section('page_title', $student->name . ' — Exams')
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('parent.child', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:22px;font-weight:800">{{ $student->name }} — Exams &amp; Results</div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Exam</th><th>Subject</th><th>Date &amp; Time</th><th>Room</th><th>Marks</th><th>Grade</th><th>Status</th></tr>
      </thead>
      <tbody>
        @forelse($exams as $exam)
        @php $result = $results->get($exam->id); @endphp
        <tr>
          <td><div style="font-weight:600">{{ $exam->name }}</div><span class="tag">{{ $exam->type_label }}</span></td>
          <td>{{ $exam->subject->name ?? 'All Subjects' }}</td>
          <td>
            <div style="font-weight:600;font-size:12.5px">{{ $exam->exam_date?->format('d M Y') }}</div>
            @if($exam->time_label)<div style="font-size:11px;color:var(--text-muted)">{{ $exam->time_label }}</div>@endif
          </td>
          <td>{{ $exam->room ?: '—' }}</td>
          <td>
            @if($result)
              <strong>{{ $result->marks_obtained }}</strong> <span style="color:var(--text-muted)">/ {{ $exam->total_marks }}</span>
            @else
              <span style="color:var(--text-muted);font-size:12px">Not yet marked</span>
            @endif
          </td>
          <td>
            @if($result)
              @php $gradeColor = match($result->grade) { 'A+','A' => 'success', 'B','C' => 'info', 'D' => 'warning', default => 'danger' }; @endphp
              <span class="badge {{ $gradeColor }}">{{ $result->grade }}</span>
            @else
              <span style="color:var(--text-muted)">—</span>
            @endif
          </td>
          <td>
            @php $statusMap = ['scheduled' => 'info', 'completed' => 'success', 'cancelled' => 'danger']; @endphp
            <span class="badge {{ $statusMap[$exam->status] ?? 'gray' }}">{{ ucfirst($exam->status) }}</span>
          </td>
        </tr>
        @empty
        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-pen"></i><p>No examinations scheduled for this class yet.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
