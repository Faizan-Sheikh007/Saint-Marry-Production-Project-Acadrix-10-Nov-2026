<?php
/**
 * Regression guard for Admin student installment statuses.
 * Run: php tests/Regression/admin_installment_status_contract.php
 * No database connection or Composer dependencies required.
 *
 * The first semester grouping must retain voucher numbers as keys; otherwise
 * the Admin view looks up 0/1/2/3 instead of actual voucher identifiers and
 * silently renders Paid and Partial installments as Unpaid.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$viewPath = $root.'/resources/views/students/show.blade.php';
$view = file_get_contents($viewPath);
if ($view === false) {
    throw new RuntimeException('Cannot read Admin student view.');
}

$checks = [
    'group semesters without resetting voucher keys' => str_contains(
        $view,
        '$voucherGroups->groupBy(fn ($lines) => $lines->first()->cycle_no, preserveKeys: true)'
    ),
    'Admin plan uses authoritative per-voucher ledger' => str_contains(
        $view, '\\App\\Services\\StudentFeeLedger::summarize($planVouchers, now())'
    ),
    'Admin ledger lookup retains original voucher number' => str_contains(
        $view, '$planLedgerByNo->get($voucherNo)'
    ),
    'Admin displays paid / partially paid / unpaid statuses from ledger' => str_contains(
        $view, '$groupPaymentState === \'Partial\' ? \'Partially Paid\' : $groupPaymentState'
    ),
];

foreach ($checks as $name => $passed) {
    if (! $passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS: {$name}\n");
}
