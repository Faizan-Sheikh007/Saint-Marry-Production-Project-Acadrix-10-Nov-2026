<?php
/** Isolated amount/parser and source-contract regression; not a database test. */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
require $root.'/app/Services/CreditAllocator.php';
require $root.'/app/Services/LegacyExtraPaymentReceipt.php';
require $root.'/app/Services/RupeeWords.php';
use App\Services\LegacyExtraPaymentReceipt as Legacy;
use App\Services\RupeeWords as Words;
$count = 0;
function check(bool $ok, string $label): void {
    global $count; $count++;
    if (! $ok) throw new RuntimeException('FAIL: '.$label);
}
$sample = 'Received extra payment of 50000 from XYZ (STU-2026-0740), added to student balance';
$parsed = Legacy::parse($sample);
check($parsed !== null, 'original Rs.50,000 audit format recognised');
check($parsed['amount'] === '50000.00', 'receipt amount is original Rs.50,000, not current credit');
check($parsed['student_name'] === 'XYZ', 'name from historical entry');
check($parsed['student_id'] === 'STU-2026-0740', 'student ID from historical entry');
check($parsed['reference_no'] === null, 'missing reference not invented');
check(!isset($parsed['paid_at']) && !isset($parsed['method']), 'legacy payment date and method not invented');
foreach (['BANK-123', 'TX[123]', 'a]b', 'name & <tag>', "Bank\nRef"] as $ref) {
    $row = Legacy::parse($sample.' [ref: '.$ref.']');
    check($row !== null && $row['reference_no'] === $ref, 'reference faithfully read: '.json_encode($ref));
}
check(Legacy::parse(str_replace('XYZ', 'Abdul (Ali) <script>alert(1)</script>', $sample))['student_name'] === 'Abdul (Ali) <script>alert(1)</script>', 'name retained for escaped rendering');
check(Legacy::parse(str_replace('XYZ', 'علی احمد', $sample))['student_name'] === 'علی احمد', 'Unicode name supported');
foreach ([
    '', 'Received extra payment of 50000',
    'Extra payment received: 50000.00; method cash',
    'Student credit applied to vouchers: 50000.00; available credit: 0.00',
    'prefix '.$sample, $sample.' extra suffix', $sample."\n",
    str_replace('50000', '0', $sample), str_replace('50000', '-50000', $sample),
    str_replace('50000', '5e4', $sample), str_replace('50000', '50,000', $sample),
    str_replace('50000', '50000.25', $sample), str_replace('50000', '10000000000', $sample),
    $sample.' [ref: '.str_repeat('x', 101).']',
] as $index => $bad) check(Legacy::parse($bad) === null, 'reject non-original receipt '.$index);
$words = [
    '0' => 'Zero Rupees Only', '1' => 'One Rupee Only', '1.01' => 'One Rupee and One Paisa Only',
    '0.50' => 'Zero Rupees and Fifty Paise Only', '21' => 'Twenty One Rupees Only',
    '99' => 'Ninety Nine Rupees Only', '100' => 'One Hundred Rupees Only',
    '101' => 'One Hundred One Rupees Only', '1000' => 'One Thousand Rupees Only',
    '28000' => 'Twenty Eight Thousand Rupees Only', '50000' => 'Fifty Thousand Rupees Only',
    '8000' => 'Eight Thousand Rupees Only', '100000' => 'One Lakh Rupees Only',
    '125000.75' => 'One Lakh Twenty Five Thousand Rupees and Seventy Five Paise Only',
    '10000000' => 'One Crore Rupees Only', '1000000000' => 'One Hundred Crore Rupees Only',
];
foreach ($words as $amount => $expected) check(Words::forAmount((string) $amount) === $expected, 'words: '.$amount);
foreach (['-1', '1e4', '50,000', '1.001', '10000000000', ''] as $bad) {
    try { Words::forAmount($bad); check(false, 'reject invalid words input'); }
    catch (InvalidArgumentException) { check(true, 'invalid words input rejected'); }
}
$controller = file_get_contents($root.'/app/Http/Controllers/ExtraPaymentReceiptController.php');
foreach (['->increment(', '->decrement(', '->save(', '->update(', '::create(', '->delete(', 'DB::', 'StudentBalanceService', 'Payment::'] as $write) {
    check(!str_contains($controller, $write), 'receipt controller has no financial write: '.$write);
}
check(str_contains($controller, '(int) $transaction->student_id === (int) $student->id'), 'ledger student ownership checked');
check(str_contains($controller, "\$transaction->type === 'receipt'"), 'allocations/reversals not printable as cash receipts');
check(str_contains($controller, "(float) \$transaction->amount > 0"), 'non-positive ledger amounts rejected');
check(str_contains($controller, "\$auditLog->subject_type === 'Student'"), 'historical subject type checked');
check(str_contains($controller, '(int) $auditLog->subject_id === (int) $student->id'), 'historical student ownership checked');
check(str_contains($controller, "\$auditLog->action === 'paid'"), 'historical payment action checked');
check(str_contains($controller, "'paid_at' => 'Not recorded in legacy entry'"), 'audit date not misrepresented as payment date');
check(str_contains($controller, "'method' => 'Not recorded in legacy entry'"), 'old method not guessed');
check(substr_count($controller, 'private, no-store, max-age=0') === 2, 'sensitive history and unified print/download responses not cached');
$balance = file_get_contents($root.'/app/Services/StudentBalanceService.php');
check(str_contains($balance, "'receipt_id' => (int) \$existing->id"), 'duplicate submit reuses original receipt ID');
check(str_contains($balance, "'receipt_id' => (int) \$receipt->id"), 'new payment returns persisted receipt ID');
$payment = file_get_contents($root.'/app/Http/Controllers/PaymentController.php');
check(str_contains($payment, "route('payments.extra-receipts.show', [\$student, \$result['receipt_id']])"), 'new payment redirects to exact receipt');
check(str_contains($payment, "return redirect()->route('payments.extra-receipts.index', \$student)"), 'applying existing credit opens original receipt history, not new receipt');
$routes = file_get_contents($root.'/routes/web.php');
$accountGroup = strpos($routes, "Route::middleware(['auth:accounts', 'role:accountant'])");
foreach (['index','show','legacy'] as $action) {
    check(strpos($routes, "name('payments.extra-receipts.$action')") > $accountGroup, 'receipt route in Accounts guard group: '.$action);
}
$view = file_get_contents($root.'/resources/views/payments/partials/extra-payment-document.blade.php');
check(!str_contains($view, '{!!'), 'all receipt values use escaped Blade output');
check(str_contains($view, 'Office Copy — Office Use Only') && str_contains($view, 'Student Copy') && str_contains($view, 'Bank Copy'), 'student, bank and office copies');
check(str_contains($view, 'size: A4 landscape'), 'single-page three-copy landscape print layout');
check(str_contains($view, 'window.print()'), 'print/PDF action');
check(!str_contains($view, 'Main Campus'), 'no campus line');
check(!str_contains($view, 'Authorized Signatory'), 'no blank signature blocks');
check(!str_contains($view, 'credit_balance'), 'receipt does not fall back to remaining credit');
check(!str_contains($view, 'combinedPaidAmount'), 'receipt is not cumulative voucher paid total');
check(str_contains($view, "number_format((float) \$receipt['amount'], 2)"), 'exact receipt amount used');
check(str_contains($view, 'Current student profile'), 'current profile fields explicitly labelled');
check(str_contains($view, 'does not certify that all student dues are cleared'), 'receipt not a full-settlement certificate');
$history = file_get_contents($root.'/resources/views/payments/extra-receipts.blade.php');
check(str_contains($history, 'Do not enter the payment again'), 'missing legacy record warning');
fwrite(STDOUT, "PASS: $count isolated amount/parser/source-contract assertions.\n");
fwrite(STDOUT, "No Laravel routes, migrations, authorization middleware, or database queries executed.\n");
