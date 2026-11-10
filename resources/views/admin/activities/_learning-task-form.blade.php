<form method="POST" action="{{ route('admin.activities.'.$kind.'.store') }}" data-learning-request style="margin-bottom:22px">
@csrf
<input type="hidden" name="activity_tab" value="{{ $kind }}">
<div class="grid grid-3" style="gap:12px">
 <div><label class="text-sm text-muted">Teacher *</label><select name="teacher_id" required class="act-field" data-task-teacher>
  <option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" {{ old('teacher_id', $filterTeacherId) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>@endforeach
 </select></div>
 <div style="grid-column:span 2"><label class="text-sm text-muted">Subject / Program / Semester *</label><select name="course_id" required class="act-field" data-task-course>
  <option value="">Select a teacher and subject</option>
  @foreach($courses as $course)<option value="{{ $course->id }}" data-teacher="{{ $course->teacher_id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->subject->name ?? $course->title }} — {{ $course->schoolClass->display_name ?? 'General' }}</option>@endforeach
 </select></div>
 <div><label class="text-sm text-muted">Title *</label><input type="text" name="title" required maxlength="190" class="act-field" value="{{ old('title') }}"></div>
 <div><label class="text-sm text-muted">Due date</label><input type="datetime-local" name="due_date" class="act-field" value="{{ old('due_date') }}"></div>
 <div><label class="text-sm text-muted">Requested marks *</label><input type="number" name="total_marks" min="1" required class="act-field" value="{{ old('total_marks', 100) }}"></div>
 <div style="grid-column:span 3"><label class="text-sm text-muted">Instructions for the teacher</label><textarea name="instructions" rows="3" class="act-field" placeholder="Describe the learning task to prepare and publish to students.">{{ old('instructions') }}</textarea></div>
</div>
<p class="text-sm text-muted">The teacher prepares the content, selects all or specific enrolled students, and publishes it. Draft requests are not shown to students.</p>
<button type="submit" class="btn btn-primary btn-sm">Assign {{ $kind === 'quizzes' ? 'Quiz' : 'Assignment' }} to Teacher</button>
</form>
