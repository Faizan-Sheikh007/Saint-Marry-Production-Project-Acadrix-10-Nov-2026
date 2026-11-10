@extends('layouts.app')
@section('title', 'Student Fees')
@section('page_title', 'Student Fees')
@section('content')

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-receipt" style="color:var(--primary)"></i> My Students — Fee Status</div>
  </div>
  <div style="padding:0 16px 12px;font-size:13px;color:var(--text-muted)">
    Read-only view (Student Fee without Heads) — students with a previous balance are highlighted so you can remind them.
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Program</th>
          <th style="text-align:right">Previous Balance</th>
          <th style="text-align:center">Active Fee?</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($students as $student)
        <tr @if($student->fee_previous_balance_total > 0) style="background:rgba(239,68,68,0.06)" @endif>
          <td style="font-weight:600;font-size:13px">{{ $student->name }} @include('partials.student-status-badge', ['statusStudent' => $student])</td>
          <td>{{ $student->program->name ?? '—' }}</td>
          <td style="text-align:right">
            @if($student->fee_previous_balance_total > 0)
              <span style="color:var(--danger);font-weight:700">Rs.{{ number_format($student->fee_previous_balance_total) }}</span>
            @else
              <span style="color:var(--text-muted)">Rs.0</span>
            @endif
          </td>
          <td style="text-align:center">
            @if($student->fee_has_active)
              <span class="badge warning">Due Now</span>
            @else
              <span class="badge gray">—</span>
            @endif
          </td>
          <td style="text-align:center">
            <a href="{{ route('teacher.students.fee-detail', $student) }}" class="btn btn-sm btn-outline">
              <i class="fas fa-eye"></i> View
            </a>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">No students found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
