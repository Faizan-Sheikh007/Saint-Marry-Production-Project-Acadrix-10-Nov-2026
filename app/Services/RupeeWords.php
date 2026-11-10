<?php

declare(strict_types=1);

namespace App\Services;

/** Integer-only money-to-words conversion; no optional PHP intl extension required. */
final class RupeeWords
{
    private const SMALL = [
        'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];
    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function forAmount(string|int $amount): string
    {
        $minor = CreditAllocator::minor($amount);
        $rupees = intdiv($minor, 100);
        $paisa = $minor % 100;
        return self::integer($rupees).' '.($rupees === 1 ? 'Rupee' : 'Rupees')
            .($paisa > 0 ? ' and '.self::integer($paisa).' '.($paisa === 1 ? 'Paisa' : 'Paise') : '')
            .' Only';
    }

    private static function integer(int $number): string
    {
        if ($number < 20) return self::SMALL[$number];
        if ($number < 100) return self::TENS[intdiv($number, 10)].($number % 10 ? ' '.self::SMALL[$number % 10] : '');
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $value => $label) {
            if ($number >= $value) {
                return self::integer(intdiv($number, $value)).' '.$label
                    .($number % $value ? ' '.self::integer($number % $value) : '');
            }
        }
        throw new \LogicException('Unreachable amount conversion.');
    }
}
