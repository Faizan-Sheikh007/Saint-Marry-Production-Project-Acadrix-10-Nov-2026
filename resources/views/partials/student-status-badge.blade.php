{{-- Shared enrollment badge; fee status is deliberately separate. --}}
@if(isset($statusStudent) && $statusStudent instanceof \App\Models\Student)
<span class="badge {{ $statusStudent->statusBadge() }} student-status-badge"
      data-student-status="{{ $statusStudent->status }}" style="white-space:nowrap"
      title="Student status: {{ $statusStudent->statusLabel() }}{{ $statusStudent->isActiveEnrollment() ? '' : ' — Profile disabled' }}">
  @if(!$statusStudent->isActiveEnrollment())<i class="fas fa-lock" aria-hidden="true"></i>@endif
  {{ $statusStudent->statusLabel() }}
</span>
@endif
