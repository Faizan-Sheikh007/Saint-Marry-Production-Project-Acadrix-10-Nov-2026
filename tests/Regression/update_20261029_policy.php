<?php
/** Pure policy and source-contract checks. No framework/database/server needed. */
declare(strict_types=1);
require dirname(__DIR__, 2).'/app/Services/CreditAllocator.php';
require dirname(__DIR__, 2).'/app/Services/LearningTaskVisibility.php';
use App\Services\CreditAllocator as Credit;
use App\Services\LearningTaskVisibility as Visibility;
$checks = 0;
function check(bool $condition, string $name): void {
    global $checks; $checks++;
    if (! $condition) throw new RuntimeException('FAIL: '.$name);
}
function throws(callable $call, string $name): void {
    try { $call(); } catch (InvalidArgumentException) { check(true, $name); return; }
    check(false, $name);
}
function money(string|int|float $value): int { return Credit::minor($value); }
$rows = [['id'=>2,'balance'=>money(28000)],['id'=>3,'balance'=>money(15000)],['id'=>4,'balance'=>money(15000)]];
$result = Credit::allocate(money(50000), $rows);
check($result['allocations'] === [['id'=>2,'amount'=>2800000],['id'=>3,'amount'=>1500000],['id'=>4,'amount'=>700000]], 'XYZ: oldest two installments settled, Rs.7,000 toward fourth');
check(array_sum(array_column($rows,'balance'))-$result['applied'] === money(8000), 'XYZ: Rs.58,000 - Rs.50,000 = Rs.8,000');
check($result['remaining'] === 0, 'XYZ: no unused credit after allocation');
check(Credit::allocate(money(60000), $rows)['remaining'] === money(2000), 'surplus retained as credit');
check(Credit::allocate(money(58000), $rows)['applied'] === money(58000), 'exact settlement');
check(Credit::allocate(0, $rows)['allocations'] === [], 'repeating allocation with zero remaining credit is a no-op');
check(Credit::allocate(money(100), [])['remaining'] === money(100), 'advance payment remains when no vouchers exist');
check(Credit::allocate(1, [['id'=>1,'balance'=>2]])['remaining'] === 0, 'one-paisa credit allocates exactly');
check(Credit::allocate(3, [['id'=>1,'balance'=>1],['id'=>2,'balance'=>5]])['allocations'][1]['amount'] === 2, 'fractional balance waterfall');
check(Credit::allocate(100, [['id'=>1,'balance'=>0],['id'=>2,'balance'=>50]])['applied'] === 50, 'paid rows skipped');
check(Credit::allocate(100, [['id'=>99,'balance'=>90],['id'=>1,'balance'=>100]])['allocations'][0]['id'] === 99, 'preserves caller due-date ordering, not arbitrary ID ordering');
foreach (['0'=>0,'1'=>100,'0.01'=>1,'1.2'=>120,'50000.00'=>5000000,'9999999999.99'=>999999999999] as $input=>$expected) check(money((string)$input)===$expected,'money parsing '.$input);
check(Credit::decimal(-123)==='-1.23','signed ledger formatting');
check(Credit::decimal(1)==='0.01','paisa formatting');
check(Credit::decimal(0)==='0.00','zero formatting');
foreach (['-1','NaN','1e4','1.123','10,000','10000000000',''] as $bad) throws(fn()=>money($bad),'reject invalid money '.$bad);
throws(fn()=>Credit::allocate(-1,[]),'negative credit rejected');
throws(fn()=>Credit::allocate(1,[['id'=>1,'balance'=>-1]]),'negative voucher rejected');
throws(fn()=>Credit::allocate(100,[['id'=>1,'balance'=>50],['id'=>1,'balance'=>50]]),'duplicate voucher row rejected');
mt_srand(20261029);
for ($case=0;$case<300;$case++) {
    $available=mt_rand(0,10000000); $sample=[];
    for($i=1;$i<=mt_rand(1,16);$i++) $sample[]=['id'=>$i,'balance'=>mt_rand(0,1000000)];
    $answer=Credit::allocate($available,$sample);
    check($answer['applied']+$answer['remaining']===$available,'money conserved '.$case);
    check($answer['applied']===min($available,array_sum(array_column($sample,'balance'))),'maximal non-overpaying allocation '.$case);
    check(array_sum(array_column($answer['allocations'],'amount'))===$answer['applied'],'ledger matches applied total '.$case);
    foreach($answer['allocations'] as $portion) check($portion['amount']>0 && $portion['amount']<=$sample[$portion['id']-1]['balance'],'per-row cap '.$case);
}
check(Visibility::visible('active',true,null,1),'active published all-student task visible');
check(Visibility::visible('active',true,[],1),'empty legacy audience means all enrolled students');
check(Visibility::visible('closed',true,[1],1),'closed published task retained as history');
check(!Visibility::visible('closed',false,null,1),'unpublished closed admin request hidden');
check(!Visibility::visible('draft',true,null,1),'draft hidden even if previously published');
check(!Visibility::visible('active',false,null,1),'unpublished active request hidden');
check(Visibility::visible('active',true,['1'],1),'numeric audience IDs normalized');
check(!Visibility::visible('active',true,[2,3],1),'student not targeted cannot view task');
check(!Visibility::visible('cancelled',true,null,1),'unsupported task state hidden');
$root=dirname(__DIR__,2);
$paper=file_get_contents($root.'/resources/views/exam-papers/print.blade.php');
foreach(['institution_campus','Main Campus','Controller of Examinations','Subject Teacher','foot-row','doc-sub'] as $removed) check(!str_contains($paper,$removed),'paper source omits '.$removed);
check(str_contains($paper,'class="lbl">Program'),'paper program label');
check(str_contains($paper,'class="lbl">Session'),'paper session label');
check(str_contains($paper,'$exam->session_label'),'paper uses exam-specific session');
check(substr_count($paper,'class="doc-name"')===1,'single visible paper title');
$routes=file_get_contents($root.'/routes/web.php');
foreach(["->name('courses.show')->whereNumber('course')","->name('assignments.show')->whereNumber('assignment')","->name('quizzes.show')->whereNumber('quiz')","payments.apply-credit","student.\\App"] as $contract) {
    if($contract==='student.\\App') continue;
    check(str_contains($routes,$contract),'route contract '.$contract);
}
$credit=file_get_contents($root.'/app/Services/StudentBalanceService.php');
check(str_contains($credit,'authoritativeVouchers()'),'allocation uses authoritative fee rows');
check(str_contains($credit,"'method' => 'balance_credit'"),'allocation distinguished from cash receipt');
check(str_contains($credit,'lockForUpdate()'),'allocation transaction has locking');
$cash=file_get_contents($root.'/app/Services/CashReceiptLedger.php');
check(str_contains($cash,"where('method', '!=', 'balance_credit')"),'cash totals exclude credit transfers');
check(str_contains($cash,"where('type', 'receipt')"),'cash totals include receipts but not reversals/allocations');
fwrite(STDOUT,"PASS: $checks policy/property/source-contract assertions.\n");
fwrite(STDOUT,"Not a Laravel/database/browser integration test. Source-contract assertions do not execute routes or queries.\n");
