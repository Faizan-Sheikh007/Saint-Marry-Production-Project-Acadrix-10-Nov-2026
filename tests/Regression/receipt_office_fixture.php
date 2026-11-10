<?php
/**
 * Executable view fixtures using a LIMITED Blade-like translator, not Laravel.
 * No database or real payment records are read. The original null relationship
 * failure is reproduced by rendering the actual challan with a portal login and
 * no fee assignment; PHP warnings are promoted to exceptions.
 */
declare(strict_types=1);
namespace App\Models {
    final class Setting {
        public static array $values = [
            'institution_name' => "Saint Mary's College of Medical & Advance Studies",
            'institution_tagline' => '', 'institution_logo' => null, 'currency' => 'PKR',
        ];
        public static function get(string $key, mixed $default = null): mixed { return self::$values[$key] ?? $default; }
    }
}
namespace Illuminate\Support {
    final class Str { public static function slug(string $s): string { return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $s), '-')); } }
}
namespace {
set_error_handler(function(int $severity, string $message, string $file, int $line): never {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__, 2);
$out = getenv('ACADRIX_QA_OUTPUT') ?: sys_get_temp_dir().'/acadrix-receipt-office';
if (!is_dir($out)) mkdir($out, 0770, true);
function escaped(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function filled(mixed $v): bool { return $v !== null && (!is_string($v) || trim($v) !== ''); }
function session(string $key): mixed { return null; }
function now(): DateTimeImmutable { return new DateTimeImmutable('2026-11-02 12:00:00'); }
function route(string $name, mixed $args): string { return '/fixture/'.$name.'/'.implode('/',array_map(fn($v)=>is_object($v)?$v->id:(string)$v,is_array($args)?$args:[$args])); }
function asset(string $path): string {
    global $root;
    if ($path === 'css/app.css') return 'data:text/css;base64,'.base64_encode(preg_replace('/^@import[^\r\n]*$/m', '', file_get_contents($root.'/public/'.$path)));
    if ($path === 'logo.png') return 'data:image/png;base64,'.base64_encode(file_get_contents($root.'/public/logo.png'));
    throw new \RuntimeException('Unexpected fixture asset: '.$path);
}
function storage_asset(string $path): string { return asset('logo.png'); }
function compileFixture(string $source): string {
    global $root;
    $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);
    $source = preg_replace_callback("/@include\('([^']+)'\)/", fn($m)=>compileFixture(file_get_contents($root.'/resources/views/'.str_replace('.', '/', $m[1]).'.blade.php')), $source);
    $source = str_replace(['@php','@endphp'], ['<?php','?>'], $source);
    $source = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', fn($m)=>'<?php echo escaped('.$m[1].'); ?>', $source);
    foreach (['forelse','foreach','elseif','if','json'] as $directive) {
        $offset = 0;
        while (($start = strpos($source, '@'.$directive.'(', $offset)) !== false) {
            $open=$start+strlen($directive)+1; $depth=1; $quote=null; $end=$open+1;
            for (; $end < strlen($source); $end++) {
                $c=$source[$end];
                if ($quote!==null) { if ($c==='\\') $end++; elseif ($c===$quote) $quote=null; continue; }
                if ($c==='"'||$c==="'") { $quote=$c; continue; }
                if ($c==='(') $depth++;
                if ($c===')' && --$depth===0) break;
            }
            if ($depth!==0) throw new \RuntimeException('Unclosed directive: '.$directive);
            $expr=substr($source,$open+1,$end-$open-1);
            $replacement=match($directive) {
                'forelse'=>'<?php $__fixtureEmpty=true; foreach ('.$expr.'): $__fixtureEmpty=false; ?>',
                'json'=>'<?php echo json_encode('.$expr.'); ?>',
                default=>'<?php '.$directive.' ('.$expr.'): ?>',
            };
            $source=substr_replace($source,$replacement,$start,$end-$start+1); $offset=$start+strlen($replacement);
        }
    }
    $source = str_replace(['@endforelse','@endforeach','@empty','@else','@endif','@once','@endonce'], ['<?php endif; ?>','<?php endforeach; ?>','<?php endforeach; if ($__fixtureEmpty): ?>','<?php else: ?>','<?php endif; ?>','',''], $source);
    token_get_all($source,TOKEN_PARSE);
    return $source;
}
function renderFixture(string $view, array $data): string {
    global $root;
    $compiled=compileFixture(file_get_contents($root.'/resources/views/'.$view.'.blade.php'));
    extract($data, EXTR_SKIP);
    ob_start();
    try { eval('?>'.$compiled); return ob_get_clean(); }
    catch (\Throwable $e) { ob_end_clean(); throw $e; }
}
final class Rows implements \IteratorAggregate {
    public function __construct(public array $items = []) {}
    public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
    public function isNotEmpty(): bool { return count($this->items)>0; }
    public function sum(callable|string $what): float { return array_sum(array_map(fn($x)=>is_callable($what)?$what($x):$x->$what, $this->items)); }
    public function map(callable $fn): self { return new self(array_map($fn, $this->items)); }
    public function groupBy(callable $by): self {
        $groups = [];
        foreach ($this->items as $row) { $groups[$by($row)][] = $row; }
        return new self(array_map(fn($group) => new self($group), $groups));
    }
    public function filter(): self { return new self(array_filter($this->items)); }
    public function unique(): self { return new self(array_unique($this->items)); }
    public function implode(string $join): string { return implode($join, $this->items); }
    public function links(): string { return ''; }
}
final class FeeAssignmentFixture {
    public ?string $lms_login_email='fallback@example.invalid';
    public ?string $lms_login_password_plain=null;
    public int $cycle_start=1;
    public function installmentLabelFor($cycle,$part): string { return 'Year 1 | 2nd Installment'; }
}
final class VoucherFixture {
    public mixed $student=null; public mixed $feeAssignment=null;
    public ?int $cycle_no=1; public ?int $part_no=2; public int $installment_no=2;
    public ?int $split_sequence=null; public mixed $issued_at=null; public mixed $due_date=null;
    public ?string $month='Year 1 - Part 2'; public string $voucher_no='VCH-PREVIEW'; public int $id=11;
    public function carryForwardBalance(): float { return 0; }
}
final class ItemFixture {
    public mixed $feeHead=null; public string $title='Tuition Fee'; public float $paid_amount=0;
    public function total(): float { return 15000; }
}
$count=0;
function check(bool $ok, string $label): void { global $count; $count++; if (!$ok) throw new \RuntimeException('FAIL: '.$label); }
require $root.'/app/Services/CreditAllocator.php';
require $root.'/app/Services/RupeeWords.php';
$student=(object)[
    'id'=>700,'name'=>'Aatiqa','student_id'=>'STU-2026-0700','ledger_number'=>'150',
    'father_name'=>null,'guardian_name'=>null,'academicYear'=>(object)['name'=>'2026-2030'],
    'program'=>(object)['name'=>'BS (Hons.) Human Nutrition and Dietetics'],
    'schoolClass'=>(object)['name'=>'Semester 1','section'=>null,'section_label'=>'Semester 1'],
    'user'=>(object)['email'=>'stu-0000@example.invalid'],
];
$baseData=[
    'student'=>$student,'institutionName'=>\App\Models\Setting::get('institution_name'),'logoPath'=>null,
    'generatedAt'=>'29 Oct 2026 (preview)','downloadNotice'=>null,
    'profile'=>['program'=>$student->program->name,'session'=>'2026-2030','semester'=>'Semester 1'],
    'bankAccounts'=>new Rows([(object)['bank_name'=>'Bank from Settings','account_title'=>'Configured institution account','account_number'=>'PREVIEW ONLY']]),
    'declaration'=>'PREVIEW: Your existing declaration from Settings will appear here. This sample does not certify a live payment.',
    'studentLogin'=>'stu-0000@example.invalid',
];
$baseReceipt=[
    'number'=>'EPR-PREVIEW-12','source'=>'Preview only; no live receipt loaded','legacy'=>false,'amount'=>'5.00',
    'student_name'=>'Aatiqa','student_id'=>'STU-2026-0700','paid_at'=>'29 Oct 2026','recorded_at'=>'Not verified in preview',
    'method'=>'Cash','reference_no'=>'Not recorded','received_by'=>'Not verified in preview','notes'=>null,
    'receipt_url'=>'/fixture/student-receipt','voucher_url'=>'/fixture/office-voucher','download_url'=>'/fixture/download',
];
$cases=[
    'preview'=>[], 'fractional'=>['amount'=>'5.25'], 'standard'=>['amount'=>'50000.00'],
    'legacy'=>['number'=>'EPR-L-PREVIEW','legacy'=>true,'amount'=>'50000.00','paid_at'=>'Not recorded in legacy entry','method'=>'Not recorded in legacy entry'],
    'unicode'=>['student_name'=>'علی احمد','notes'=>'<script>window.__injected=true</script> فیس کی ادائیگی'],
    'long-declaration'=>['notes'=>str_repeat('Verified record detail for layout testing. ',24)],
    'long'=>['student_name'=>str_repeat('Example Long Student Name ',4),'reference_no'=>str_repeat('Ref',34),'notes'=>str_repeat('A long payment note that must wrap without disappearing. ',70)],
];
foreach (['student'=>'payments/extra-receipt','office'=>'payments/extra-payment-voucher'] as $audience=>$view) {
    foreach ($cases as $case=>$changes) {
        $receipt=array_replace($baseReceipt,$changes); $receipt['words']=\App\Services\RupeeWords::forAmount($receipt['amount']);
        $fixtureData=[...$baseData,'receipt'=>$receipt];
        if ($case==='long-declaration') {
            $fixtureData['declaration']=str_repeat('PREVIEW DECLARATION: The payment shown is acknowledged without certifying that all student dues are cleared. ',14);
            $fixtureData['bankAccounts']=new Rows([
                (object)['bank_name'=>'Bank One','account_title'=>'Configured institution account','account_number'=>'PREVIEW ONLY'],
                (object)['bank_name'=>'Bank Two','account_title'=>'Second institution account','account_number'=>'PREVIEW ONLY'],
            ]);
        }
        $html=renderFixture($view,$fixtureData);
        $html=str_replace('<div class="copy-tag">','<div class="r-fee-note">PREVIEW / NOT ISSUED — no live receipt loaded.</div><div class="copy-tag">',$html);
        file_put_contents($out.'/'.$audience.'-'.$case.'.html',$html);
        check(substr_count($html,'<section class="receipt"')===3,"$audience/$case: student, bank and office copies on same sheet");
        check(str_contains($html,'data-document-audience="student-bank-office"'),"$audience/$case: three-copy audience");
        check(substr_count($html,'PKR '.number_format((float)$receipt['amount'],2))===6,"$audience/$case: original amount in breakdown and total on all three copies");
        check(str_contains($html,'Bank Copy'),"$audience/$case: bank copy present");
        check(!str_contains($html,'Payable Amount'),"$audience/$case: not a new fee demand");
        check(!str_contains($html,'lms_login_password_plain'),"$audience/$case: no password field");
        check(substr_count($html,'LMS Login')===1,"$audience/$case: student login occurs only once");
    }
    // Sparse profile, no LMS, bank, declaration or assignment: must render normally.
    $receipt=$baseReceipt; $receipt['words']='Five Rupees Only';
    $sparse=[...$baseData,'student'=>(object)['id'=>700,'father_name'=>null,'guardian_name'=>null,'ledger_number'=>null,'academicYear'=>null],
        'studentLogin'=>null,'bankAccounts'=>new Rows(),'declaration'=>'','receipt'=>$receipt];
    $html=renderFixture($view,$sparse);
    check(!str_contains($html,'institution-bank-details'),"$audience: absent bank hidden");
    check(!str_contains($html,'institution-declaration'),"$audience: cleared declaration hidden");
    check(!str_contains($html,'student-login'),"$audience: no missing-login crash");
    file_put_contents($out.'/'.$audience.'-sparse.html',$html);
}
check(file_get_contents($out.'/student-preview.html')===file_get_contents($out.'/office-preview.html'),'both print entry points render the identical three-copy sheet');
check(substr_count(file_get_contents($out.'/student-preview.html'), 'class="receipt" data-copy=') === 3, 'extra-payment document contains exactly three receipts');
check(str_contains(file_get_contents($out.'/student-preview.html'), 'Bank Copy'), 'bank copy present in extra payment document');
// New collection-specific paid receiving receipt: exactly two independent copies,
// with the ORIGINAL one-action amount preserved even when split across vouchers.
$paid = (object)[
    'receipt_number'=>'PR-PREVIEW-001','amount'=>'25200.00',
    'paid_at'=>new \DateTimeImmutable('2026-11-03'),
    'recorded_at'=>new \DateTimeImmutable('2026-11-03 10:55:00'),
    'method'=>'cash','reference_no'=>'EXAMPLE-ONLY','received_by'=>'Accounts Officer',
    'notes'=>'Test collection. This is not a real issued receipt.',
    'allocations'=>new Rows([
        (object)['voucher'=>(object)['voucher_no'=>'VCH-A'],'amount'=>22000.00],
        (object)['voucher'=>(object)['voucher_no'=>'VCH-B'],'amount'=>3000.00],
        (object)['voucher'=>(object)['voucher_no'=>'VCH-C'],'amount'=>200.00],
    ]),
];
$paidHtml = renderFixture('payments/received-payment-receipt', [
    'student'=>$student, 'receipt'=>$paid,
    'institutionName'=>\App\Models\Setting::get('institution_name'),
]);
check(substr_count($paidHtml,'<section class="paper"') === 2, 'paid receiving receipt is two copies, not three');
check(str_contains($paidHtml,'data-document-audience="student-office"'), 'paid receipt print audience excludes bank');
check(!str_contains($paidHtml,'Bank Copy'), 'paid receiving receipt has no bank copy');
check(substr_count($paidHtml,'Rs. 25,200.00') === 4, 'two paid copies show exact amount in header and total');
check(substr_count($paidHtml,'Rs. 22,000.00') === 2, 'voucher split is shown on each copy without extra cash receipt');
file_put_contents($out.'/received-paid-preview.html', $paidHtml);

// Fee installment summary must also print Student/Bank/Office, not two copies.
$summaryStudent = clone $student;
$summaryStudent->photo = null;
$summaryStudent->section = null;
$summaryStudent->session = '2026-2030';
$summaryStudent->father_name = 'Parent Name';
$summaryData = [
    'student'=>$summaryStudent,
    'feeAssignment'=>(object)[],
    'feeNote'=>'Please pay your installment on time.',
    'cycleLabel'=>'Semester 1',
    'scopeTotal'=>45000, 'installmentsCount'=>3, 'perInstallment'=>15000,
    'rows'=>[
        ['voucher_no'=>'VCH-PREVIEW-A','payable'=>15000,'due_date'=>new \DateTimeImmutable('2026-11-08')],
        ['voucher_no'=>'VCH-PREVIEW-B','payable'=>15000,'due_date'=>new \DateTimeImmutable('2026-12-08')],
        ['voucher_no'=>'VCH-PREVIEW-C','payable'=>15000,'due_date'=>new \DateTimeImmutable('2027-01-08')],
    ],
    'declaration'=>'A sample declaration for print-layout testing.',
    'loop'=>(object)['iteration'=>1], // Only the test translator lacks Blade's real $loop helper.
];
$summaryHtml = renderFixture('fee-assignments/summary', $summaryData);
check(substr_count($summaryHtml,'<div class="copy">')===3, 'fee installment summary includes three copies');
check(str_contains($summaryHtml,'Bank Copy'), 'fee installment summary includes bank copy');
file_put_contents($out.'/fee-summary-preview.html', $summaryHtml);

// ACTUAL regular challan partial with the exact reported relationship scenario.
foreach (['portal-no-assignment','no-portal-no-assignment','assignment-no-password','assignment-password','missing-student'] as $case) {
    $voucher=new VoucherFixture(); $voucher->student=clone $student;
    if ($case==='no-portal-no-assignment') $voucher->student->user=null;
    if (str_starts_with($case,'assignment-')) $voucher->feeAssignment=new FeeAssignmentFixture();
    if ($case==='assignment-password') $voucher->feeAssignment->lms_login_password_plain='FIXTURE-ONLY-PASSWORD';
    if ($case==='missing-student') $voucher->student=null;
    $html=renderFixture('vouchers/partials/modern-challan',[
        'voucher'=>$voucher,'lineItems'=>new Rows([new ItemFixture()]),'feeNote'=>null,
        'bankAccounts'=>new Rows(),'declaration'=>'','qrUrl'=>null,
    ]);
    check(substr_count($html,'<div class="receipt">')===3,"$case: regular voucher retains three copies");
    check(str_contains($html,'15,000'),"$case: ordinary voucher amount retained");
    check(str_contains($html,'FIXTURE-ONLY-PASSWORD')===($case==='assignment-password'),"$case: password optional without null dereference");
    if ($case==='portal-no-assignment') check(str_contains($html,'stu-0000@example.invalid'),'portal/no assignment reproducer: login printed without crashing');
    file_put_contents($out.'/challan-'.$case.'.html',$html);
}
// Render the actual main content of the second-screenshot history view.
$receipts=new Rows([(object)['id'=>12,'amount'=>'5.00','paid_at'=>new \DateTimeImmutable('2026-10-29'),'method'=>'cash','reference_no'=>null]]);
$legacyReceipts=new Rows([['record'=>(object)['id'=>88,'created_at'=>new \DateTimeImmutable('2026-10-29')],'data'=>['amount'=>'50000.00','reference_no'=>'BANK-PREVIEW']]]);
$source=file_get_contents($root.'/resources/views/payments/extra-receipts.blade.php');
$source=substr($source,strpos($source,"@section('content')")+strlen("@section('content')"));
$source=str_replace('@endsection','',$source);
$compiled=compileFixture($source); ob_start(); eval('?>'.$compiled); $history=ob_get_clean();
check(substr_count($history,'Print All 3 Copies</a>')===4,'three-copy print buttons in ledger and historical rows');
check(str_contains($history,'/fixture/payments.extra-receipts.voucher/700/12'),'ledger office button targets original receipt ID');
check(str_contains($history,'/fixture/payments.extra-receipts.legacy-voucher/700/88'),'legacy office button targets original audit ID');
$history='<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="'.asset('css/app.css').'"><style>body{padding:24px}.card-header h3{margin:0}.card{margin:20px 0}a{white-space:nowrap}h2{margin-bottom:6px}.preview-warning{padding:10px;background:#fff4da;margin-bottom:16px}</style></head><body><div class="preview-warning">PREVIEW / NOT A LIVE ACCOUNT — payment details are static examples.</div>'.$history.'</body></html>';
file_put_contents($out.'/history-preview.html',$history);
echo "PASS: $count limited-translator fixture assertions, including the single-page three-copy sheet and missing-fee-assignment failure.\n";
echo "NOT a Laravel/Blade engine or database integration test.\n";
}
