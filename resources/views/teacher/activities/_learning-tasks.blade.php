<div class="card">
@forelse($tasks as $item)
 <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
  <strong>{{ $item->title }}</strong>
  <span class="badge {{ $item->published_at ? 'success' : 'warning' }}">{{ $item->published_at && $item->status !== 'draft' ? ($item->status === 'closed' ? 'Closed — visible in history' : 'Published to students') : 'Preparation required' }}</span>
  <p class="text-sm text-muted">{{ $item->course->subject->name ?? $item->course->title ?? 'Choose a subject below' }} · {{ $item->due_date?->format('d M Y H:i') ?? 'No due date' }}</p>
  <p>{{ $kind === 'assignments' ? $item->instructions : $item->description }}</p>
  @if($item->course)
   <a class="btn btn-primary btn-sm" href="{{ route('lms.'.$kind.'.edit', [$item->course, $item]) }}">{{ $item->published_at ? 'Edit Student Task' : 'Prepare & Assign to Students' }}</a>
   <a class="btn btn-outline btn-sm" href="{{ route('lms.'.$kind.'.show', [$item->course, $item]) }}">View {{ $kind === 'quizzes' ? 'Attempts' : 'Submissions' }}</a>
  @else
   <form method="POST" action="{{ route('teacher.activities.'.$kind.'.link', $item) }}">@csrf
    <select name="course_id" class="form-control" required><option value="">Select your subject</option>
    @foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }} — {{ $course->schoolClass->display_name ?? '' }}</option>@endforeach</select>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Link Subject &amp; Prepare for Students</button>
   </form>
  @endif
  <details style="margin-top:14px"><summary>Optional response to Admin</summary>
   @if($item->teacher_response)<p>{{ $item->teacher_response }}</p>@else
   <form method="POST" action="{{ route('teacher.activities.'.$kind.'.submit', $item) }}">@csrf
    <textarea name="teacher_response" rows="3" class="act-textarea" placeholder="Progress note for Admin (does not publish to students)" required></textarea>
    <button class="btn btn-outline btn-sm">Send Response</button>
   </form>@endif
  </details>
 </div>
@empty<div class="empty-state"><p>No {{ $kind }} allocated to you by Admin.</p></div>@endforelse
</div>
