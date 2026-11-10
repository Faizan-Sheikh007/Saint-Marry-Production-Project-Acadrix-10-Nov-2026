<div class="ax-student-actions" aria-label="Actions for {{ $student->name }}">
  <a href="{{ route('vouchers.index', ['all' => 1, 'student_id' => $student->id, 'fee_state' => 'pending']) }}"
     class="ax-mini-action ax-mini-action-pending"
     title="View pending vouchers for {{ $student->name }}">
    <i class="fas fa-hourglass-half"></i>
    <span>Pending</span>
  </a>
  <a href="{{ route('vouchers.index', ['all' => 1, 'student_id' => $student->id, 'fee_state' => 'paid']) }}"
     class="ax-mini-action ax-mini-action-paid"
     title="View paid vouchers for {{ $student->name }}">
    <i class="fas fa-circle-check"></i>
    <span>Paid</span>
  </a>
  <a href="{{ route('accounts.students.show', $student) }}"
     class="ax-mini-action ax-mini-action-profile"
     title="View student profile">
    <i class="fas fa-user"></i>
    <span>Profile</span>
  </a>
</div>
