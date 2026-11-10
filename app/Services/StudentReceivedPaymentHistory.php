<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\StudentBalanceTransaction;
use Illuminate\Support\Collection;

/**
 * Actual money received, displayed by collection rather than by voucher.
 *
 * Direct verified payments can be allocated to several voucher rows; those
 * allocations all have the same receipt_batch_id for collections recorded
 * after this update. Older rows without a batch ID must stay separate rather
 * than being incorrectly combined using similar timestamps or references.
 *
 * Extra-payment receipts are a separate source of incoming money. Subsequent
 * applications of that credit to vouchers are NOT a second receipt.
 */
final class StudentReceivedPaymentHistory
{
    public static function forStudent(Collection $voucherPayments, Collection $extraReceipts): Collection
    {
        $direct = $voucherPayments
            ->filter(fn (Payment $payment) => $payment->voucher !== null
                && $payment->isVerified()
                && $payment->method !== 'balance_credit'
                && (float) $payment->amount > 0)
            ->groupBy(fn (Payment $payment) => $payment->receipt_batch_id
                ? 'batch:'.$payment->receipt_batch_id
                : 'payment:'.$payment->id)
            ->map(function (Collection $allocations) {
                /** @var Payment $first */
                $first = $allocations->sortBy('id')->first();

                return (object) [
                    'type' => 'voucher_payment',
                    'receipt_number' => 'PR-'.str_pad((string) $first->id, 8, '0', STR_PAD_LEFT),
                    'amount' => round((float) $allocations->sum('amount'), 2),
                    'paid_at' => $first->paid_at,
                    'recorded_at' => $first->created_at,
                    'method' => $first->method,
                    'reference_no' => $first->reference_no,
                    'received_by' => $first->received_by,
                    'notes' => $first->notes,
                    'voucher_numbers' => $allocations->pluck('voucher.voucher_no')->filter()->unique()->values(),
                    'allocations' => $allocations->sortBy('id')->values(),
                    'proof_paths' => $allocations->pluck('proof_path')->filter()->unique()->values(),
                    'payment_id' => $first->id,
                    'extra_receipt_id' => null,
                ];
            })->values();

        $credit = $extraReceipts
            ->filter(fn (StudentBalanceTransaction $entry) => $entry->type === 'receipt' && (float) $entry->amount > 0)
            ->map(fn (StudentBalanceTransaction $entry) => (object) [
                'type' => 'extra_receipt',
                'receipt_number' => 'EPR-'.str_pad((string) $entry->id, 8, '0', STR_PAD_LEFT),
                'amount' => round((float) $entry->amount, 2),
                'paid_at' => $entry->paid_at,
                'recorded_at' => $entry->created_at,
                'method' => $entry->method,
                'reference_no' => $entry->reference_no,
                'received_by' => $entry->recorded_by,
                'notes' => $entry->notes,
                'voucher_numbers' => collect(),
                'allocations' => collect(),
                'proof_paths' => collect(),
                'payment_id' => null,
                'extra_receipt_id' => $entry->id,
            ])->values();

        return $direct->concat($credit)
            ->sortByDesc(fn ($receipt) => sprintf('%012d-%012d',
                $receipt->paid_at?->timestamp ?? $receipt->recorded_at?->timestamp ?? 0,
                $receipt->recorded_at?->timestamp ?? 0
            ))->values();
    }
}
