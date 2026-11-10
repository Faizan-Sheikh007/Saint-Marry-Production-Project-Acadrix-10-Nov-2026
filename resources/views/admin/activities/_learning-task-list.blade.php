@forelse($tasks as $item)
<div style="padding:12px 0;border-top:1px solid var(--border)">
 <strong>{{ $item->title }}</strong> — {{ $item->teacher->name ?? 'Teacher unavailable' }}
 <span class="badge {{ $item->published_at ? 'success' : 'warning' }}">{{ $item->published_at && $item->status !== 'draft' ? ($item->status === 'closed' ? 'Closed — visible in history' : 'Published to students') : 'Awaiting preparation' }}</span>
 <div class="text-sm text-muted">{{ $item->course->subject->name ?? $item->course->title ?? 'Legacy request — teacher can link a subject' }} · {{ $item->due_date?->format('d M Y H:i') ?? 'No due date' }}</div>
 @if($item->course)<a class="btn btn-outline btn-xs" style="margin-top:8px" href="{{ route('lms.'.$kind.'.show', [$item->course, $item]) }}">View {{ $kind === 'quizzes' ? 'quiz' : 'assignment' }}</a>@endif
 @if($item->teacher_response)<div class="text-sm" style="margin-top:8px">{{ $item->teacher_response }}</div>@endif
</div>
@empty<p class="text-sm text-muted">No {{ $kind }} assigned to teachers yet.</p>@endforelse
