@extends('layouts.app')
@section('title', 'Fee Summary — ' . $student->name)
@section('page_title', 'Fee Summary')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">{{ $student->name }}</div>
    <div style="font-size:13px">Father Name: {{ $student->father_name ?: ($student->guardian_name ?: '—') }}</div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">
      {{ $student->student_id }} &middot; {{ $student->program->name ?? '—' }} &middot; {{ $student->academicYear->name ?? $student->session }}
    </div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="{{ route('students.message', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-envelope"></i> Message Parent/Student</a>
    <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Student</a>
  </div>
</div>

<div class="alert" style="background:rgba(59,130,246,0.08);border:1px solid var(--primary);color:var(--text-muted);margin-bottom:20px;font-size:12.5px">
  <i class="fas fa-circle-info"></i> Read-only view. Fee vouchers are recorded and received exclusively by the Accounts office —
  recording or reversing a payment is not available from the admin (EMS) side.
</div>

@include('partials.fee-summary', ['feeSummary' => $feeSummary, 'feeRouteName' => null])

{{-- Read-only, grouped financial history shared with the Student Profile. --}}
@include('partials.student-finance-history', [
  'feeLedger' => $feeLedger,
  'paymentHistory' => $paymentHistory,
  'financeShowTotals' => true,
  'financeShowPlans' => true,
  'financePlans' => $feeAssignments,
  'financeVoucherRoute' => 'vouchers.show',
  'financeReceiptRoute' => 'vouchers.receipt',
  'financeCreditTransactions' => $financeCreditTransactions,
  'financeAvailableCredit' => $financeAvailableCredit,
])

@endsection
