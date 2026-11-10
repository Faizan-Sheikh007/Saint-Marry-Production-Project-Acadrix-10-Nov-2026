@extends('layouts.app')
@section('title','My Fees')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <div style="font-size:11px;opacity:.7;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Accounts</div>
    <h1 style="font-size:24px;font-weight:800;margin:6px 0"><i class="fas fa-receipt"></i> My Fees</h1>
    <p style="opacity:.8">Fee plans, paid and partially paid installments, vouchers and payment history for {{ $student->name }}. Only the Accounts Office can receive and record payments.</p>
  </div>
</div>

{{-- Detailed, read-only finance ledger and verified payment history. --}}
@include('partials.student-finance-history', [
  'feeLedger' => $feeLedger,
  'paymentHistory' => $paymentHistory,
  'financeShowTotals' => true,
  'financeShowPlans' => true,
  'financePlans' => $feeAssignments,
  'financeVoucherRoute' => 'student.voucher',
  'financeReceiptRoute' => 'student.voucher',
  'financePlanRoute' => 'student.fee-plan',
  'financeCreditTransactions' => $financeCreditTransactions,
  'financeAvailableCredit' => $financeAvailableCredit,
])

{{-- All paid / unpaid / overdue / upcoming vouchers are already grouped and
     filterable in the canonical ledger above. Do not repeat partial fee-head
     rows in a second summary with conflicting statuses. --}}


@endsection
