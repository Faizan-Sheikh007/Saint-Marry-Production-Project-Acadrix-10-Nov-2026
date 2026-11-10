{{-- Current enrollment status, not a payment/attendance/result status. Safe in screen and print tables. --}}
@php
  $reportStatusValue = \App\Support\StudentStatus::normalize($statusStudent->status ?? null);
  $reportStatusLabel = \App\Support\StudentStatus::label($reportStatusValue);
  $reportStatusColor = match ($reportStatusValue) {
      'active' => '#166534',
      'left', 'dropped', 'terminated' => '#9f1239',
      'suspended' => '#92400e',
      default => '#334155',
  };
@endphp
<span class="report-student-status" data-student-status="{{ $reportStatusValue ?: 'unknown' }}"
      title="Current enrollment status when this report was generated"
      style="display:inline-block;font-size:0.9em;line-height:1.35;font-weight:400;white-space:normal;color:{{ $reportStatusColor }};border:1px solid currentColor;border-radius:3px;padding:1px 4px;margin-top:3px;break-inside:avoid">Student status: <strong>{{ $reportStatusLabel }}</strong></span>
