@extends('layouts.app')
@section('title', $course->title)
@section('page_title', $course->title)
@section('content')

@php
  $statusMap = ['draft' => 'gray', 'published' => 'success', 'archived' => 'warning'];
  $enrolledCount = $course->enrollments->count();
  $pendingQuestions = $course->questions->where('status', 'pending')->count();
  $ungradedCount = $allSubmissions->whereNull('marks_obtained')->count();
@endphp

<div class="page-header">
  <div>
    <div class="page-title">{{ $course->title }}</div>
    <div class="page-subtitle breadcrumb-path" style="margin-top:6px">
      @if($course->subject)<span class="crumb subject"><i class="fas fa-book"></i> {{ $course->subject->name }}</span>@endif
      @if($course->schoolClass)<i class="fas fa-chevron-right"></i><span class="crumb class"><i class="fas fa-users-rectangle"></i> {{ $course->schoolClass->section_label ?? $course->schoolClass->name }}</span>@endif
      <span class="badge {{ $statusMap[$course->status] ?? 'gray' }}">{{ ucfirst($course->status) }}</span>
      @if($isAdmin)<span class="badge gray"><i class="fas fa-eye"></i> Read-only (Admin)</span>@endif
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    @if($isAdmin)
      <a href="{{ url()->previous() }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    @else
      <a href="{{ route('lms.courses.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    @endif
    @if($isTeacher)
      <a href="{{ route('lms.courses.edit', $course) }}" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
      <form action="{{ route('lms.courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Delete this course and everything in it?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i> Delete</button>
      </form>
    @endif
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

@if($course->description)
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="font-size:13.5px;color:var(--text-muted);line-height:1.6">{{ $course->description }}</div>
</div>
@endif

<div class="grid grid-4" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-align-left"></i></div>
    <div><div class="stat-value">{{ $notesLessons->count() }}</div><div class="stat-label">Lessons &amp; Notes</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-circle-play"></i></div>
    <div><div class="stat-value">{{ $videoLessons->count() }}</div><div class="stat-label">Video Lectures</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-file-lines"></i></div>
    <div><div class="stat-value">{{ $course->assignments->count() }}</div><div class="stat-label">Assignments</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-users"></i></div>
    <div><div class="stat-value">{{ $enrolledCount }}</div><div class="stat-label">Enrolled</div></div>
  </div>
</div>

<div class="tab-bar" style="flex-wrap:wrap">
  <div class="tab-btn active" data-tab="lessons" onclick="lmsTab('lessons')">Lessons &amp; Notes</div>
  <div class="tab-btn" data-tab="video" onclick="lmsTab('video')">Video Lectures</div>
  <div class="tab-btn" data-tab="quizzes" onclick="lmsTab('quizzes')">Quizzes</div>
  <div class="tab-btn" data-tab="assignments" onclick="lmsTab('assignments')">Assignments</div>
  <div class="tab-btn" data-tab="announcements" onclick="lmsTab('announcements')">Announcements</div>
  <div class="tab-btn" data-tab="qa" onclick="lmsTab('qa')">
    Q&amp;A @if($isTeacher && $pendingQuestions)<span class="tag" style="margin-left:4px">{{ $pendingQuestions }}</span>@endif
  </div>
  @if($isTeacher || $isAdmin)
  <div class="tab-btn" data-tab="submissions" onclick="lmsTab('submissions')">
    Submissions @if($isTeacher && $ungradedCount)<span class="tag" style="margin-left:4px">{{ $ungradedCount }}</span>@endif
  </div>
  @endif
  <div class="tab-btn" data-tab="grades" onclick="lmsTab('grades')">Grades &amp; Progress</div>
</div>

{{-- Lessons & Notes --}}
<div class="lms-pane" id="pane-lessons">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <div class="card-title">Lessons &amp; Notes</div>
      @if($isTeacher)<a href="{{ route('lms.lessons.create', $course) }}?type=text" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Lesson</a>@endif
    </div>
    @if($notesLessons->isEmpty())
      <div class="empty-state"><i class="fas fa-align-left"></i><p>No lessons or notes added yet.</p></div>
    @else
      @foreach($notesLessons as $lesson)
      @php $done = $student && $lesson->isCompletedBy($student->id); @endphp
      <a href="{{ route('lms.lessons.show', [$course, $lesson]) }}" class="lesson-item" style="text-decoration:none;color:inherit">
        <div class="lesson-num {{ $done ? 'done' : '' }}">{{ $done ? '✓' : $loop->iteration }}</div>
        <div class="lesson-info">
          <div class="lesson-title">{{ $lesson->title }}</div>
          <div class="lesson-meta">
            <i class="fas fa-{{ $lesson->type === 'file' ? 'file' : 'align-left' }}"></i>
            {{ ucfirst($lesson->type) }}
            @if($lesson->duration_minutes) &middot; {{ $lesson->duration_minutes }} min @endif
            @if($lesson->file_attachment) &middot; <i class="fas fa-paperclip"></i> Notes attached @endif
            @if($lesson->is_free) &middot; <span style="color:var(--success)">Free preview</span> @endif
          </div>
        </div>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:12px"></i>
      </a>
      @endforeach
    @endif
  </div>

  @if($isTeacher)
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">Share Notes, Files or a Link</div></div>
    <div class="card-body">
      <form method="POST" action="{{ route('lms.materials.store', $course) }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-2" style="margin-bottom:12px">
          <div>
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 3 slides" required>
          </div>
          <div>
            <label class="form-label">Type</label>
            <select name="type" class="form-control" id="materialType" onchange="toggleMaterialFields()">
              <option value="file">Upload a file (PDF, Doc, Presentation…)</option>
              <option value="link">External link</option>
            </select>
          </div>
        </div>
        <div style="margin-bottom:12px" id="materialFileField">
          <label class="form-label">File</label>
          <input type="file" name="file" class="form-control">
        </div>
        <div style="margin-bottom:12px;display:none" id="materialUrlField">
          <label class="form-label">URL</label>
          <input type="url" name="url" class="form-control" placeholder="https://…">
        </div>
        <div style="margin-bottom:14px">
          <label class="form-label">Description (optional)</label>
          <input type="text" name="description" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> Share</button>
      </form>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <div class="card-title">Shared Notes &amp; Links</div>
      <span class="tag">{{ $course->materials->count() }}</span>
    </div>
    @if($course->materials->isEmpty())
      <div class="empty-state"><i class="fas fa-folder-open"></i><p>No notes, files or links shared yet.</p></div>
    @else
      @foreach($course->materials as $material)
      <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px">
        <div class="lesson-num"><i class="fas fa-{{ $material->isLink() ? 'link' : 'file-lines' }}" style="font-size:12px"></i></div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:13.5px">
            <a href="{{ $material->isLink() ? $material->url : storage_asset($material->file_path) }}" target="_blank" style="color:inherit;text-decoration:none">
              {{ $material->title }} <i class="fas fa-arrow-up-right-from-square" style="font-size:10px;color:var(--text-muted)"></i>
            </a>
          </div>
          @if($material->description)<div style="font-size:12.5px;color:var(--text-muted);margin-top:2px">{{ $material->description }}</div>@endif
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
            {{ $material->isLink() ? 'External link' : strtoupper($material->file_type) }} &middot; {{ $material->created_at->diffForHumans() }}
          </div>
        </div>
        @if($isTeacher)
        <form action="{{ route('lms.materials.destroy', [$course, $material]) }}" method="POST" onsubmit="return confirm('Remove this material?')">
          @csrf @method('DELETE')
          <button class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
        </form>
        @endif
      </div>
      @endforeach
    @endif
  </div>
</div>

{{-- Video Lectures --}}
<div class="lms-pane" id="pane-video" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Video Lectures</div>
      @if($isTeacher)<a href="{{ route('lms.lessons.create', $course) }}?type=video" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Video Lecture</a>@endif
    </div>
    @if($videoLessons->isEmpty())
      <div class="empty-state"><i class="fas fa-circle-play"></i><p>No video lectures added yet.</p></div>
    @else
      @foreach($videoLessons as $lesson)
      @php $done = $student && $lesson->isCompletedBy($student->id); @endphp
      <a href="{{ route('lms.lessons.show', [$course, $lesson]) }}" class="lesson-item" style="text-decoration:none;color:inherit">
        <div class="lesson-num {{ $done ? 'done' : '' }}">{{ $done ? '✓' : $loop->iteration }}</div>
        <div class="lesson-info">
          <div class="lesson-title">{{ $lesson->title }}</div>
          <div class="lesson-meta">
            <i class="fas fa-circle-play"></i> Video
            @if($lesson->duration_minutes) &middot; {{ $lesson->duration_minutes }} min @endif
            @if($lesson->is_free) &middot; <span style="color:var(--success)">Free preview</span> @endif
          </div>
        </div>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:12px"></i>
      </a>
      @endforeach
    @endif
  </div>
</div>

{{-- Quizzes --}}
<div class="lms-pane" id="pane-quizzes" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Quizzes</div>
      @if($isTeacher)<a href="{{ route('lms.quizzes.create', $course) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Quiz</a>@endif
    </div>
    @if($course->quizzes->isEmpty())
      <div class="empty-state"><i class="fas fa-circle-question"></i><p>No quizzes yet.</p></div>
    @else
      @foreach($course->quizzes as $quiz)
      <a href="{{ route('lms.quizzes.show', [$course, $quiz]) }}" class="lesson-item" style="text-decoration:none;color:inherit">
        <div class="lesson-num"><i class="fas fa-circle-question" style="font-size:11px"></i></div>
        <div class="lesson-info">
          <div class="lesson-title">{{ $quiz->title }}</div>
          <div class="lesson-meta">
            {{ $quiz->questions->count() }} question{{ $quiz->questions->count() === 1 ? '' : 's' }} &middot; {{ $quiz->total_marks }} marks &middot; {{ $quiz->time_limit }} min
            &middot; <span class="badge {{ $quiz->status === 'active' ? 'info' : 'gray' }}">{{ ucfirst($quiz->status) }}</span>
          </div>
        </div>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:12px"></i>
      </a>
      @endforeach
    @endif
  </div>
</div>

{{-- Assignments --}}
<div class="lms-pane" id="pane-assignments" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Assignments</div>
      @if($isTeacher)<a href="{{ route('lms.assignments.create', $course) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Assignment</a>@endif
    </div>
    @if($course->assignments->isEmpty())
      <div class="empty-state"><i class="fas fa-file-lines"></i><p>No assignments yet.</p></div>
    @else
      @foreach($course->assignments as $assignment)
      <a href="{{ route('lms.assignments.show', [$course, $assignment]) }}" class="lesson-item" style="text-decoration:none;color:inherit">
        <div class="lesson-num"><i class="fas fa-file-lines" style="font-size:11px"></i></div>
        <div class="lesson-info">
          <div class="lesson-title">{{ $assignment->title }}</div>
          <div class="lesson-meta">
            {{ $assignment->total_marks }} marks
            @if($assignment->due_date) &middot; Due {{ $assignment->due_date->format('d M Y') }} @endif
            &middot; <span class="badge {{ $assignment->status === 'active' ? 'info' : 'gray' }}">{{ ucfirst($assignment->status) }}</span>
            &middot; {{ $assignment->submissions->count() }} submitted
          </div>
        </div>
        <i class="fas fa-chevron-right" style="color:var(--text-muted);font-size:12px"></i>
      </a>
      @endforeach
    @endif
  </div>
</div>

{{-- Announcements --}}
<div class="lms-pane" id="pane-announcements" style="display:none">
  @if($isTeacher)
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">Post an Announcement</div></div>
    <div class="card-body">
      <form method="POST" action="{{ route('lms.announcements.store', $course) }}">
        @csrf
        <div style="margin-bottom:12px">
          <input type="text" name="title" class="form-control" placeholder="Title" required>
        </div>
        <div style="margin-bottom:12px">
          <textarea name="content" class="form-control" rows="3" placeholder="Write your announcement…" required></textarea>
        </div>
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:14px">
          <input type="checkbox" name="pinned" value="1"> Pin to top
        </label>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Post</button>
      </form>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header"><div class="card-title">Announcements</div></div>
    @if($course->lmsAnnouncements->isEmpty())
      <div class="empty-state"><i class="fas fa-bullhorn"></i><p>No announcements yet.</p></div>
    @else
      @foreach($course->lmsAnnouncements as $a)
      <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <div style="font-weight:700;font-size:13.5px">{{ $a->title }}</div>
          @if($a->pinned)<span class="badge warning"><i class="fas fa-thumbtack"></i> Pinned</span>@endif
          <span style="font-size:11px;color:var(--text-muted);margin-left:auto">{{ $a->created_at->diffForHumans() }}</span>
          @if($isTeacher)
          <form action="{{ route('lms.announcements.destroy', [$course, $a]) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline btn-sm" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
          </form>
          @endif
        </div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:6px;line-height:1.5">{{ $a->content }}</div>
      </div>
      @endforeach
    @endif
  </div>
</div>

{{-- Q&A --}}
<div class="lms-pane" id="pane-qa" style="display:none">
  @if($student)
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <div class="card-title">Ask the Teacher</div>
      <span class="tag">{{ $questionsAskedToday }}/3 today</span>
    </div>
    <div class="card-body">
      @if($questionsAskedToday >= 3)
        <div class="alert alert-warning" style="margin-bottom:0"><i class="fas fa-hourglass-half"></i> You've used all 3 questions for today. Come back tomorrow!</div>
      @else
        <form method="POST" action="{{ route('lms.questions.store', $course) }}">
          @csrf
          <textarea name="question" class="form-control" rows="3" placeholder="Type your question for the teacher…" required maxlength="2000"></textarea>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-top:10px"><i class="fas fa-paper-plane"></i> Ask</button>
        </form>
      @endif
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <div class="card-title">{{ $isTeacher ? 'Student Questions' : ($isAdmin ? 'All Questions' : 'Your Questions') }}</div>
      <span class="tag">{{ $course->questions->count() }}</span>
    </div>
    @if($course->questions->isEmpty())
      <div class="empty-state"><i class="fas fa-comments"></i><p>No questions yet.</p></div>
    @else
      @foreach($course->questions as $q)
      @continue(!$isTeacher && !$isAdmin && $student && $q->student_id !== $student->id)
      <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span style="font-weight:700;font-size:13px">{{ $q->student->name ?? 'Student' }}</span>
          <span class="badge {{ $q->status === 'answered' ? 'success' : 'warning' }}">{{ ucfirst($q->status) }}</span>
          <span style="font-size:11px;color:var(--text-muted);margin-left:auto">{{ $q->created_at->diffForHumans() }}</span>
        </div>
        <div style="font-size:13px;margin-top:6px;line-height:1.5">{{ $q->question }}</div>

        @if($q->status === 'answered')
          <div style="margin-top:10px;background:var(--bg);border-radius:8px;padding:10px 14px">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px">Teacher's answer</div>
            <div style="font-size:13px;line-height:1.5">{{ $q->answer }}</div>
          </div>
        @elseif($isTeacher)
          <form method="POST" action="{{ route('lms.questions.answer', [$course, $q]) }}" style="margin-top:10px;display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
            @csrf
            <textarea name="answer" class="form-control" rows="2" placeholder="Write your answer…" required style="flex:1;min-width:220px"></textarea>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-reply"></i> Answer</button>
          </form>
        @endif
      </div>
      @endforeach
    @endif
  </div>
</div>

@if($isTeacher || $isAdmin)
{{-- Submissions --}}
<div class="lms-pane" id="pane-submissions" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">All Student Submissions</div>
      <span class="tag">{{ $allSubmissions->count() }}</span>
    </div>
    @if($allSubmissions->isEmpty())
      <div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet.</p></div>
    @else
      <div class="table-wrap">
        <table>
          <thead><tr><th>Assignment</th><th>Student</th><th>Status</th><th>Submitted</th><th>File</th><th>Marks</th>@if($isTeacher)<th>Actions</th>@endif</tr></thead>
          <tbody>
            @foreach($course->assignments as $assignment)
              @foreach($assignment->submissions as $sub)
              <tr>
                <td style="font-weight:600">{{ $assignment->title }}</td>
                <td>{{ $sub->student->name ?? '—' }}</td>
                <td><span class="badge {{ $sub->status === 'graded' ? 'success' : ($sub->status === 'late' ? 'warning' : 'info') }}">{{ ucfirst($sub->status) }}</span></td>
                <td style="font-size:12.5px">{{ $sub->submitted_at?->format('d M Y, h:i A') ?? '—' }}</td>
                <td>@if($sub->file_path)<a href="{{ storage_asset($sub->file_path) }}" target="_blank"><i class="fas fa-file"></i></a>@else —@endif</td>
                <td>{{ $sub->marks_obtained !== null ? $sub->marks_obtained . '/' . $assignment->total_marks : '—' }}</td>
                @if($isTeacher)
                <td>
                  <a href="{{ route('lms.assignments.show', [$course, $assignment]) }}" class="btn btn-outline btn-sm"><i class="fas fa-star"></i> Grade</a>
                </td>
                @endif
              </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endif

{{-- Grades & Progress --}}
<div class="lms-pane" id="pane-grades" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">{{ $student ? 'Your Grades & Progress' : 'Grades & Overall Progress' }}</div>
      <span class="tag">{{ $enrolledCount }} student{{ $enrolledCount === 1 ? '' : 's' }}</span>
    </div>
    @if($course->enrollments->isEmpty())
      <div class="empty-state"><i class="fas fa-users"></i><p>No students enrolled yet.</p></div>
    @else
      <div class="table-wrap">
        <table>
          <thead><tr><th>Student</th><th>Status</th><th>Lesson Progress</th><th>Overall Grade</th></tr></thead>
          <tbody>
            @foreach($course->enrollments as $enrollment)
            @continue($student && $enrollment->student_id !== $student->id)
            @php $pct = $studentGrades[$enrollment->student_id] ?? null; @endphp
            <tr>
              <td>{{ $enrollment->student->name ?? '—' }}</td>
              <td><span class="badge {{ $enrollment->status === 'active' ? 'success' : 'gray' }}">{{ ucfirst($enrollment->status) }}</span></td>
              <td>
                <div style="min-width:100px">
                  <div class="progress" style="margin-bottom:3px"><div class="progress-bar success" style="width:{{ $enrollment->progress_percent ?? 0 }}%"></div></div>
                  <div style="font-size:11px;color:var(--text-muted)">{{ $enrollment->progress_percent ?? 0 }}%</div>
                </div>
              </td>
              <td>
                @if($pct === null)
                  <span style="color:var(--text-muted);font-size:12.5px">No graded work yet</span>
                @else
                  <div style="display:flex;align-items:center;gap:10px;max-width:220px">
                    <div class="progress" style="flex:1"><div class="progress-bar {{ $pct >= 50 ? 'success' : 'danger' }}" style="width:{{ $pct }}%"></div></div>
                    <span style="font-weight:700;font-size:12.5px">{{ $pct }}%</span>
                  </div>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

<script>
function lmsTab(name) {
  document.querySelectorAll('.lms-pane').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('pane-' + name).style.display = '';
  document.querySelector('.tab-btn[data-tab="' + name + '"]').classList.add('active');
}
function toggleMaterialFields() {
  const type = document.getElementById('materialType').value;
  document.getElementById('materialFileField').style.display = type === 'file' ? '' : 'none';
  document.getElementById('materialUrlField').style.display = type === 'link' ? '' : 'none';
}
</script>

@endsection
