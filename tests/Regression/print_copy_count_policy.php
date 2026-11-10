<?php
/** Standalone source-contract checks for copy counts, no Laravel/DB required. */
declare(strict_types=1);
$root = dirname(__DIR__, 2).'/';
$read = fn (string $p): string => file_get_contents($root.$p) ?: '';
$assert = function (bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException('FAIL: '.$message); }
};
$challan = $read('resources/views/vouchers/partials/modern-challan.blade.php');
$plan = $read('resources/views/fee-assignments/plan.blade.php');
$summary = $read('resources/views/fee-assignments/summary.blade.php');
$extra = $read('resources/views/payments/partials/extra-payment-document.blade.php');
$paidVoucherReceipt = $read('resources/views/vouchers/receipt.blade.php');
$paidReceivedReceipt = $read('resources/views/payments/received-payment-receipt.blade.php');
foreach (['regular fee challan'=>$challan, 'installment plan'=>$plan, 'fee summary'=>$summary] as $name=>$view) {
    $assert(str_contains($view, "['Student Copy', 'Bank Copy', 'Office Copy']"), "$name: three-copy order");
}
$assert(str_contains($extra, "['student', 'bank', 'office']"), 'extra/advance payment prints three copies');
$assert(str_contains($extra, 'data-document-audience="student-bank-office"'), 'extra/advance receipt and voucher share three-copy sheet');
$assert(str_contains($extra, 'Do not pay this amount again.'), 'extra payment bank copy not mistaken for a due demand');
$assert(str_contains($summary, 'repeat(3, minmax(0, 1fr))'), 'summary three-column print grid');
$assert(str_contains($paidVoucherReceipt, "['Student Copy', 'Office Copy']"), 'paid voucher receipt remains two copies');
$assert(str_contains($paidVoucherReceipt, 'repeat(2, 1fr)'), 'paid voucher receipt remains two columns');
$assert(str_contains($paidReceivedReceipt, "['Student Copy', 'Office Copy']"), 'individual paid receipt prints two copies');
$assert(str_contains($paidReceivedReceipt, 'data-document-audience="student-office"'), 'individual paid receipt has no bank copy');
$assert(str_contains($paidReceivedReceipt, 'number_format($receipt->amount, 2)'), 'paid receipt prints exact collection, not cumulative voucher total');
$assert(str_contains($paidReceivedReceipt, 'Print 2 Copies / Save as PDF'), 'paid receipt print UI says two copies');
foreach (['resources/views/payments/extra-receipts.blade.php', 'resources/views/accounts/students-show.blade.php', 'resources/views/partials/student-credit.blade.php'] as $p) {
    $assert(str_contains($read($p), 'Print All 3 Copies'), "$p: extra/advance print link says three copies");
    $assert(!str_contains($read($p), 'Print 2 Copies'), "$p: no stale two-copy extra/advance action");
}
$assert(str_contains($read('app/Http/Controllers/ExtraPaymentReceiptController.php'), 'Use Print All 3 Copies / Save as PDF'), 'download notice matches three-copy sheet');
echo "PASS: three-copy voucher/extra/summary documents and two-copy paid receipts. No financial writes.\n";
