@php
  $statusClass = static function ($status) {
      return match ($status) {
          'active'      => 'success',
          'suspended'   => 'warning',
          'inactive'    => 'gray',
          'graduated'   => 'info',
          'transferred' => 'purple',
          'dropped', 'terminated', 'left' => 'danger',
          default       => 'gray',
      };
  };
@endphp

<div class="ax-explorer-result-meta">
  <div>
    <strong>{{ number_format($students->count()) }}</strong>
    student{{ $students->count() === 1 ? '' : 's' }} found
  </div>
  @if($students->isNotEmpty())
    <span>All matching students are shown below</span>
  @endif
</div>

@if($viewMode === 'enrollment')
  <div class="ax-explorer-table-wrap table-wrap">
    <table class="ax-explorer-table ax-enrollment-table">
      <thead>
        <tr>
          <th class="ax-select-col">Select</th>
          <th>SR #</th>
          <th>Student ID</th>
          <th>Photo</th>
          <th>Student</th>
          <th>Program</th>
          <th>Session</th>
          <th>Semester / Part</th>
          <th>Shift</th>
          <th>Admission Date</th>
          <th>Last Fee Paid</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
          <tr data-student-row="{{ $student->id }}">
            <td class="ax-select-col"><input type="checkbox" class="ax-student-checkbox" value="{{ $student->id }}" aria-label="Select {{ $student->name }}"></td>
            <td class="ax-sr">{{ $loop->iteration }}</td>
            <td><span class="ax-id-chip">{{ $student->student_id ?: '—' }}</span></td>
            <td>
              @if($student->photo)
                <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" class="ax-student-photo">
              @else
                <span class="ax-student-photo ax-student-photo-fallback">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
              @endif
            </td>
            <td>
              <div class="ax-student-name">{{ $student->name }}</div>
              <div class="ax-student-sub">{{ $student->email ?: ($student->phone ?: 'No contact') }}</div>
            </td>
            <td><strong>{{ $student->program->name ?? '—' }}</strong></td>
            <td>{{ $student->academicYear->name ?? ($student->session ?: '—') }}</td>
            <td><span class="ax-soft-chip"><i class="fas fa-layer-group"></i>{{ $student->schoolClass->section_label ?? '—' }}</span></td>
            <td>{{ $student->shift ? ucfirst($student->shift) : '—' }}</td>
            <td>{{ $student->admission_date?->format('d M Y') ?? '—' }}</td>
            <td>
              @if($student->last_fee_paid_at)
                <div class="ax-last-paid"><i class="fas fa-calendar-check"></i><span><strong>{{ \Illuminate\Support\Carbon::parse($student->last_fee_paid_at)->format('d M Y') }}</strong><small>Last received fee</small></span></div>
              @else
                <span class="ax-last-paid-none"><i class="fas fa-minus-circle"></i> Not paid yet</span>
              @endif
            </td>
            <td>@include('partials.student-status-badge', ['statusStudent' => $student])</td>
            <td>@include('dashboard._student-explorer-actions', ['student' => $student])</td>
          </tr>
        @empty
          <tr><td colspan="13" class="ax-empty-cell"><i class="fas fa-user-graduate"></i><strong>No students found</strong><span>Try another Semester / Part filter or clear your search.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@elseif($viewMode === 'fees')
  <div class="ax-explorer-table-wrap table-wrap">
    <table class="ax-explorer-table ax-fees-table">
      <thead>
        <tr>
          <th class="ax-select-col">Select</th>
          <th>SR #</th>
          <th>Student ID</th>
          <th>Photo</th>
          <th>Student</th>
          <th>Program / Part</th>
          <th style="text-align:right">Total Paid</th>
          <th>Last Fee Paid</th>
          <th style="text-align:right">Pending Balance</th>
          <th style="text-align:right">Credit Balance</th>
          <th>Fees Status</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
          @php
            $pending = (float) $student->pendingDues();
            $totalPaid = (float) $student->totalPaid();
            $feeState = $pending <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');
          @endphp
          <tr data-student-row="{{ $student->id }}">
            <td class="ax-select-col"><input type="checkbox" class="ax-student-checkbox" value="{{ $student->id }}" aria-label="Select {{ $student->name }}"></td>
            <td class="ax-sr">{{ $loop->iteration }}</td>
            <td><span class="ax-id-chip">{{ $student->student_id ?: '—' }}</span></td>
            <td>
              @if($student->photo)
                <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" class="ax-student-photo">
              @else
                <span class="ax-student-photo ax-student-photo-fallback">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
              @endif
            </td>
            <td>
              <div class="ax-student-name">{{ $student->name }}</div>
              <div class="ax-student-sub">{{ $student->academicYear->name ?? ($student->session ?: '—') }}</div>
            </td>
            <td>
              <div class="ax-student-name ax-student-name-sm">{{ $student->program->name ?? '—' }}</div>
              <div class="ax-student-sub">{{ $student->schoolClass->section_label ?? '—' }}</div>
            </td>
            <td class="ax-money ax-money-paid">Rs. {{ number_format($totalPaid, 0) }}</td>
            <td>
              @if($student->last_fee_paid_at)
                <div class="ax-last-paid"><i class="fas fa-calendar-check"></i><span><strong>{{ \Illuminate\Support\Carbon::parse($student->last_fee_paid_at)->format('d M Y') }}</strong><small>Last received fee</small></span></div>
              @else
                <span class="ax-last-paid-none"><i class="fas fa-minus-circle"></i> Not paid yet</span>
              @endif
            </td>
            <td class="ax-money {{ $pending > 0 ? 'ax-money-due' : 'ax-money-clear' }}">Rs. {{ number_format($pending, 0) }}</td>
            <td class="ax-money">Rs. {{ number_format((float) ($student->credit_balance ?? 0), 0) }}</td>
            <td>
              <span class="badge {{ $feeState === 'paid' ? 'success' : ($feeState === 'partial' ? 'warning' : 'danger') }}">
                {{ ucfirst($feeState) }}
              </span>
            </td>
            <td>@include('partials.student-status-badge', ['statusStudent' => $student])</td>
            <td>@include('dashboard._student-explorer-actions', ['student' => $student])</td>
          </tr>
        @empty
          <tr><td colspan="13" class="ax-empty-cell"><i class="fas fa-wallet"></i><strong>No students found</strong><span>Try another Semester / Part filter or clear your search.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@else
  <div class="ax-explorer-table-wrap table-wrap">
    <table class="ax-explorer-table ax-student-details-table">
      <thead>
        <tr>
          <th class="ax-select-col">Select</th>
          <th>SR #</th>
          <th>Student ID</th>
          <th>Photo</th>
          <th>Student</th>
          <th>Program / Part</th>
          <th>Session</th>
          <th>Contact</th>
          <th style="text-align:right">Pending Balance</th>
          <th>Last Fee Paid</th>
          <th>Fees Status</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
          @php
            $pending = (float) $student->pendingDues();
            $totalPaid = (float) $student->totalPaid();
            $feeState = $pending <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');
          @endphp
          <tr data-student-row="{{ $student->id }}">
            <td class="ax-select-col"><input type="checkbox" class="ax-student-checkbox" value="{{ $student->id }}" aria-label="Select {{ $student->name }}"></td>
            <td class="ax-sr">{{ $loop->iteration }}</td>
            <td><span class="ax-id-chip">{{ $student->student_id ?: '—' }}</span></td>
            <td>
              @if($student->photo)
                <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" class="ax-student-photo">
              @else
                <span class="ax-student-photo ax-student-photo-fallback">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
              @endif
            </td>
            <td>
              <div class="ax-student-name">{{ $student->name }}</div>
              <div class="ax-student-sub">{{ $student->email ?: 'Student account' }}</div>
            </td>
            <td>
              <div class="ax-student-name ax-student-name-sm">{{ $student->program->name ?? '—' }}</div>
              <div class="ax-student-sub">{{ $student->schoolClass->section_label ?? '—' }}</div>
            </td>
            <td>{{ $student->academicYear->name ?? ($student->session ?: '—') }}</td>
            <td><div>{{ $student->phone ?: '—' }}</div></td>
            <td class="ax-money {{ $pending > 0 ? 'ax-money-due' : 'ax-money-clear' }}">Rs. {{ number_format($pending, 0) }}</td>
            <td>
              @if($student->last_fee_paid_at)
                <div class="ax-last-paid"><i class="fas fa-calendar-check"></i><span><strong>{{ \Illuminate\Support\Carbon::parse($student->last_fee_paid_at)->format('d M Y') }}</strong><small>Last received fee</small></span></div>
              @else
                <span class="ax-last-paid-none"><i class="fas fa-minus-circle"></i> Not paid yet</span>
              @endif
            </td>
            <td>
              <span class="badge {{ $feeState === 'paid' ? 'success' : ($feeState === 'partial' ? 'warning' : 'danger') }}">
                {{ ucfirst($feeState) }}
              </span>
            </td>
            <td>@include('partials.student-status-badge', ['statusStudent' => $student])</td>
            <td>@include('dashboard._student-explorer-actions', ['student' => $student])</td>
          </tr>
        @empty
          <tr><td colspan="13" class="ax-empty-cell"><i class="fas fa-user-graduate"></i><strong>No students found</strong><span>Try another Semester / Part filter or clear your search.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endif
