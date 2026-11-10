@if(isset($statusStudent) && $statusStudent instanceof \App\Models\Student)
<div class="student-status-panel {{ $statusStudent->isActiveEnrollment() ? 'is-active' : 'is-locked' }}"
     @if(!$statusStudent->isActiveEnrollment()) data-profile-locked="{{ $statusStudent->id }}" @endif
     style="padding:13px 17px;margin-bottom:16px;border:1px solid {{ $statusStudent->isActiveEnrollment() ? '#86bca5' : '#dca2aa' }};border-left:5px solid {{ $statusStudent->isActiveEnrollment() ? '#19805d' : '#980f2b' }};border-radius:10px;background:var(--card);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div>
    <strong>Student status: {{ $statusStudent->statusLabel() }}</strong>
    @if(!$statusStudent->isActiveEnrollment())
      <span class="badge danger" style="margin-left:7px"><i class="fas fa-lock"></i> Profile Disabled</span>
      <div style="font-size:12px;margin-top:5px;color:var(--text-muted)">Read-only record. Student portal access and changes are disabled until an administrator reactivates this profile. Existing fees, credit and history are retained.</div>
    @endif
  </div>
  @if(!$statusStudent->isActiveEnrollment() && auth('web')->user()?->isAdmin())
    <form method="POST" action="{{ route('students.update-status', $statusStudent) }}" class="no-print" data-student-reactivation
          data-confirm="Reactivate this student? Existing records and balances will be kept." data-confirm-title="Reactivate student" data-confirm-ok-text="Reactivate" style="margin:0">
      @csrf<input type="hidden" name="status" value="active">
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-play"></i> Reactivate Student</button>
    </form>
  @endif
</div>
@endif
