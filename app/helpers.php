<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('storage_asset')) {
    /**
     * Build a URL for a file on the "public" storage disk.
     *
     * This intentionally does NOT rely on the public/storage symlink
     * (php artisan storage:link). That symlink frequently fails or
     * silently breaks on Windows dev environments (XAMPP/Laragon) unless
     * PHP/Apache is run elevated or Developer Mode is enabled — which is
     * exactly why uploaded photos were 404-ing even though the files
     * existed on disk. Routing through the media.show controller instead
     * means photos work identically on Windows, Linux, and shared hosting,
     * with no server configuration required.
     */
    function storage_asset(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return route('media.show', ['path' => ltrim($path, '/')]);
    }
}

if (! function_exists('ordinal')) {
    /**
     * Turn an integer into its ordinal word form ("1st", "2nd", "3rd", "4th"...).
     * Used to label installments as "1st Installment", "2nd Installment", etc.
     */
    function ordinal(?int $number): string
    {
        $number = (int) $number;

        if ($number <= 0) {
            return (string) $number;
        }

        if (in_array($number % 100, [11, 12, 13], true)) {
            return $number . 'th';
        }

        return $number . match ($number % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}

if (! function_exists('semester_installment_label')) {
    /**
     * Standard "Semester X | Nth Installment" label used across vouchers,
     * installments, and receipts so every screen names installments the
     * same way instead of the older "Installment N of M" phrasing.
     */
    function semester_installment_label(?int $cycleNo, ?int $installmentNo, ?string $cycleWord = 'Semester'): string
    {
        $parts = [];

        if ($cycleNo) {
            $parts[] = trim($cycleWord) . ' ' . $cycleNo;
        }

        if ($installmentNo) {
            $parts[] = ordinal($installmentNo) . ' Installment';
        }

        return $parts ? implode(' | ', $parts) : '';
    }
}
