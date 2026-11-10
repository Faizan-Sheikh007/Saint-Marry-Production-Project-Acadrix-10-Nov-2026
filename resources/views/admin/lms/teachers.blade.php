@extends('layouts.app')
@section('title', 'LMS Monitor — Teachers')
@section('page_title', 'LMS Monitor')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-chalkboard-user" style="color:var(--primary)"></i> LMS Monitor</div>
    <div class="page-subtitle">Read-only view. Pick a teacher to see the classes and courses they run.</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Teachers</div>
    <span class="tag">{{ $teachers->count() }}</span>
  </div>
  @if($teachers->isEmpty())
    <div class="empty-state"><i class="fas fa-chalkboard-user"></i><p>No teachers found.</p></div>
  @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>Teacher</th><th>Subject</th><th>Courses</th><th></th></tr></thead>
        <tbody>
          @foreach($teachers as $teacher)
          <tr>
            <td style="display:flex;align-items:center;gap:10px">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12.5px;flex-shrink:0">
                {{ strtoupper(substr($teacher->name, 0, 2)) }}
              </div>
              <span style="font-weight:600">{{ $teacher->name }}</span>
            </td>
            <td>{{ $teacher->subject->name ?? '—' }}</td>
            <td><span class="tag">{{ $teacher->courses_count }} course{{ $teacher->courses_count === 1 ? '' : 's' }}</span></td>
            <td style="text-align:right">
              <a href="{{ route('admin.lms.teacher-subjects', $teacher) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-eye"></i> View Subjects
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
