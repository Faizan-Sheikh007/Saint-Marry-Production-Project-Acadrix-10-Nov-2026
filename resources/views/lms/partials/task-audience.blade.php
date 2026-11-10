@php
  $targetIds = old('target_student_ids', $task->target_student_ids ?? []);
  $audience = old('audience', !empty($targetIds) ? 'selected' : 'all');
@endphp
<div class="card" style="margin:16px 0;max-width:760px"><div class="card-body">
  <label class="form-label">Assign to students</label>
  <select name="audience" class="form-control" id="taskAudience" onchange="document.getElementById('taskStudentTargets').hidden = this.value !== 'selected'">
    <option value="all" {{ $audience === 'all' ? 'selected' : '' }}>All actively enrolled students in this subject</option>
    <option value="selected" {{ $audience === 'selected' ? 'selected' : '' }}>Selected students only</option>
  </select>
  <div id="taskStudentTargets" {{ $audience !== 'selected' ? 'hidden' : '' }} style="margin-top:12px;max-height:240px;overflow:auto">
    @forelse($students as $recipient)
      <label style="display:block;padding:6px 0"><input type="checkbox" name="target_student_ids[]" value="{{ $recipient->id }}" {{ in_array($recipient->id, $targetIds ?? []) ? 'checked' : '' }}> {{ $recipient->name }} — {{ $recipient->student_id }}</label>
    @empty<p>No actively enrolled students in this subject.</p>@endforelse
  </div>
  <p class="text-sm text-muted" style="margin-top:10px">Choose Active to publish. Students will find this under their teacher, subject, and Quizzes &amp; Assignments.</p>
</div></div>
