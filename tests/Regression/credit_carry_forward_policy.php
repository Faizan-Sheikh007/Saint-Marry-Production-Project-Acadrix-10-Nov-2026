<?php
/** Isolated policy/render/source checks. Not a database, HTTP, or Laravel integration test. */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
foreach (['CreditAllocator', 'StudentCreditOverview', 'RupeeWords', 'SimplePdfDocument', 'ExtraPaymentSlipPdf'] as $service) {
    require $root.'/app/Services/'.$service.'.php';
}
use App\Services\{CreditAllocator as Credit, StudentCreditOverview as Overview, RupeeWords, ExtraPaymentSlipPdf};
$count = 0;
function check(bool $condition, string $label): void {
    global $count; $count++;
    if (!$condition) throw new RuntimeException('FAIL: '.$label);
}
function amount(string|int|float $value): int { return Credit::minor($value); }
function settle(int $credit, array $balances): array {
    $rows = [];
    foreach ($balances as $id => $balance) $rows[] = ['id' => (int) $id, 'balance' => amount($balance)];
    return Credit::allocate($credit, $rows);
}
$first = settle(amount(60000), [2 => 28000, 3 => 15000, 4 => 15000]);
check($first['applied'] === amount(58000) && $first['remaining'] === amount(2000), 'surplus retained after settling all current dues');
$waiting = settle($first['remaining'], []);
check($waiting['remaining'] === amount(2000) && $waiting['applied'] === 0, 'no future vouchers: retained credit does not disappear');
$next = settle($waiting['remaining'], [61 => 10000, 62 => 10000]);
check($next['allocations'] === [['id' => 61, 'amount' => amount(2000)]], 'next semester first installment receives carried credit');
check(amount(20000) - $next['applied'] === amount(18000), 'next semester outstanding reduced by carried credit once');
check(settle($next['remaining'], [61 => 8000, 62 => 10000])['applied'] === 0, 'observer retry or repeat request cannot reuse consumed credit');
$advance = settle(amount(50000), []);
check($advance['remaining'] === amount(50000), 'payment without any voucher remains fully available');
$later = settle($advance['remaining'], [1 => 5000, 2 => 5000]);
check($later['remaining'] === amount(40000), 'credit can exceed next semester dues');
check(settle($later['remaining'], [3 => 60000])['remaining'] === 0, 'remaining surplus continues into a further semester');
$oldest = settle(amount(12000), [5 => 3000, 6 => 20000]);
check($oldest['allocations'] === [['id' => 5, 'amount' => amount(3000)], ['id' => 6, 'amount' => amount(9000)]], 'older arrears take priority over next semester');
check(settle(amount('0.03'), [1 => '0.01', 2 => '0.05'])['allocations'][1]['amount'] === 2, 'credit uses exact minor currency units');
check(settle(amount(100), [1 => 0, 2 => 0])['remaining'] === amount(100), 'paid cards do not consume surplus');
foreach ([
    [5, [1, 5, 6], 8, 5], [5, [], 8, 5], [null, [1, 5, 6], 8, 6],
    [null, [], 8, 1], [99, [1, 5, 8, 12], 8, 8], [0, [0, -1], 8, 1], [null, [], 0, 1],
] as [$current, $assigned, $total, $expected]) {
    check(Overview::displayCycle($current, $assigned, $total) === $expected, 'card placement '.json_encode([$current, $assigned, $total]));
}
$receipt = [
    'number' => 'EPR-PREVIEW', 'source' => 'Layout fixture - not a live payment', 'legacy' => true,
    'amount' => '50000.00', 'student_name' => 'XYZ', 'student_id' => 'STU-2026-0740',
    'paid_at' => 'Not recorded in legacy entry', 'recorded_at' => 'Fixture only - not an issued receipt',
    'method' => 'Not recorded in legacy entry', 'reference_no' => 'Not recorded', 'received_by' => 'Not recorded',
    'notes' => 'DRAFT PREVIEW ONLY. No payment or credit was created by this test.',
    'words' => RupeeWords::forAmount('50000.00'),
];
$profile = ['program' => 'BS (Hons.) Human Nutrition and Dietetics', 'session' => '2024-2028', 'semester' => 'Semester 5'];
$institution = "SAINT MARY'S COLLEGE OF MEDICAL & ADVANCE STUDIES";
check(ExtraPaymentSlipPdf::supports($receipt, $profile, $institution), 'ASCII receipt is downloadable');
$pdf = (new ExtraPaymentSlipPdf())->render($receipt, $profile, $institution, '29 Oct 2026 - preview');
check(str_starts_with($pdf, '%PDF-1.4') && str_ends_with($pdf, '%%EOF'), 'PDF has header and trailer');
check(substr_count($pdf, '/Type /Page ') === 3, 'standard receipt has exactly three pages, one per copy');
check(substr_count($pdf, '(AMOUNT RECEIVED: Rs. 50,000.00)') === 3, 'all three copies print original received amount');
check(str_contains($pdf, 'Student Copy') && str_contains($pdf, 'Bank Copy') && str_contains($pdf, 'Office Copy'), 'three copy labels present');
check(!str_contains($pdf, '/JavaScript') && !str_contains($pdf, '/OpenAction'), 'PDF has no active actions');
$unicode = $receipt; $unicode['student_name'] = 'علی احمد';
check(!ExtraPaymentSlipPdf::supports($unicode, $profile, $institution), 'Unicode name uses browser print instead of corrupt PDF glyphs');
try { (new ExtraPaymentSlipPdf())->render($unicode, $profile, $institution, 'Preview'); check(false, 'unsafe font input rejected'); }
catch (InvalidArgumentException) { check(true, 'unsafe font input rejected'); }
$inject = $receipt; $inject['student_name'] = 'XYZ) /OpenAction (\\ test';
$escape = (new ExtraPaymentSlipPdf())->render($inject, $profile, $institution, 'Preview');
check(str_contains($escape, 'XYZ\\) /OpenAction \\(\\\\ test'), 'PDF string delimiters and backslashes escaped');
$controller = file_get_contents($root.'/app/Http/Controllers/ExtraPaymentReceiptController.php');
check(str_contains($controller, 'return $this->show($student, $transaction, true);'), 'download reuses ledger ownership/type checks');
check(str_contains($controller, 'return $this->legacy($student, $auditLog, true);'), 'legacy download reuses original-audit ownership checks');
check(!str_contains($controller, 'ExtraPaymentSlipPdf'), 'old unbranded PDF is not used by current print/download routes');
check(str_contains($controller, 'Use Print All 3 Copies / Save as PDF'), 'download action explains same-design browser PDF saving');
check(str_contains($controller, "->header('X-Content-Type-Options', 'nosniff')"), 'download prevents MIME sniffing');
foreach (['->save(', '->update(', '::create(', '->increment(', '->decrement(', '->delete('] as $write) {
    check(!str_contains($controller, $write), 'download controller has no data write '.$write);
    check(!str_contains(file_get_contents($root.'/app/Services/StudentCreditOverview.php'), $write), 'overview has no data write '.$write);
}
$assign = file_get_contents($root.'/app/Http/Controllers/FeeAssignmentController.php');
check(substr_count($assign, '$this->applyCreditToGeneratedSemester($student);') === 2, 'both initial and renewed plans apply credit');
foreach ([['public function store(', 'public function renew('], ['public function renewStore(', 'private function applyCreditToGeneratedSemester(']] as [$start, $end]) {
    $method = substr($assign, strpos($assign, $start), strpos($assign, $end) - strpos($assign, $start));
    check(strpos($method, '$this->applyCreditToGeneratedSemester($student);') > strpos($method, "->update(['total_amount' => \$totalAmount]);"), 'generation applies credit only after final plan totals');
    check(strpos($method, '$this->applyCreditToGeneratedSemester($student);') < strrpos($method, '        });'), 'generation applies credit before parent transaction commits');
}
check(str_contains($assign, 'StudentBalanceService::class)->applyAvailable($current, $actor)'), 'generation uses existing locked, idempotent allocator');
$observer = file_get_contents($root.'/app/Observers/VoucherObserver.php');
check(str_contains($observer, 'DB::afterCommit(') && str_contains($observer, '->applyAvailable($student)'), 'other voucher creators keep after-commit credit fallback');
$partial = file_get_contents($root.'/resources/views/partials/student-credit.blade.php');
check(str_contains($partial, 'Credited Amount') && str_contains($partial, "\$creditOverview['available']"), 'card displays authoritative available credit');
check(str_contains($partial, 'Print All 3 Copies'), 'card contains three-copy print action');
check(str_contains($partial, 'shared credit balance'), 'card does not misattribute student credit to every semester');
check(strpos($partial, "@forelse(\$creditOverview['receipts']") > strpos($partial, 'No unused credit remains'), 'receipts displayed independently of whether credit remains');
check(!str_contains($partial, '{!!'), 'receipt references use escaped Blade output');
foreach (['resources/views/vouchers/index.blade.php', 'resources/views/accounts/students-show.blade.php'] as $path) {
    $view = file_get_contents($root.'/'.$path);
    check(str_contains($view, "@include('partials.student-credit')"), 'shared panel included in '.$path);
    check(str_contains($view, "(int) \$cycle === \$creditOverview['display_cycle']"), 'only one card carries shared balance in '.$path);
}
$routes = file_get_contents($root.'/routes/web.php');
$group = strpos($routes, "Route::middleware(['auth:accounts', 'role:accountant'])");
foreach (['download', 'legacy-download'] as $name) check(strpos($routes, "name('payments.extra-receipts.$name')") > $group, 'PDF route declared after Accounts-only group start: '.$name);
if ($directory = getenv('ACADRIX_QA_OUTPUT')) {
    if (!is_dir($directory) && !mkdir($directory, 0770, true)) throw new RuntimeException('Cannot create QA output');
    file_put_contents($directory.'/extra-slip-preview.pdf', $pdf);
}
echo "PASS: $count isolated credit-carry-forward, PDF-renderer, and source-contract checks.\n";
echo "No actual Laravel middleware, database queries, migrations or concurrent requests executed.\n";
