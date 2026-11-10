<?php
/** Real Blade compilation/PHP parsing; run with installed vendor on staging. No SQL or HTTP request. */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
if (!is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "NOT RUN: vendor/autoload.php is absent. Run on installed staging.\n");
    exit(2);
}
require $root.'/vendor/autoload.php';
$views = [
    'admin/reporting/attendance-summary',
    'admin/reporting/partials/fee-plan-print-table',
    'admin/reporting/partials/fee-plan-table',
    'admin/reporting/print/attendance-summary',
    'admin/reporting/print/scholarship',
    'admin/reporting/print/student-profile',
    'admin/reporting/scholarship',
    'admin/reporting/student-search',
    'attendance/print',
    'attendance/report',
    'attendance/show',
    'dashboard/student-explorer-print',
    'exam-papers/print',
    'examinations/report',
    'examinations/results',
    'ledger/general',
    'ledger/index',
    'ledger/student',
    'partials/report-student-status',
    'reports/attendance-student',
    'reports/attendance',
    'reports/collection',
    'reports/defaulters',
    'reports/discounts',
    'reports/financial',
    'reports/general',
    'reports/installments',
    'reports/print/attendance',
    'reports/print/collection',
    'reports/print/defaulters',
    'reports/print/discounts',
    'reports/print/general',
    'reports/print/installments',
    'reports/print/student',
    'reports/student-show',
    'reports/students',
    'results/by-student',
    'results/class',
    'results/marksheet',
    'students/exit-report',
];
foreach ($views as $view) {
    $compiler = new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
    $compiled = $compiler->compileString(file_get_contents($root.'/resources/views/'.$view.'.blade.php'));
    token_get_all($compiled, TOKEN_PARSE);
    echo "PASS: $view\n";
}
echo 'PASS: '.count($views)." real Blade compilation/PHP parse checks; not full HTTP/database rendering.\n";
