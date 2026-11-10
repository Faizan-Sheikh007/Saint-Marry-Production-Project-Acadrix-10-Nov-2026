<div class="card" style="margin-bottom:20px;border-left:4px solid var(--primary)" role="status">
  <div class="card-body">
    @if($progression['is_complete'])
      <strong style="font-size:16px">No next semester fee plan is available.</strong>
      <div style="font-size:12.5px;color:var(--text-muted);margin-top:6px">Fee plans already extend through {{ $progression['cycle_label'] }} {{ $progression['last_covered_cycle'] }}. Review existing plans from the student profile.</div>
    @else
      <strong style="font-size:16px">You are creating a fee plan for {{ $progression['cycle_label'] }} {{ $progression['next_cycle'] }}@if($progression['total_cycles']) of {{ $progression['total_cycles'] }}@endif.</strong>
      <div style="font-size:12.5px;color:var(--text-muted);margin-top:6px">
        @if($progression['last_covered_cycle'] > 0)
          Plans already exist through {{ $progression['cycle_label'] }} {{ $progression['last_covered_cycle'] }}.
        @endif
        Only this semester/year will be created. Changing the fee template does not change the semester number. Previous plans and payments remain unchanged.
      </div>
      @if(old('cycle_no') && (int) old('cycle_no') !== $progression['next_cycle'])
        <div style="font-size:12px;margin-top:8px;color:var(--warning)">The previous form was out of date. The next available semester/year has been loaded. Review the amounts and select its due dates before saving.</div>
      @endif
      @if($progression['minimum_date'])
        <div style="font-size:12.5px;margin-top:8px;color:var(--primary)">
          <i class="fas fa-calendar-alt" aria-hidden="true"></i>
          Dates before <strong>{{ \Carbon\Carbon::parse($progression['minimum_date'])->format('d/m/Y') }}</strong> are unavailable.
          All months through {{ \Carbon\Carbon::parse($progression['last_due_date'])->format('F Y') }} are reserved for earlier fee plans.
        </div>
      @endif
      @if(($blockedDefaultDateCount ?? 0) > 0)
        <div style="font-size:12px;margin-top:8px;color:var(--warning)">{{ $blockedDefaultDateCount }} session-default date(s) fell in reserved months and were left blank. Choose replacement dates in the calendar; the saved session defaults were not changed.</div>
      @endif
    @endif
    @if(!empty($progression['duplicate_cycles']))
      <div style="font-size:12px;margin-top:12px;padding:10px 12px;border:1px solid var(--warning);border-radius:8px">
        <strong>Existing duplicate plans need review.</strong>
        @foreach($progression['duplicate_cycles'] as $cycle => $planIds)
          <div>{{ $progression['cycle_label'] }} {{ $cycle }}:
            @foreach($planIds as $planId)
              <a href="{{ route('fee-assignments.show', $planId) }}">Plan #{{ $planId }}</a>@unless($loop->last), @endunless
            @endforeach
          </div>
        @endforeach
        No existing plan has been deleted or renumbered. All existing plan dates are considered when reserving earlier months; review incorrect duplicates before continuing.
      </div>
    @endif
  </div>
</div>
