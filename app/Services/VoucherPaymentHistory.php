<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Collection;

/**
 * Read-only summaries of payments ALREADY stored against fee vouchers.
 *
 * One voucher number can have several fee-head rows and several payment
 * allocations. Summing verified Payment records across all sibling rows
 * prevents the history/receipt from displaying an arbitrary allocation.
 *
 * Balance-credit allocations count as fees PAID against the voucher, but
 * are not new cash receipts. Pending/rejected claims never count as paid.
 */
final class VoucherPaymentHistory
{
    public static function forStudent(Collection $vouchers, Collection $payments): Collection
    {
        $byVoucherNo = $vouchers->groupBy('voucher_no');

        return $payments
            ->filter(fn (Payment $p) => $p->voucher !== null && $byVoucherNo->has($p->voucher->voucher_no))
            ->groupBy(fn (Payment $p) => $p->voucher->voucher_no)
            ->map(function (Collection $entries, string $voucherNo) use ($byVoucherNo) {
                $verified = $entries->filter(fn (Payment $p) => $p->isVerified());
                $pending  = $entries->filter(fn (Payment $p) => $p->isPending());
                $rows     = $byVoucherNo->get($voucherNo);
                $recent   = ($verified->isNotEmpty() ? $verified : $entries)
                    ->sortByDesc(fn (Payment $p) => $p->paid_at?->timestamp ?? $p->created_at?->timestamp ?? 0)
                    ->first();

                $received = round((float) $verified->sum('amount'), 2);
                $cash     = round((float) $verified->reject(fn (Payment $p) => $p->method === 'balance_credit')->sum('amount'), 2);
                $credit   = round((float) $verified->filter(fn (Payment $p) => $p->method === 'balance_credit')->sum('amount'), 2);

                $status = $verified->isNotEmpty()
                    ? ($pending->isNotEmpty() ? 'Verified + Pending' : 'Verified')
                    : ($pending->isNotEmpty() ? 'Pending' : 'Rejected');

                $methods = ($verified->isNotEmpty() ? $verified : $entries)
                    ->pluck('method')->filter()->unique()->values()
                    ->map(fn ($method) => ucwords(str_replace('_', ' ', $method)))->implode(', ');

                $receivers = ($verified->isNotEmpty() ? $verified : $entries)
                    ->map(fn (Payment $p) => $p->received_by ?: ($p->submitted_by === 'student' ? 'Student (online)' : null))
                    ->filter()->unique()->values()->implode(', ');

                return (object) [
                    'voucher_no'      => $voucherNo,
                    'voucher'         => $rows->sortBy('id')->first(),
                    'voucher_amount'  => round((float) $rows->sum(fn (Voucher $v) => $v->total()), 2),
                    'received_amount' => $received,
                    'direct_amount'   => $cash,
                    'credit_amount'   => $credit,
                    'method'          => $methods ?: '—',
                    'status'          => $status,
                    'proof_paths'     => $entries->pluck('proof_path')->filter()->unique()->values(),
                    'received_by'     => $receivers ?: '—',
                    'paid_at'         => $recent?->paid_at,
                    'sort_at'         => $recent?->paid_at?->timestamp ?? $recent?->created_at?->timestamp ?? 0,
                ];
            })
            ->sortByDesc('sort_at')
            ->values();
    }

    /** Used by the printable voucher receipt so its figure matches history. */
    public static function verifiedAmountForVoucher(Voucher $voucher): float
    {
        return round((float) $voucher->combinedPayments()
            ->filter(fn (Payment $payment) => $payment->isVerified())
            ->sum('amount'), 2);
    }
}
