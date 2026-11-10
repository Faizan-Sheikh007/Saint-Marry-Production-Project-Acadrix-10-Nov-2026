{{--
    Renders the Action cell for one voucher row inside partials/fee-summary.
    Expects $voucher, and (inherited from the including view's scope)
    $feeRouteName, $showAccountantActions, $printRouteName.

    $voucher is the row's "representative" fee-head line — since rows here
    are grouped by installment (voucher_no), balance/pay/cancel all need to
    look at the whole group (Voucher::lineItems()), not just this one line,
    or a representative that happens to be already paid would hide the Pay
    /Cancel buttons even though sibling fee-head lines still owe money.
--}}
@php
    $feeRouteName          = $feeRouteName ?? null;
    $showAccountantActions = $showAccountantActions ?? false;
    $printRouteName        = $printRouteName ?? null;
    $fsaGroupBalance        = round($voucher->lineItems()->sum(fn ($v) => $v->balance()), 2);
@endphp

@if($showAccountantActions)
  <div class="fs-actions">
    @if($printRouteName)
      <a href="{{ route($printRouteName, $voucher) }}" target="_blank" class="fs-action-btn" title="View / Print Voucher">
        <i class="fas fa-print"></i>
      </a>
    @endif
    @if($fsaGroupBalance > 0)
      <button type="button" class="fs-action-btn fs-pay-btn"
        data-pay-url="{{ route('payments.store', $voucher) }}"
        data-voucher-no="{{ $voucher->voucher_no }}"
        data-balance="{{ $fsaGroupBalance }}"
        title="Receive payment for this voucher">
        <i class="fas fa-money-bill-wave"></i>
      </button>
      <form action="{{ route('vouchers.cancel', $voucher) }}" method="POST" style="display:inline"
            data-confirm="Cancel voucher {{ $voucher->voucher_no }}? This cannot be undone."
            data-confirm-title="Cancel this voucher?"
            data-confirm-variant="danger"
            data-confirm-ok-text="Yes, cancel it">
        @csrf
        <button type="submit" class="fs-action-btn fs-cancel-btn" title="Cancel this voucher">
          <i class="fas fa-ban"></i>
        </button>
      </form>
    @endif
  </div>
@elseif($feeRouteName)
  <a href="{{ route($feeRouteName, $voucher) }}" target="_blank" title="View voucher"><i class="fas fa-download"></i></a>
@else
  —
@endif
