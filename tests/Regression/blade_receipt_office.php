<?php
/** Real Blade compilation/PHP parse check for the installed Laravel project.
 * No database queries or application bootstrap; does not test HTTP/middleware.
 * Run: php tests/Regression/blade_receipt_office.php
 */
declare(strict_types=1);
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
$root = dirname(__DIR__, 2);
if (!is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Missing vendor/autoload.php. Run this check on the installed staging project.\n");
    exit(2);
}
require $root.'/vendor/autoload.php';
$views = [
    'vouchers/partials/modern-challan', 'vouchers/partials/modern-challan-styles',
    'payments/extra-receipt', 'payments/extra-payment-voucher',
    'payments/partials/extra-payment-document', 'payments/extra-receipts',
    'accounts/students-show', 'partials/student-credit',
    'payments/received-payment-receipt', 'fee-assignments/summary', 'vouchers/receipt',
];
foreach ($views as $view) {
    $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
    $compiled = $compiler->compileString(file_get_contents($root.'/resources/views/'.$view.'.blade.php'));
    token_get_all($compiled, TOKEN_PARSE);
    echo "PASS: $view\n";
}
echo "PASS: ".count($views)." views compile with real Blade and parse as PHP. No live rendering or database checks.\n";
