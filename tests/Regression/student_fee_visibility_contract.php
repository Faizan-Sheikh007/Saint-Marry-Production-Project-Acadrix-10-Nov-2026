<?php
/** Read-only source contract tests: does not need Laravel/vendor or any DB. */
declare(strict_types=1);
$root=dirname(__DIR__,2);
function assertSource(bool $ok,string $message):void{global $checks;$checks++;if(!$ok)throw new RuntimeException($message);}
$checks=0;
$read=static fn(string $file): string => file_get_contents($root.'/'.$file);
$ledger=$read('app/Services/StudentFeeLedger.php');
$student=$read('app/Models/Student.php');
$accounts=$read('resources/views/accounts/students-show.blade.php');
$acctCtl=$read('app/Http/Controllers/AccountsDirectoryController.php');
$studentView=$read('resources/views/student/fees.blade.php');
$adminView=$read('resources/views/students/show.blade.php');
$finance=$read('resources/views/partials/student-finance-history.blade.php');
$dashboard=$read('resources/views/student/dashboard.blade.php');
$summary=$read('resources/views/partials/fee-summary.blade.php');
assertSource(str_contains($ledger,"$".'filters[] = ' . "'unpaid_only'"), 'unpaid-only ledger tag');
assertSource(str_contains($ledger,"$".'paymentState === ' . "'Partial'"), 'partial payment branch');
assertSource(str_contains($ledger,"$".'schedule === ' . "'Overdue'"), 'overdue balance branch');
assertSource(str_contains($student,'voucher:'."'" ) || str_contains($student,"'voucher:'"), 'feeSummary per voucher group');
assertSource(str_contains($student,'$previousGroups') && str_contains($student,'$paidGroups'), 'status buckets built from groups');
assertSource(str_contains($acctCtl,"$".'feeLedger[\'totals\'][\'pending\']'), 'Accounts uses authoritative ledger pending total');
assertSource(str_contains($acctCtl,"$".'feeLedger[\'totals\'][\'overdue\']'), 'Accounts uses overdue total');
assertSource(str_contains($accounts,'Scheduled payments') && str_contains($accounts,'nothing overdue'), 'future balance not called overdue');
assertSource(str_contains($accounts,'$lineVoucherNumbers') && str_contains($accounts,'$linePending'), 'split voucher references visible');
assertSource(str_contains($accounts,"@include('partials.student-finance-history'"), 'Accounts uses shared finance ledger');
assertSource(str_contains($studentView,"@include('partials.student-finance-history'"), 'student portal shows ledger');
assertSource(str_contains($adminView,"@include('partials.student-finance-history'"), 'admin student shows ledger');
assertSource(str_contains($adminView,'$planPosition') && str_contains($adminView,'$groupSchedule'), 'admin plan gets dynamic payment status');
assertSource(str_contains($dashboard,'Nothing overdue'), 'student dashboard distinguishes overdue');
assertSource(str_contains($finance,'Complete Payment History') && str_contains($finance,'Credit Movements'), 'payments and credit movements separate');
assertSource(str_contains($finance,'Unpaid Only') && str_contains($finance,'Partially Paid') && str_contains($finance,'Overdue') && str_contains($finance,'Upcoming'), 'vouchers are filterable');
assertSource(str_contains($summary,"'Partially Paid'") && str_contains($summary,'$row->schedule'), 'old fee summary displays partial and due date separately');
assertSource(!str_contains($studentView, "route('payments.apply-credit'"), 'student has no accounts credit action');
assertSource(str_contains($read('routes/web.php'),"Route::get('/fees',"), 'student fees route still registered');
echo "PASS: $checks read-only Student/Accounts fee visibility source-contract checks.\n";
