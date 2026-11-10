<tr>
  {{-- SR # stays first; Student ID is the first identifying field. --}}
  <td style="white-space:nowrap;font-weight:800;color:var(--text-muted);text-align:center">
    {{ $sr ?? '—' }}
  </td>

  <td style="white-space:nowrap">
    <span style="display:inline-flex;align-items:center;padding:5px 9px;border-radius:8px;background:var(--bg);border:1px solid var(--border);font-size:11.5px;font-weight:800;letter-spacing:.2px">
      {{ $student->student_id ?: '—' }}
    </span>
  </td>

  <td style="white-space:nowrap">{{ $student->ledger_number ?: '—' }}</td>
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

  {{-- Student Name + Email --}}
  <td>
    <div style="font-weight:600">{{ $student->name }}</div>
    <div style="font-size:11px;color:var(--text-muted)">{{ $student->email ?: '—' }}</div>
  </td>

  {{-- Program / Semester / Part / Session --}}
  <td>
    <div style="font-weight:600">{{ $student->program->name ?? '—' }}</div>
    <div style="font-size:11px;color:var(--text-muted)">
      {{ $student->schoolClass->section_label ?? ($student->schoolClass->name ?? '—') }}
      @if($student->session)
        &middot; Session {{ $student->session }}
      @endif
    </div>
  </td>

  <td>{{ $student->roll_number ?: '—' }}</td>
  <td>{{ $student->father_name ?: ($student->guardian_name ?: '—') }}</td>
  <td>{{ $student->phone }}</td>

  {{-- Pending Balance --}}
  <td style="text-align:right">
    @php $pending = $student->pendingDues(); @endphp
    <span style="font-weight:700;color:{{ $pending > 0 ? 'var(--danger)' : 'var(--success)' }}">
      Rs.{{ number_format($pending, 0) }}
    </span>
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

  <td>
    @include('partials.student-status-badge', ['statusStudent' => $student])
  </td>

  {{-- Actions — Accounts is read-only on the admission record itself, so
       this stops at "View" (full profile + fee summary + Ledger Number,
       which Accounts does own, is edited from there). No Edit/Delete/
       Status controls, unlike the Admin students list. --}}
  <td class="no-print">
    <div style="display:flex;gap:6px">
      <a href="{{ route('accounts.students.show', $student) }}" class="btn btn-outline btn-sm" title="View">
        <i class="fas fa-eye"></i> View
      </a>
    </div>
  </td>
</tr>
