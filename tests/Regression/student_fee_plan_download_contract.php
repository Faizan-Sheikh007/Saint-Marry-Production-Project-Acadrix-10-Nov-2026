<?php
/**
 * Run from the Laravel root: php tests/Regression/student_fee_plan_download_contract.php
 * This is a static regression contract; no database or vendor dependencies.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$read = static fn (string $file): string => (string) file_get_contents($root.'/'.$file);
$routes  = $read('routes/web.php');
$student = $read('app/Http/Controllers/Student/StudentDashboardController.php');
$parent  = $read('app/Http/Controllers/Guardian/GuardianDashboardController.php');
$accounts = $read('app/Http/Controllers/FeeAssignmentController.php');
$service = $read('app/Services/FeePlanSummary.php');
$summary = $read('resources/views/fee-assignments/summary.blade.php');
$finance = $read('resources/views/student/fees.blade.php');

$checks = [
    'student Download uses student.fee-plan' => str_contains($finance, "'financePlanRoute' => 'student.fee-plan'"),
    'student route stays student-only' => str_contains($routes, "Route::middleware(['auth', 'role:student'])->prefix('student')"),
    'parent route stays parent-only' => str_contains($routes, "Route::middleware(['auth', 'role:parent'])->prefix('parent')"),
    'student enforces ownership before rendering' => (bool) preg_match('/function feePlan\(FeeAssignment \$feeAssignment\).*?authorizeOwn\(\$feeAssignment->student_id\).*?FeePlanSummary::class/s', $student),
    'parent enforces child ownership before rendering' => (bool) preg_match('/function feePlan\(FeeAssignment \$feeAssignment\).*?authorizeChild\(\$feeAssignment->student\).*?FeePlanSummary::class/s', $parent),
    'student voucher view remains independent' => str_contains($student, "return view('vouchers.receipt'"),
    'parent voucher view remains independent' => str_contains($parent, "return view('vouchers.receipt'"),
    'accounts summary reuses the same renderer' => str_contains($accounts, 'FeePlanSummary::class)->render($feeAssignment, $cycle)'),
    'renderer uses complete fee summary rather than challans' => str_contains($service, "view('fee-assignments.summary'") && ! str_contains($service, "view('fee-assignments.plan'"),
    'summary lists installment and due dates' => str_contains($summary, 'Installment Plan') && str_contains($summary, "['due_date']") && str_contains($summary, "['payable']"),
];

foreach ($checks as $label => $passed) {
    if (!$passed) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "PASS: {$label}\n";
}
echo 'All '.count($checks)." fee-plan download contract checks passed.\n";
