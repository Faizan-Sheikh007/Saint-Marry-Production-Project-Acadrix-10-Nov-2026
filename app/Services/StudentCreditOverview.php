<?php

namespace App\Services;

use App\Models\{AuditLog, Student};

/** Read-only account credit and original extra-payment receipts for a semester card. */
final class StudentCreditOverview
{
    public function forStudent(Student $student, array $cycleOwner, int $totalCycles): array
    {
        $student->loadMissing('schoolClass');
        $available = CreditAllocator::minor((string) ($student->credit_balance ?? '0'));
        $receipts = [];
        foreach ($student->balanceTransactions()->where('type', 'receipt')->where('amount', '>', 0)
            ->latest('id')->limit(3)->get() as $entry) {
            $receipts[] = [
                'source' => 'ledger', 'id' => (int) $entry->id,
                'number' => 'EPR-'.str_pad((string) $entry->id, 8, '0', STR_PAD_LEFT),
                'amount' => (string) $entry->amount,
                'date' => $entry->paid_at?->format('d M Y') ?? 'Not recorded',
                'date_label' => 'Received',
                'sort' => $entry->created_at?->getTimestamp() ?? 0,
            ];
        }
        // Old extra payments have no voucher or new-ledger receipt. Read their original audit entry;
        // never create a replacement payment or infer a receipt amount from remaining credit.
        $legacy = AuditLog::where('action', 'paid')->where('subject_type', 'Student')
            ->where('subject_id', $student->id)
            ->where('description', 'like', 'Received extra payment of %')
            ->latest('id')->limit(30)->get();
        foreach ($legacy as $entry) {
            $data = LegacyExtraPaymentReceipt::parse($entry->description);
            if ($data === null) continue;
            $receipts[] = [
                'source' => 'legacy', 'id' => (int) $entry->id,
                'number' => 'EPR-L-'.str_pad((string) $entry->id, 8, '0', STR_PAD_LEFT),
                'amount' => $data['amount'],
                'date' => $entry->created_at?->format('d M Y') ?? 'Not recorded',
                'date_label' => 'Entry recorded',
                'sort' => $entry->created_at?->getTimestamp() ?? 0,
            ];
        }
        usort($receipts, static fn (array $a, array $b) =>
            ($b['sort'] <=> $a['sort']) ?: strcmp($b['number'], $a['number']));

        return [
            'available_minor' => $available,
            'available' => CreditAllocator::decimal($available),
            'display_cycle' => self::displayCycle($student->schoolClass?->term_number, array_keys($cycleOwner), $totalCycles),
            'receipts' => array_slice($receipts, 0, 3),
        ];
    }

    /** Show the shared credit once, on the student's current card; never invent a receipt's semester. */
    public static function displayCycle(?int $current, array $assignedCycles, int $totalCycles): int
    {
        $totalCycles = max(1, $totalCycles);
        if ($current !== null && $current >= 1 && $current <= $totalCycles) return $current;
        $valid = array_filter(array_map('intval', $assignedCycles),
            static fn (int $cycle) => $cycle >= 1 && $cycle <= $totalCycles);
        return $valid ? max($valid) : 1;
    }
}
