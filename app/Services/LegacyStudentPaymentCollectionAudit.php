<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Original collection amounts saved by the legacy Accounts > Student >
 * Receive Payment action. These are evidence of the original action, NOT
 * additional money to add to verified Payment / credit-ledger totals.
 *
 * Old Payment rows can be split across voucher numbers with no shared
 * receipt ID. Parsing the explicitly written audit amount is safer than
 * trying to reconstruct those batches from approximate timestamps.
 */
final class LegacyStudentPaymentCollectionAudit
{
    /** @return array{amount:float,student_id:string,voucher_numbers:array<int,string>}|null */
    public static function parse(string $description): ?array
    {
        if (! preg_match(
            '/\AReceived payment of ([1-9][0-9]*(?:\.[0-9]{1,2})?) for (.+) \(([^()]+)\), applied to voucher\(s\): (.+)\z/u',
            $description,
            $parts
        )) {
            return null;
        }

        $vouchers = array_values(array_filter(array_map('trim', explode(',', $parts[4]))));
        if ($vouchers === []) return null;

        return [
            'amount' => round((float) $parts[1], 2),
            'student_id' => $parts[3],
            'voucher_numbers' => $vouchers,
        ];
    }

    /** @return Collection<int, object> */
    public static function forStudent(Student $student): Collection
    {
        $records = AuditLog::query()
            ->with('user')
            ->where('action', 'paid')
            ->where('subject_type', 'Payment')
            ->whereNull('subject_id') // The legacy student-level Receive Payment action.
            ->where('description', 'like', 'Received payment of % for %')
            // Narrow the search, then verify the exact student ID in parse().
            ->where('description', 'like', '%('.$student->student_id.'), applied to voucher(s):%')
            ->latest('id')
            ->get();

        return $records->map(function (AuditLog $record) use ($student) {
            $parsed = self::parse((string) $record->description);
            if (! $parsed || $parsed['student_id'] !== (string) $student->student_id) {
                return null;
            }
            return (object) [
                'audit_id' => $record->id,
                'amount' => $parsed['amount'],
                'recorded_at' => $record->created_at,
                'recorded_by' => $record->user?->name,
                'voucher_numbers' => $parsed['voucher_numbers'],
            ];
        })->filter()->values();
    }
}
