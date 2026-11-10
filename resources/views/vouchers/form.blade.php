@extends('layouts.app')
@section('title', 'New Voucher')
@section('page_title', 'Generate Voucher')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('vouchers.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>
@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
  </div>
@endif
<div class="card" style="max-width:600px">
  <div class="card-header"><div class="card-title">New Voucher</div></div>
  <div class="card-body">
    <form action="{{ route('vouchers.store') }}" method="POST">
      @csrf
      <div class="form-group"><label class="form-label">Student *</label>
        <select name="student_id" class="form-control" required>
          <option value="">Select Student</option>
          @foreach($students as $s)
          <option value="{{ $s->id }}" {{ old('student_id', $student->id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->student_id }})</option>
          @endforeach
        </select></div>

      <div class="form-group"><label class="form-label">Fee Head *</label>
        <select name="fee_head_id" id="fee_head_id" class="form-control" required>
          <option value="">Select Fee Head</option>
          @foreach($feeHeads as $fh)
          <option value="{{ $fh->id }}" data-amount="{{ $fh->default_amount }}" data-category="{{ $fh->category }}" {{ old('fee_head_id') == $fh->id ? 'selected' : '' }}>{{ $fh->name }}</option>
          @endforeach
        </select></div>

      <div class="form-group"><label class="form-label">Title / Description</label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Defaults to fee head name"></div>

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group"><label class="form-label">Amount *</label>
          <input type="number" step="1" min="0" id="amount" name="amount" class="form-control" required value="{{ old('amount') }}"></div>
        <div class="form-group"><label class="form-label">Discount</label>
          <input type="number" step="1" min="0" name="discount_amount" class="form-control" value="{{ old('discount_amount', 0) }}"></div>
      </div>

      <div class="form-group"><label class="form-label">Due Date *</label>
        <input type="date" name="due_date" class="form-control" required value="{{ old('due_date', now()->addDays(10)->format('Y-m-d')) }}"></div>

      <div class="form-group"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>

      <div id="attendance-hold-notice" style="display:none;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:12.5px;margin-bottom:14px">
        <i class="fas fa-triangle-exclamation"></i> This student may be under the Attendance-linked Fee Hold for exam fees.
        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;cursor:pointer">
          <input type="checkbox" name="override_attendance_hold" value="1"> Override and issue anyway
        </label>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="{{ route('vouchers.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Generate Voucher</button>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
document.getElementById('fee_head_id').addEventListener('change', function () {
  const opt = this.selectedOptions[0];
  if (opt && opt.dataset.amount) document.getElementById('amount').value = opt.dataset.amount;
  document.getElementById('attendance-hold-notice').style.display = (opt && opt.dataset.category === 'exam') ? 'block' : 'none';
});
</script>
@endpush
@endsection
