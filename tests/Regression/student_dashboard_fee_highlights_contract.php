<?php
/**
 * Read-only static regression checks: no database or Composer needed.
 * From the Laravel root: php tests/Regression/student_dashboard_fee_highlights_contract.php
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/Student/StudentDashboardController.php');
$view = file_get_contents($root.'/resources/views/student/dashboard.blade.php');

$checks = [
    'Uses the authoritative active-plan voucher set' => str_contains($controller, '$student->authoritativeVouchers()'),
    'Uses the exact shared student finance ledger' => str_contains($controller, 'StudentFeeLedger::summarize($feeVouchers, now())'),
    'Paid/credited uses the same ledger paid total as My Fees' => str_contains($controller, '$paidCreditedFees = $feeTotals[\'paid\'];'),
    'Remaining balance uses the same ledger pending total as My Fees' => str_contains($controller, '$pendingFees = $feeTotals[\'pending\'];'),
    'Overdue badge uses the same ledger overdue total' => str_contains($controller, '$overdueFees = $feeTotals[\'overdue\'];'),
    'Paid balance passed to the Blade view' => str_contains($controller, '"paidCreditedFees", "overdueFees"'),
    'Unlinked student safely receives zero amounts' => str_contains($controller, '"paidCreditedFees"    => 0,')
        && str_contains($controller, '"overdueFees"         => 0,'),
    'Two financial cards use the highlighted header region' => str_contains($view, 'class="student-fee-highlights"')
        && str_contains($view, 'student-fee-highlight--paid')
        && str_contains($view, 'student-fee-highlight--pending'),
    'Correct paid and remaining labels' => str_contains($view, 'Paid / Credited</span>')
        && str_contains($view, 'Remaining Balance</span>'),
    'Amounts are formatted with two decimals' => str_contains($view, 'number_format($paidCreditedFees, 2)')
        && str_contains($view, 'number_format($pendingFees, 2)'),
    'Both cards link to the student-owned fee records' => substr_count($view, 'href="{{ route(\'student.fees\') }}"') >= 2,
    'Cards shown even when the fee is fully paid' => strpos($view, 'class="student-fee-highlights"') > strpos($view, '@endif\n      @endif'),
    'Welcome banner and navigation remain in place' => str_contains($view, 'welcome-banner student-dashboard-hero')
        && str_contains($view, 'My Fees, Vouchers &amp; Payment History'),
    'Student plan print uses the previously corrected renderer' => str_contains($controller, 'FeePlanSummary::class')
        && str_contains($controller, "return view('vouchers.receipt'"),
];

$failures = 0;
foreach ($checks as $label => $passed) {
    echo ($passed ? 'PASS' : 'FAIL').': '.$label."\n";
    if (!$passed) ++$failures;
}
if ($failures) exit(1);
echo 'All '.count($checks)." student-dashboard contract checks passed.\n";
