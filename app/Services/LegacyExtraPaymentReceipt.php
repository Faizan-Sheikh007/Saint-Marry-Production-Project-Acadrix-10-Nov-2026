<?php

declare(strict_types=1);

namespace App\Services;

/** Read-only decoder for the EXACT message written by the pre-credit-ledger code. */
final class LegacyExtraPaymentReceipt
{
    /**
     * No guesses from today's balance, allocations, or a new ledger audit entry.
     * The old code did not persist the selected payment date or payment method.
     * @return array{amount:string, student_name:string, student_id:string, reference_no:?string}|null
     */
    public static function parse(string $description): ?array
    {
        $pattern = '/\AReceived extra payment of ([1-9][0-9]{0,9}) from (.+) \(([^\r\n()]+)\), added to student balance(?: \[ref: ([\s\S]{1,100})\])?\z/u';
        if (! preg_match($pattern, $description, $matches)) {
            return null;
        }
        return [
            'amount' => $matches[1].'.00',
            'student_name' => $matches[2],
            'student_id' => $matches[3],
            'reference_no' => $matches[4] ?? null,
        ];
    }
}
