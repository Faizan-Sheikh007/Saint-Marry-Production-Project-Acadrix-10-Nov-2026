<tr data-student-id="{{ $student->id }}" data-student-status="{{ $student->status }}" @if(!$student->isActiveEnrollment()) class="student-row-locked" style="background:rgba(137,12,37,.045)" @endif>
  {{-- SR # stays first; Student ID is the first identifying field. --}}
  <td style="white-space:nowrap;font-weight:800;color:var(--text-muted);text-align:center">
    {{ $sr ?? '—' }}
  </td>

  <td style="white-space:nowrap">
    <label class="no-print" style="display:inline-flex;align-items:center;gap:8px;margin:0;cursor:pointer">
      <input type="checkbox" class="student-select" @disabled(!$student->isActiveEnrollment()) value="{{ $student->id }}" data-name="{{ $student->name }}" aria-label="Select {{ $student->name }}" style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer">
    <span style="display:inline-flex;align-items:center;padding:5px 9px;border-radius:8px;background:var(--bg);border:1px solid var(--border);font-size:11.5px;font-weight:800;letter-spacing:.2px">
      {{ $student->student_id ?: '—' }}
    </span>
    </label>
  </td>

  {{-- Photo Column --}}
  <td>
    @if($student->photo)
      <img src="{{ storage_asset($student->photo) }}"
           alt="{{ $student->name }}"
           style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--primary)">
    @else
      <div class="avatar" style="background:var(--primary);width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff">
        {{ strtoupper(substr($student->name, 0, 2)) }}
      </div>
    @endif
  </td>

  <td>{{ $student->roll_number ?: '—' }}</td>
  {{-- Student Name + Email --}}
  <td>
    <div style="font-weight:600">{{ $student->name }}</div>
    <div style="font-size:11px;color:var(--text-muted)">{{ $student->email }}</div>
  </td>

  <td>{{ $student->father_name ?: ($student->guardian_name ?: '—') }}</td>
  {{-- Program / Semester / Part / Session — matches the Admission Form's academic fields --}}
  <td>
    <div style="font-weight:600">{{ $student->program->name ?? '—' }}</div>
    <div style="font-size:11px;color:var(--text-muted)">
      {{ $student->schoolClass->name ?? '—' }}
      @if($student->session)
        &middot; Session {{ $student->session }}
      @endif
    </div>
  </td>

  <td>{{ $student->phone }}</td>

  {{-- Pending Balance — outstanding across every unpaid/partial/overdue voucher --}}
  <td style="text-align:right">
    @php $pending = $student->pendingDues(); @endphp
    <span style="font-weight:700;color:{{ $pending > 0 ? 'var(--danger)' : 'var(--success)' }}">
      Rs.{{ number_format($pending, 0) }}
    </span>
  </td>

  <td>@include('partials.student-status-badge', ['statusStudent' => $student])
    @if(!$student->isActiveEnrollment())<div style="font-size:10px;margin-top:3px;color:var(--text-muted)">Profile disabled</div>@endif
  </td>

  <td>
    @if($student->currentFeeStatus() === 'paid')
      <span class="badge success">Paid</span>
    @elseif($student->currentFeeStatus() === 'partial')
      <span class="badge warning">Partial</span>
    @else
      <span class="badge danger">Unpaid</span>
    @endif
  </td>

  <td class="no-print">
    <div style="display:flex;gap:6px">
      <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm" title="View">
        <i class="fas fa-eye"></i>
      </a>
      @if($student->isActiveEnrollment())
      <a href="{{ route('students.edit', $student) }}" class="btn btn-outline btn-sm" title="Edit">
        <i class="fas fa-edit"></i>
      </a>
      <a href="{{ route('students.id-card', $student) }}" class="btn btn-outline btn-sm" title="ID Card">
        <i class="fas fa-id-card"></i>
      </a>

      @else
      <button type="button" class="btn btn-outline btn-sm" disabled title="Profile disabled — reactivate to edit"><i class="fas fa-edit"></i></button>
      <button type="button" class="btn btn-outline btn-sm" disabled title="Profile disabled — ID card unavailable"><i class="fas fa-id-card"></i></button>
      <form method="POST" action="{{ route('students.update-status', $student) }}" data-student-reactivation
            data-confirm="Reactivate {{ $student->name }}? Existing records and balances will be kept." data-confirm-title="Reactivate student" data-confirm-ok-text="Reactivate">
        @csrf<input type="hidden" name="status" value="active">
        <button type="submit" class="btn btn-primary btn-sm" title="Reactivate student"><i class="fas fa-play"></i> Reactivate</button>
      </form>
      @endif

      @if($student->isActiveEnrollment())
      {{-- More actions: Suspend / Reactivate / Terminate / Delete --}}
      <div style="position:relative;display:inline-block">
        <button type="button" class="btn btn-outline btn-sm action-menu-toggle" title="More">
          <i class="fas fa-ellipsis-vertical"></i>
        </button>
        <div class="action-menu-dropdown" style="display:none;position:fixed;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);z-index:9999;min-width:190px;overflow:hidden">

          @if(in_array($student->status, ['active', 'inactive']))
            <form action="{{ route('students.update-status', $student) }}" method="POST"
              data-confirm="{{ $student->name }} will be marked Suspended and paused from active academic activity."
              data-confirm-title="Suspend this student?"
              data-confirm-variant="warning"
              data-confirm-icon="fa-pause"
              data-confirm-ok-text="Yes, suspend">
              @csrf
              <input type="hidden" name="status" value="suspended">
              <button type="submit" style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;font-size:13px;font-weight:600;color:#b45309;cursor:pointer;display:flex;align-items:center;gap:8px">
                <i class="fas fa-pause" style="width:14px"></i> Suspend
              </button>
            </form>
          @else
            <form action="{{ route('students.update-status', $student) }}" method="POST"
              data-confirm="{{ $student->name }} will be marked Active again."
              data-confirm-title="Reactivate this student?"
              data-confirm-variant="primary"
              data-confirm-icon="fa-play"
              data-confirm-ok-text="Yes, reactivate">
              @csrf
              <input type="hidden" name="status" value="active">
              <button type="submit" style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;font-size:13px;font-weight:600;color:var(--success);cursor:pointer;display:flex;align-items:center;gap:8px">
                <i class="fas fa-play" style="width:14px"></i> Reactivate
              </button>
            </form>
          @endif

          @foreach(['dropped' => ['label'=>'Drop', 'icon'=>'fa-user-minus'], 'left'=>['label'=>'Left', 'icon'=>'fa-person-walking-arrow-right']] as $newStatus => $details)
            @if($student->status !== $newStatus)
              <form action="{{ route('students.update-status', $student) }}" method="POST"
                data-confirm="Mark {{ $student->name }} as {{ $details['label'] }}? The record and payment history will be retained."
                data-confirm-title="Mark student {{ $details['label'] }}?"
                data-confirm-variant="warning" data-confirm-ok-text="Confirm">
                @csrf
                <input type="hidden" name="status" value="{{ $newStatus }}">
                <button type="submit" style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;font-size:13px;font-weight:600;color:#9a3412;cursor:pointer">
                  <i class="fas {{ $details['icon'] }}" style="width:14px"></i> {{ $details['label'] }}
                </button>
              </form>
            @endif
          @endforeach

          @if($student->status !== 'terminated')
            <form action="{{ route('students.update-status', $student) }}" method="POST"
              data-confirm="{{ $student->name }} will be marked Terminated and treated as no longer enrolled. This can be reversed later from here if needed."
              data-confirm-title="Terminate this student?"
              data-confirm-variant="danger"
              data-confirm-icon="fa-ban"
              data-confirm-ok-text="Yes, terminate">
              @csrf
              <input type="hidden" name="status" value="terminated">
              <button type="submit" style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;font-size:13px;font-weight:600;color:var(--danger);cursor:pointer;display:flex;align-items:center;gap:8px;border-top:1px solid #f1f5f9">
                <i class="fas fa-ban" style="width:14px"></i> Terminate
              </button>
            </form>
          @endif

          <form action="{{ route('students.destroy', $student) }}" method="POST"
            data-confirm="This permanently removes {{ $student->name }} and their records. This can't be undone."
            data-confirm-title="Delete this student?"
            data-confirm-variant="danger"
            data-confirm-icon="fa-trash"
            data-confirm-ok-text="Yes, delete">
            @csrf @method('DELETE')
            <button type="submit" style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;font-size:13px;font-weight:600;color:var(--danger);cursor:pointer;display:flex;align-items:center;gap:8px;border-top:1px solid #f1f5f9">
              <i class="fas fa-trash" style="width:14px"></i> Delete
            </button>
          </form>

        </div>
      </div>
      @endif
    </div>
  </td>
</tr>
