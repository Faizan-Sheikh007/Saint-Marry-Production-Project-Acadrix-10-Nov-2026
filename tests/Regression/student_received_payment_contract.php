<?php
/** Static regression assertions for the accountant receipt history screens. */
declare(strict_types=1);
$root = dirname(__DIR__, 2).'/';
$contents = fn (string $p): string => file_get_contents($root.$p) ?: '';
$assert = function(bool $condition, string $description): void {
    if (! $condition) throw new RuntimeException('FAIL: '.$description);
};

$view = $contents('resources/views/accounts/students-show.blade.php');
$controller = $contents('app/Http/Controllers/AccountsDirectoryController.php');
$routes = $contents('routes/web.php');
$paymentController = $contents('app/Http/Controllers/PaymentController.php');
$receiptController = $contents('app/Http/Controllers/ReceivedPaymentReceiptController.php');
$historyService = $contents('app/Services/StudentReceivedPaymentHistory.php');
$migration = $contents('database/migrations/2026_11_03_000001_add_receipt_batch_id_to_payments.php');
$assert(str_contains($view, 'Payment Received History — Individual Receipts'), 'history heading present');
$assert(str_contains($view, 'Original Receive Payment Entries — Historical Audit'), 'legacy collection audit heading present');
$assert(str_contains($view, 'id="received-payments"'), 'scroll destination present');
$assert(str_contains($view, 'No received payments recorded'), 'empty state present');
$assert(str_contains($controller, "'receivedPayments', 'receivedPaymentsTotal', 'originalPaymentActions'"), 'profile receives all receipt data');
$assert(str_contains($routes, "name('payments.received-receipt.show')"), 'reprint route named');
$assert(strpos($routes, "name('payments.received-receipt.show')") > strpos($routes, "auth:accounts"), 'reprint defined inside accounts routes');
$assert(substr_count($paymentController, "'receipt_batch_id' => \$receiptBatchId") === 2, 'both receive flows preserve one UUID per collection');
$assert(str_contains($historyService, "\$payment->method !== 'balance_credit'"), 'credit allocations excluded from received cash');
$assert(str_contains($historyService, "\$payment->isVerified()"), 'pending/rejected excluded from receipts');
$assert(str_contains($receiptController, "\$payment->voucher->student_id"), 'receipt checks student ownership');
$assert(str_contains($migration, "\$table->uuid('receipt_batch_id')"), 'batch ID persisted');
$assert(strpos($view, "@endif\n\n{{-- Payment receipts") !== false, 'history visible after fully paid');
echo "PASS: student payment-history screen, history audit, security and schema contracts\n";
