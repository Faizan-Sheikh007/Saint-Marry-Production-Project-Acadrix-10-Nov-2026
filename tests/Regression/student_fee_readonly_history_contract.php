<?php
/**
 * v5 regression: offline source contracts for fee history and student payment lockout.
 * This does NOT simulate Laravel middleware or production database data.
 * Run: php tests/Regression/student_fee_readonly_history_contract.php
 */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$read = static fn(string $file): string => file_get_contents($root.'/'.$file);
$summary = $read('resources/views/partials/fee-summary.blade.php');
$finance = $read('resources/views/partials/student-finance-history.blade.php');
$student = $read('resources/views/student/fees.blade.php');
$routes = $read('routes/web.php');
$controller = $read('app/Http/Controllers/Student/StudentDashboardController.php');
$accounts = $read('resources/views/accounts/students-show.blade.php');
$checks = 0;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    $checks++;
    if (!$condition) throw new RuntimeException("FAIL #{$checks}: {$label}");
};
$assert(str_contains($summary, '$fsHistoryRows = $fsGroupByInstallment($fsAll)'), 'history groups complete voucher from all fee-status buckets');
$assert(!str_contains($summary, '$fsPaidGrouped = $fsGroupByInstallment($feeSummary[\'previous_paid\']'), 'history no longer limited to 100%-paid bucket');
$assert(str_contains($summary, '$row->paid_amount > 0.005'), 'history includes voucher with some allocated payment');
$assert(str_contains($summary, '$row->balance_amount'), 'fee summary displays partial unpaid portion');
$assert(str_contains($summary, '$row->voucher_amount'), 'fee summary shows full voucher amount');
$assert(str_contains($summary, 'Partially Paid Installments'), 'fee history heading explicitly includes partial');
$assert(str_contains($finance, '$financePaidAndPartialRows'), 'student-facing fee-history table exists');
$assert(str_contains($finance, '$row->paid > 0.005'), 'student-facing partial history includes nonzero paid');
$assert(str_contains($finance, 'number_format($entry->paid, 2)') && str_contains($finance, 'number_format($entry->balance, 2)'), 'student-facing history displays paid and remaining');
$assert(str_contains($finance, 'Complete Payment History — By Voucher'), 'verified payment/credit history preserved');
$assert(str_contains($student, "@include('partials.student-finance-history'"), 'student still sees full ledger');
$assert(str_contains($student, 'Only the Accounts Office can receive and record payments'), 'student informed fees are accounts-only');
$assert(!str_contains($student, '<form') && !str_contains($student, 'Upload Proof') && !str_contains($student, 'pay-online'), 'student fee page is read-only');
$assert(!str_contains($routes, "/vouchers/{voucher}/pay-online"), 'student payment POST route removed');
$assert(!str_contains($controller, 'function payOnline(') && !str_contains($controller, 'Payment::create('), 'student payment-write method removed');
$assert(str_contains($routes, "Route::middleware(['auth:accounts', 'role:accountant'])"), 'accountant-only guard preserved');
$assert(str_contains($routes, "Route::post('/vouchers/{voucher}/payments'") && str_contains($routes, "Route::post('/students/{student}/payments'"), 'accounts payment recording routes preserved');
$assert(str_contains($accounts, "route('payments.store-for-student'"), 'accounts receive payment form preserved');
echo "PASS: {$checks} v5 student payment read-only / fee history source checks.\n";
