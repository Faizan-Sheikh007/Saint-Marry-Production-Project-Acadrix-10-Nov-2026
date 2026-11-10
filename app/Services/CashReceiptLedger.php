<?php

namespace App\Services;

use App\Models\{Payment, StudentBalanceTransaction};
use Illuminate\Support\Collection;

/** Money actually received. Applying existing credit is a settlement, not another receipt. */
class CashReceiptLedger
{
    public static function directPayments($from, $to, string $status = 'verified')
    {
        $query = Payment::where('method', '!=', 'balance_credit')->whereBetween('paid_at', [$from, $to]);
        if ($status === 'verified') $query->where(fn ($q) => $q->where('status', 'verified')->orWhereNull('status'));
        elseif ($status !== 'all') $query->where('status', $status);
        return $query;
    }

    public static function extraReceipts($from, $to)
    {
        return StudentBalanceTransaction::where('type', 'receipt')->whereBetween('paid_at', [$from, $to]);
    }

    public static function total($from, $to): float
    {
        return round((float) static::directPayments($from, $to)->sum('amount')
            + (float) static::extraReceipts($from, $to)->sum('amount'), 2);
    }

    /** Mixed read-only models: Payment (voucher->student), or balance receipt (student). */
    public static function entries($from, $to, string $status = 'verified'): Collection
    {
        $payments = static::directPayments($from, $to, $status)
            ->with('voucher.student.program', 'voucher.student.schoolClass', 'voucher.student.academicYear')->get()->toBase();
        if (in_array($status, ['verified', 'all'], true)) {
            $payments = $payments->concat(static::extraReceipts($from, $to)
                ->with('student.program', 'student.schoolClass', 'student.academicYear')->get());
        }
        return $payments->sortBy('paid_at')->values();
    }
}
