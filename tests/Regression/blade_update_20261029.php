<?php
/** Compile the changed views with the REAL installed Laravel Blade compiler. No DB writes. */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "NOT RUN: vendor/autoload.php is missing. Run in the installed application.\n"); exit(2);
}
require $root.'/vendor/autoload.php';
$compiler = new Illuminate\View\Compilers\BladeCompiler(new Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
$views = [
    'resources/views/partials/student-credit.blade.php',
    'resources/views/vouchers/index.blade.php',
    'resources/views/payments/extra-receipt.blade.php',
    'resources/views/payments/extra-payment-voucher.blade.php',
    'resources/views/payments/extra-receipts.blade.php',
    'resources/views/accounts/receive-payment.blade.php',
    'resources/views/accounts/students-show.blade.php',
    'resources/views/admin/activities/_learning-task-form.blade.php',
    'resources/views/admin/activities/_learning-task-list.blade.php',
    'resources/views/admin/activities/index.blade.php',
    'resources/views/admin/activities/requests.blade.php',
    'resources/views/exam-papers/print.blade.php',
    'resources/views/examinations/edit.blade.php',
    'resources/views/examinations/index.blade.php',
    'resources/views/examinations/schedule.blade.php',
    'resources/views/examinations/show.blade.php',
    'resources/views/ledger/general.blade.php',
    'resources/views/lms/assignments/form.blade.php',
    'resources/views/lms/assignments/show.blade.php',
    'resources/views/lms/courses/teacher-subjects.blade.php',
    'resources/views/lms/partials/task-audience.blade.php',
    'resources/views/lms/quizzes/form.blade.php',
    'resources/views/lms/quizzes/show.blade.php',
    'resources/views/lms/quizzes/take.blade.php',
    'resources/views/partials/sidebar-student.blade.php',
    'resources/views/reports/collection.blade.php',
    'resources/views/reports/print/collection.blade.php',
    'resources/views/student/learning-tasks.blade.php',
    'resources/views/teacher/activities/_learning-tasks.blade.php',
    'resources/views/teacher/activities/index.blade.php',
];
$failed = 0;
foreach ($views as $view) {
    try {
        $source = file_get_contents($root.'/'.$view);
        if ($source === false) throw new RuntimeException('Missing view');
        token_get_all($compiler->compileString($source), TOKEN_PARSE);
        echo "PASS: $view\n";
    } catch (Throwable $e) { $failed++; fwrite(STDERR, "FAIL: $view: ".$e->getMessage()."\n"); }
}
echo count($views)." views checked; $failed failed. Syntax only, not rendered integration.\n";
exit($failed ? 1 : 0);
