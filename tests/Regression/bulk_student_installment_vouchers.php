<?php
/**
 * Standalone offline regression for the Students bulk installment voucher change.
 * Run: php tests/Regression/bulk_student_installment_vouchers.php
 * Database/browser integration still needs a configured Laravel installation.
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/Services/BulkInstallmentVoucherSelector.php';

use App\Services\BulkInstallmentVoucherSelector as Selector;

$root = dirname(__DIR__, 2);
$get = static fn (string $path): string => file_get_contents($root . '/' . $path);
$asserts = 0;
$check = static function (bool $condition, string $message) use (&$asserts): void {
    ++$asserts;
    if (! $condition) throw new RuntimeException("FAIL #{$asserts}: {$message}");
};

// A 5-installment plan has the fifth part; a 4-installment plan does not.
$fourParts = [1, 2, 3, 4];
$fiveParts = [1, 2, 3, 4, 5];
$matches = static fn (array $parts, int $selected, int $total): int => count(array_filter($parts, static fn ($part) => Selector::slotInCycle(1, $part, $part, 1, 1, $total, 1) === $selected));
$check($matches($fourParts, 2, 4) === 1 && $matches($fiveParts, 2, 5) === 1, 'second part matches each saved plan');
$check($matches($fourParts, 5, 4) === 0 && $matches($fiveParts, 5, 5) === 1, 'fifth only exists in a five-part plan');
// The 5th installment is an example, NOT a maximum: 6th, 8th and later work too.
$eightParts = range(1, 8);
$twelveParts = range(1, 12);
$check($matches($eightParts, 8, 8) === 1, 'eighth installment is available for an eight-part plan');
$check($matches($fiveParts, 8, 5) === 0, 'five-part plans are skipped when eighth is selected');
$check($matches($twelveParts, 12, 12) === 1, 'twelfth installment is available for a twelve-part plan');
$check(Selector::slotInCycle(2, 1, 5, 1, 2, 4, 1) === null, 'never print semester 2 under semester 1');
$check(Selector::slotInCycle(2, 1, 5, 1, 2, 4, 2) === 1, 'semester 2 installment 1 derives from stored part_no');
$check(Selector::slotInCycle(2, null, 6, 1, 2, 4, 2) === 2, 'legacy cycle-aware plan wide installment 6 maps to semester 2 part 2');
$check(Selector::slotInCycle(null, null, 2, 1, 1, 4, 1) === 2, 'single-semester old plan can use saved installment number');
$check(Selector::slotInCycle(null, null, 2, 1, 2, 4, 1) === null, 'ambiguous old multi-cycle record must not be guessed');
$check(Selector::slotInCycle(1, 2, 2, 1, 1, 5, 3) === null, 'a cycle outside the fee plan never matches');

$routes = $get('routes/web.php');
$voucher = $get('resources/views/vouchers/index.blade.php');
$students = $get('resources/views/accounts/students-index.blade.php');
$dashboard = $get('resources/views/dashboard/index.blade.php');
$service = $get('app/Services/BulkInstallmentVoucherSelector.php');
$controller = $get('app/Http/Controllers/VoucherController.php');
$check(!str_contains($voucher, 'generateTemplateModal') && !str_contains($voucher, 'openBulkGenerateModal'), 'old voucher bulk generate modal and control removed');
$check(!str_contains($routes, "name('vouchers.generate-template')"), 'old arbitrary template bulk generation route no longer exposed');
$check(!str_contains($get('resources/views/vouchers/_browse-panel.blade.php'), 'openBulkGenerateModal'), 'old selected-student generation button removed');
$check(str_contains($students, "route('accounts.students.bulk-installments.print')") && str_contains($students, 'bulk-installment-number'), 'Students directory has dedicated installment print action');
$check(str_contains($students, 'student_count') && str_contains($students, 'voucher_count'), 'dropdown displays live eligible students and challans');
$check(str_contains($students, '(data.installments || []).forEach'), 'frontend renders all backend installment options, not a fixed count of five');
$check(str_contains($service, '$plans->max') && str_contains($service, '$maxSavedSlot'), 'installment maximum is derived from stored plans and saved vouchers');
$check(str_contains($dashboard, "route('accounts.students.index', ['bulk_installments' => 1])"), 'dashboard shortcut navigates to Students bulk modal');
$check(str_contains($service, "->where('status', '!=', 'cancelled')"), 'cancelled challans omitted');
$check(str_contains($service, "->whereIn('fee_assignment_id', " . '$owningPlans->pluck(\'id\')' . ")"), 'only saved authoritative fee-plan vouchers are selected');
$check(str_contains($service, '->unique(fn (Voucher $v) => $v->student_id') && str_contains($service, '$v->voucher_no'), 'one printable challan per student + voucher number');
$check(str_contains($controller, 'abort_if($vouchers->isEmpty(), 404'), 'empty selections never print a random voucher');
$check(str_contains($controller, "return view('vouchers.bulk-print'"), 'existing official three-copy voucher layout reused');
$check(strpos($routes, "'/students/bulk-installments/options'") < strpos($routes, "'/students/{student}'"), 'static bulk route is registered before student catch-all');
$check(str_contains($service, '->where(\'school_class_id\', $scope[\'classId\'])'), 'exact Semester/Part enrollment is enforced');
echo "PASS: {$asserts} bulk installment voucher regression checks.\n";
