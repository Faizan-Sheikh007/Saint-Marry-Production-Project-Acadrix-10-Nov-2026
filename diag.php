<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = App\Models\Student::find(443);

echo "Student: {$s->name} ({$s->student_id})\n";
echo str_repeat('-', 100)."\n";

foreach ($s->vouchers as $v) {
    echo sprintf(
        "id:%-5s fa_id:%-6s cycle:%-4s inst:%-4s tmpl:%-6s batch:%-8s status:%-10s amount:%-8s paid:%-8s balance:%-8s | %s\n",
        $v->id,
        $v->fee_assignment_id ?? 'NULL',
        $v->cycle_no ?? 'NULL',
        $v->installment_no ?? 'NULL',
        $v->fee_template_id ?? 'NULL',
        $v->batch_id ?: 'NULL',
        $v->status,
        $v->amount,
        $v->paid_amount,
        $v->balance(),
        $v->title
    );
}

echo str_repeat('-', 100)."\n";
echo "outstandingBalance(): ".$s->outstandingBalance()."\n";

echo "\n=== Voucher #252 detail ===\n";
$v = App\Models\Voucher::find(252);
echo "voucher_no: {$v->voucher_no}\n";
echo "issued_at: {$v->issued_at}\n";
echo "due_date: {$v->due_date}\n";
echo "notes: {$v->notes}\n";
echo "created_at: {$v->created_at}\n";

echo "\n=== Fee Assignment #22 (the Year1/Year2 plan) ===\n";
$fa = App\Models\FeeAssignment::find(22);
echo "created_at: {$fa->created_at}\n";

echo "\n=== Audit log mentioning voucher 252 ===\n";
foreach (App\Models\AuditLog::where('model', 'Voucher')->where('model_id', 252)->get() as $log) {
    echo "{$log->created_at} | {$log->action} | {$log->description} | user_id:{$log->user_id}\n";
}

echo "\n=== Audit log for voucher 252 (corrected columns) ===\n";
foreach (App\Models\AuditLog::where('subject_type', 'Voucher')->where('subject_id', 252)->get() as $log) {
    $user = $log->user_id ? App\Models\User::find($log->user_id) : null;
    echo "{$log->created_at} | {$log->action} | {$log->description} | user: " . ($user->name ?? 'unknown') . " (id:{$log->user_id})\n";
}

echo "\n=== Everything logged around that timestamp for this student ===\n";
foreach (App\Models\AuditLog::where('description', 'like', '%Abdullah Ali%')->orderBy('created_at')->get() as $log) {
    echo "{$log->created_at} | {$log->action} | {$log->subject_type}#{$log->subject_id} | {$log->description}\n";
}
