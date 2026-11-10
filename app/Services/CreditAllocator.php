<?php

declare(strict_types=1);

namespace App\Services;

/** Pure, integer-minor-unit allocation: callers supply authoritative rows in due-date order. */
final class CreditAllocator
{
    public static function minor(string|int|float $amount): int
    {
        $value = is_float($amount) ? number_format($amount, 2, '.', '') : (string) $amount;
        if (! preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw new \InvalidArgumentException('Amount must be a non-negative monetary value with at most two decimals.');
        }
        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function decimal(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $minor = abs($minor);
        return $sign.intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @param array<int, array{id:int,balance:int}> $rows */
    public static function allocate(int $available, array $rows): array
    {
        if ($available < 0) throw new \InvalidArgumentException('Credit cannot be negative.');
        $remaining = $available;
        $allocations = [];
        $seen = [];
        foreach ($rows as $row) {
            if (isset($seen[$row['id']])) throw new \InvalidArgumentException('Duplicate voucher row.');
            $seen[$row['id']] = true;
            if ($row['balance'] < 0) throw new \InvalidArgumentException('Balance cannot be negative.');
            $portion = min($row['balance'], $remaining);
            if ($portion > 0) $allocations[] = ['id' => $row['id'], 'amount' => $portion];
            $remaining -= $portion;
        }
        return ['allocations' => $allocations, 'applied' => $available - $remaining, 'remaining' => $remaining];
    }
}
