<?php
/**
 * Executes the real receipt controller against isolated model/response stubs.
 * NO Laravel bootstrap, database, middleware, model binding or live financial data.
 * Run with: php tests/Regression/paid_voucher_policy.php
 */
declare(strict_types=1);

namespace App\Http\Controllers { class Controller {} }
namespace App\Models {
    class FixtureRecord extends \stdClass {
        public function __construct(array $values) { foreach ($values as $key => $value) $this->$key = $value; }
        public function loadMissing(mixed $relations): static { return $this; }
        public function __call(string $name, array $args): never { throw new \LogicException('Unexpected model action: '.$name); }
    }
    class Student extends FixtureRecord {}
    class BankAccount { public static function active(): array { return []; } }
    class StudentBalanceTransaction extends FixtureRecord {}
    class AuditLog extends FixtureRecord {}
    class Setting {
        public static function get(string $key, mixed $default = null): mixed {
            return $key === 'institution_name' ? 'Fixture Institution' : $default;
        }
    }
}
namespace {
    use App\Http\Controllers\ExtraPaymentReceiptController;
    use App\Models\{Student, StudentBalanceTransaction, AuditLog};
    final class FixtureResponse {
        public array $headers = [];
        public function __construct(public ?string $view = null, public array $data = []) {}
        public function view(string $name, array $data): self { return new self($name, $data); }
        public function header(string $name, string $value): self { $this->headers[$name] = $value; return $this; }
    }
    function response(): FixtureResponse { return new FixtureResponse(); }
    function route(string $name, mixed $args): string {
        return '/fixture/'.$name.'/'.implode('/', array_map(fn($x) => (string)(is_object($x) ? $x->id : $x), is_array($args) ? $args : [$args]));
    }
    function now(): DateTimeImmutable { return new DateTimeImmutable('2026-10-29 14:05:00'); }
    function abort_unless(bool $valid, int $code): void { if (!$valid) throw new RuntimeException('Rejected', $code); }
    $root = dirname(__DIR__, 2);
    foreach (['CreditAllocator', 'LegacyExtraPaymentReceipt', 'RupeeWords', 'ExtraPaymentSlipPdf'] as $service) require $root.'/app/Services/'.$service.'.php';
    require $root.'/app/Http/Controllers/ExtraPaymentReceiptController.php';
    $count = 0;
    function check(bool $ok, string $label): void {
        global $count; $count++;
        if (!$ok) throw new RuntimeException('FAIL: '.$label);
    }
    function rejected(callable $action, string $label): void {
        try { $action(); } catch (RuntimeException $e) { check($e->getCode() === 404, $label); return; }
        check(false, $label);
    }
    $student = new Student([
        'id'=>700, 'student_id'=>'STU-2026-0700', 'name'=>'Fixture Student', 'ledger_number'=>'150',
        'user'=>null, 'credit_balance'=>'0.00', 'program'=>(object)['name'=>'Fixture Program'],
        'session'=>'2026-2030', 'academicYear'=>null, 'schoolClass'=>(object)['name'=>'Semester 1', 'section_label'=>'Semester 1'],
    ]);
    $tx = new StudentBalanceTransaction([
        'id'=>23, 'student_id'=>700, 'type'=>'receipt', 'amount'=>'5.00', 'balance_after'=>'5.00',
        'paid_at'=>new DateTimeImmutable('2026-10-29'), 'created_at'=>new DateTimeImmutable('2026-10-29 14:02:00'),
        'method'=>'cash', 'reference_no'=>null, 'recorded_by'=>'Fixture Cashier', 'notes'=>null,
    ]);
    $controller = new ExtraPaymentReceiptController();
    $before = serialize([$student, $tx]);
    $voucher = $controller->voucher($student, $tx);
    check($voucher->view === 'payments.extra-payment-voucher', 'paid voucher uses the existing-design receipt view');
    check($voucher->data['receipt']['amount'] === '5.00', 'exact Rs.5 received, not zero available credit');
    check($voucher->data['receipt']['words'] === 'Five Rupees Only', 'correct original amount in words');
    check($voucher->data['receipt']['number'] === 'EPR-00000023', 'stable original receipt identifier retained');
    check($voucher->data['receipt']['method'] === 'Cash', 'original method retained');
    check($voucher->data['receipt']['paid_at'] === '29 Oct 2026', 'original payment date retained');
    check($voucher->data['receipt']['receipt_url'] === '/fixture/payments.extra-receipts.show/700/23', 'back link scoped to original receipt');
    check(($voucher->headers['Cache-Control'] ?? '') === 'private, no-store, max-age=0', 'paid voucher private and non-cacheable');
    check(serialize([$student, $tx]) === $before, 'voucher printing makes no state changes in isolated models');
    $receipt = $controller->show($student, $tx);
    check($receipt->view === 'payments.extra-receipt', 'student receipt uses audience-specific wrapper');
    check($receipt->data['receipt'] === $voucher->data['receipt'], 'same persisted data in receipt and voucher');
    check($controller->voucher($student, $tx)->data['receipt'] === $voucher->data['receipt'], 'reprints keep amount and reference');
    $student->credit_balance = '12345.67';
    check($controller->voucher($student, $tx)->data['receipt']['amount'] === '5.00', 'other available credit cannot change voucher amount');
    foreach (['0.01', '5.25', '50000.00', '9999999999.99'] as $amount) {
        $copy = clone $tx; $copy->amount = $amount;
        check($controller->voucher($student, $copy)->data['receipt']['amount'] === $amount, 'decimal original amount preserved: '.$amount);
    }
    foreach ([['student_id'=>999],['type'=>'allocation'],['type'=>'reversal'],['amount'=>'0.00'],['amount'=>'-5.00']] as $bad) {
        $copy = clone $tx; foreach ($bad as $key=>$value) $copy->$key=$value;
        rejected(fn()=>$controller->voucher($student,$copy), 'invalid ledger row rejected: '.json_encode($bad));
    }
    $audit = new AuditLog([
        'id'=>88, 'action'=>'paid', 'subject_type'=>'Student', 'subject_id'=>700,
        'description'=>'Received extra payment of 50000 from Original Name (STU-2026-0700), added to student balance [ref: BANK-REF]',
        'created_at'=>new DateTimeImmutable('2026-10-29 14:02:00'), 'user'=>null,
    ]);
    $historical=$controller->legacyVoucher($student,$audit);
    check($historical->view === 'payments.extra-payment-voucher', 'legacy paid voucher uses same layout');
    check($historical->data['receipt']['number'] === 'EPR-L-00000088', 'historical stable reference retained');
    check($historical->data['receipt']['amount'] === '50000.00', 'historical original amount retained');
    check($historical->data['receipt']['student_name'] === 'Original Name', 'historical name retained');
    check($historical->data['receipt']['paid_at'] === 'Not recorded in legacy entry', 'legacy payment date not inferred from audit date');
    check($historical->data['receipt']['method'] === 'Not recorded in legacy entry', 'legacy method not invented');
    check($historical->data['receipt']['reference_no'] === 'BANK-REF', 'legacy reference retained');
    check($controller->legacy($student,$audit)->data['receipt'] === $historical->data['receipt'], 'legacy receipt and voucher show same data');
    foreach ([['subject_id'=>999],['action'=>'updated'],['subject_type'=>'Voucher'],['description'=>'Student credit applied to vouchers: 50000.00'],['description'=>'Received extra payment of 0 from Name (STU-2026-0700), added to student balance']] as $bad) {
        $copy=clone $audit;foreach($bad as $key=>$value)$copy->$key=$value;
        rejected(fn()=>$controller->legacyVoucher($student,$copy), 'invalid legacy record rejected: '.json_encode($bad));
    }
    $source=file_get_contents($root.'/app/Http/Controllers/ExtraPaymentReceiptController.php');
    foreach (['->save(', '->update(', '->increment(', '->decrement(', '::create(', '->delete(', 'DB::', 'StudentBalanceService', 'Payment::', 'Voucher::'] as $write) {
        check(!str_contains($source,$write),'receipt/voucher controller contains no financial write: '.$write);
    }
    $routes=file_get_contents($root.'/routes/web.php');
    $group=strpos($routes,"Route::middleware(['auth:accounts', 'role:accountant'])");
    foreach(['voucher','legacy-voucher'] as $action) {
        $pos=strpos($routes,"->name('payments.extra-receipts.$action')");
        check($pos!==false&&$pos>$group,'paid voucher route beneath Accounts guard: '.$action);
        $line=explode("\n",substr($routes,strrpos(substr($routes,0,$pos),"\n")+1))[0];
        check(str_contains($line,'Route::get(')&&str_contains($line,'whereNumber('),'read-only GET route with numeric record ID: '.$action);
    }
    $view=file_get_contents($root.'/resources/views/payments/partials/extra-payment-document.blade.php');
    foreach(['window.print()', "@include('vouchers.partials.modern-challan-styles')", 'Office Copy — Office Use Only', 'Student Copy', 'Print Date', 'Do not pay this amount again.', 'does not certify that all student dues are cleared', "number_format((float) $"."receipt['amount'], 2)"] as $token) check(str_contains($view,$token),'paired document contract: '.$token);
    check(str_contains($view,'Bank Copy') && !str_contains($view,'College Copy'), 'extra-payment document contains bank copy');
    check(str_contains($view,'size: A4 landscape'), 'single-page three-copy print format');
    $styles=file_get_contents($root.'/resources/views/vouchers/partials/modern-challan-styles.blade.php');
    check(str_contains($styles, 'size: A4 landscape'), 'regular fee-voucher print format unchanged');
    foreach (['receipts-row', 'receipt', 'copy-tag', 'r-header', 'r-brand', 'r-dates', 'r-info', 'r-fee-breakdown', 'r-total-box', 'r-bank', 'r-stamps', 'r-footer'] as $className) {
        check(str_contains($view, 'class="'.$className.'"'), 'existing design element retained: '.$className);
    }
    foreach(['{!!', 'credit_balance', 'combinedPaidAmount', 'lms_login_password', 'Main Campus', 'Due Date'] as $token) check(!str_contains($view,$token),'payment document excludes misleading/unsafe field: '.$token);
    foreach(['accounts/students-show','payments/extra-receipts','partials/student-credit'] as $name) {
        $entry=file_get_contents($root.'/resources/views/'.$name.'.blade.php');
        check(str_contains($entry,'Print All 3 Copies'), 'three-copy print action: '.$name);
    }
    $studentView=file_get_contents($root.'/resources/views/payments/extra-receipt.blade.php');
    $officeView=file_get_contents($root.'/resources/views/payments/extra-payment-voucher.blade.php');
    check(str_contains($studentView, "@include('payments.partials.extra-payment-document')"), 'receipt wrapper opens combined sheet');
    check(str_contains($officeView, "@include('payments.partials.extra-payment-document')"), 'voucher wrapper opens same combined sheet');
    $pdfSave=$controller->download($student,$tx);
    check($pdfSave->view==='payments.extra-receipt', 'old download URL preserves the combined branded template');
    check(str_contains($pdfSave->data['downloadNotice'],'Save as PDF'), 'old download URL explains browser PDF saving');
    check(($pdfSave->headers['X-Content-Type-Options'] ?? '')==='nosniff', 'print/download responses disable MIME sniffing');
    check($controller->legacyDownload($student,$audit)->view==='payments.extra-receipt','historical download also opens combined sheet');
    foreach ([['student_id'=>999],['type'=>'allocation'],['amount'=>'0.00']] as $bad) {
        $copy=clone $tx; foreach($bad as $key=>$value)$copy->$key=$value;
        rejected(fn()=>$controller->show($student,$copy),'invalid student receipt rejected');
        rejected(fn()=>$controller->download($student,$copy),'invalid download rejected');
    }
    echo "PASS: $count isolated controller-stub and source-contract checks.\n";
    echo "No Laravel middleware, routing, database writes, or real student data tested.\n";
}
