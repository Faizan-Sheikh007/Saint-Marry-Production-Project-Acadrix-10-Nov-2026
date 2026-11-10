@extends('layouts.app')
@section('title', 'Extra Payment Receipts')
@section('content')
<div style="display:flex;gap:14px;justify-content:space-between;align-items:center;flex-wrap:wrap;margin-bottom:20px">
  <div><h2>Extra Payment Receipts</h2><p>{{ $student->name }} &middot; {{ $student->student_id }}</p></div>
  <a href="{{ route('accounts.students.show', $student) }}" class="btn btn-outline">Back to Student Account</a>
</div>
<div class="card" style="margin-bottom:20px"><div class="card-body">
  Reprinting does not add money, deduct credit, or change vouchers. Applying existing credit is not a new payment.
  Select the original payment below. Either print action opens the same sheet with <strong>Student Copy, Bank Copy and Office Copy</strong> together on <strong>one A4 landscape page</strong>, using the existing voucher design. Print once and separate the three copies. Neither is a new fee demand.
</div></div>
<div class="card" style="margin-bottom:20px"><div class="card-header"><h3>Extra Payments Recorded in the Credit Ledger</h3></div>
<div class="card-body" style="overflow-x:auto"><table class="table">
  <thead><tr><th>Receipt</th><th>Date received</th><th>Amount</th><th>Method / Reference</th><th style="min-width:330px">Print Documents</th></tr></thead>
  <tbody>@forelse($receipts as $entry)
    <tr><td>EPR-{{ str_pad((string) $entry->id, 8, '0', STR_PAD_LEFT) }}</td>
      <td>{{ $entry->paid_at?->format('d M Y') ?? 'Not recorded' }}</td>
      <td>Rs. {{ number_format($entry->amount, 2) }}</td>
      <td>{{ ucwords(str_replace('_', ' ', $entry->method ?? 'Not recorded')) }} @if($entry->reference_no)<br>{{ $entry->reference_no }}@endif</td>
      <td><div class="extra-payment-print-actions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <a class="btn btn-primary btn-sm" target="_blank" rel="noopener" href="{{ route('payments.extra-receipts.show', [$student, $entry]) }}" title="Print student, bank and office copies together on one page">Print All 3 Copies</a>
        <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="{{ route('payments.extra-receipts.voucher', [$student, $entry]) }}" title="Print student, bank and office copies on one page">Print All 3 Copies</a>
      </div></td></tr>
  @empty<tr><td colspan="5">No new-ledger extra-payment receipts. Check the historical receipts below for earlier payments.</td></tr>@endforelse</tbody>
</table>{{ $receipts->links() }}</div></div>
<div class="card"><div class="card-header"><h3>Historical Extra Payments (Before the Credit Ledger)</h3></div>
<div class="card-body" style="overflow-x:auto">
  <p class="text-sm text-muted">Reproduced from the original payment audit entries, including earlier payments already applied to vouchers. The old flow did not save the payment method or selected payment date. The audit entry date below is not a confirmed payment date.</p>
  <table class="table"><thead><tr><th>Receipt</th><th>Audit entry recorded</th><th>Original amount</th><th>Reference</th><th style="min-width:330px">Print Documents</th></tr></thead>
  <tbody>@forelse($legacyReceipts as $item)
    <tr><td>EPR-L-{{ str_pad((string) $item['record']->id, 8, '0', STR_PAD_LEFT) }}</td>
      <td>{{ $item['record']->created_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</td>
      <td>Rs. {{ number_format((float) $item['data']['amount'], 2) }}</td><td>{{ $item['data']['reference_no'] ?? 'Not recorded' }}</td>
      <td><div class="extra-payment-print-actions" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <a class="btn btn-primary btn-sm" target="_blank" rel="noopener" href="{{ route('payments.extra-receipts.legacy', [$student, $item['record']]) }}" title="Print student, bank and office copies of the original historical payment on one page">Print All 3 Copies</a>
        <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="{{ route('payments.extra-receipts.legacy-voucher', [$student, $item['record']]) }}" title="Print all three historical copies on one page">Print All 3 Copies</a>
      </div></td></tr>
  @empty<tr><td colspan="5">No matching historical extra-payment audit entries found. Do not enter the payment again just to obtain a receipt. Accounts must verify the original register or bank record.</td></tr>@endforelse</tbody>
  </table>{{ $legacyReceipts->links() }}
</div></div>
@endsection
