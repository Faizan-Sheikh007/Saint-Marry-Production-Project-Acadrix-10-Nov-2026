{{-- Account-wide credit: displayed once on the current semester, not attributed to that semester. --}}
@once
<style>
.student-credit-panel{margin-top:14px;padding:14px;border:1px solid #b8d9cc;border-radius:10px;background:#f2faf6;color:#173b30;min-width:0;font-size:12px;line-height:1.5}
.student-credit-panel *{box-sizing:border-box}
.student-credit-top{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.student-credit-label{font-weight:750;font-size:12px;line-height:1.4}
.student-credit-value{font-size:21px;font-weight:800;color:#086647;white-space:nowrap}
.student-credit-note{margin:7px 0 0;font-size:11px;color:#426757}
.student-credit-slips{margin-top:12px;padding-top:10px;border-top:1px dashed #b8d9cc}
.student-credit-slips h4{font-size:12px;margin:0 0 8px;color:#173b30}
.student-credit-slip{padding:8px 0;border-top:1px solid #dcebe3;min-width:0}
.student-credit-slip:first-of-type{border-top:0}
.student-credit-slip-head{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11px}
.student-credit-number{overflow-wrap:anywhere;font-weight:700}
.student-credit-date{font-size:10px;color:#526b60;margin-top:2px}
.student-credit-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;align-items:center}
.student-credit-actions a{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;min-height:32px;border:1px solid #b8d9cc;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;color:#086647;background:#fff}
.student-credit-actions a.credit-slip-download{background:#086647;border-color:#086647;color:#fff}
.student-credit-all{display:inline-block;font-size:11px;font-weight:700;margin-top:10px;color:#086647;text-decoration:underline}
.student-credit-empty{font-size:11px;color:#526b60;margin:0}
.student-credit-next{font-size:11px;margin-top:10px;padding:10px;border-radius:7px;background:#f2faf6;color:#426757;border:1px dashed #b8d9cc}
</style>
@endonce
<section class="student-credit-panel" data-student-credit-panel aria-label="Student credit and extra-payment slips">
  <div class="student-credit-top">
    <div class="student-credit-label"><i class="fas fa-wallet" aria-hidden="true"></i> Credited Amount<br><span style="font-weight:400">Available student credit</span></div>
    <strong class="student-credit-value">Rs. {{ number_format((float) $creditOverview['available'], 2) }}</strong>
  </div>
  @if($creditOverview['available_minor'] > 0)
    <p class="student-credit-note">Carried forward to future fees. Credit is deducted automatically when the next semester's vouchers are generated; any older unpaid vouchers are settled first.</p>
  @else
    <p class="student-credit-note">No unused credit remains. Original extra-payment slips stay available even after the credit has been used.</p>
  @endif
  <p class="student-credit-note">This is the student's shared credit balance, not a separate credit for each semester.</p>
  @if($creditOverview['available_minor'] > 0 && (float) ($studentPending ?? $outstandingTotal ?? 0) > 0)
    <form method="POST" action="{{ route('payments.apply-credit', $student) }}" style="margin-top:10px">
      @csrf
      <button type="submit" class="btn btn-outline btn-sm">Apply Existing Credit to Outstanding Vouchers</button>
    </form>
  @endif
  <div class="student-credit-slips">
    <h4><i class="fas fa-receipt" aria-hidden="true"></i> Extra-payment slips (without voucher)</h4>
    @forelse($creditOverview['receipts'] as $creditReceipt)
      <div class="student-credit-slip" data-extra-payment-receipt="{{ $creditReceipt['number'] }}">
        <div class="student-credit-slip-head"><span class="student-credit-number">{{ $creditReceipt['number'] }}</span><strong>Received Rs. {{ number_format((float) $creditReceipt['amount'], 2) }}</strong></div>
        <div class="student-credit-date">{{ $creditReceipt['date_label'] }}: {{ $creditReceipt['date'] }}{{ $creditReceipt['source'] === 'legacy' ? ' (historical entry)' : '' }}</div>
        <div class="student-credit-actions">
          <a class="credit-slip-download" target="_blank" rel="noopener" href="{{ route($creditReceipt['source'] === 'legacy' ? 'payments.extra-receipts.legacy' : 'payments.extra-receipts.show', [$student, $creditReceipt['id']]) }}"><i class="fas fa-print" aria-hidden="true"></i> Print All 3 Copies</a>
          <a target="_blank" rel="noopener" href="{{ route($creditReceipt['source'] === 'legacy' ? 'payments.extra-receipts.legacy-voucher' : 'payments.extra-receipts.voucher', [$student, $creditReceipt['id']]) }}">Print All 3 Copies</a>
        </div>
      </div>
    @empty
      <p class="student-credit-empty">No original extra-payment receipt found. Check receipt history or the original payment register. Do not enter the payment again to create a slip.</p>
    @endforelse
    <a class="student-credit-all" href="{{ route('payments.extra-receipts.index', $student) }}">View all extra-payment receipts</a>
    <p class="student-credit-date">Print or save either document as PDF from its print page.</p>
  </div>
</section>
