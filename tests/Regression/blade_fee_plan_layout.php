<?php

declare(strict_types=1);

/**
 * Compile the two fee-plan layout views with the installed Laravel Blade compiler,
 * then parse the resulting PHP. Requires the application's vendor directory.
 *
 * Run from the Laravel project root:
 *     php tests/Regression/blade_fee_plan_layout.php
 *
 * No application boot, database connection, or financial writes are performed.
 */

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$root = dirname(__DIR__, 2);
$autoload = $root.'/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Missing vendor/autoload.php. Run this check in the installed Laravel project.\n");
    exit(2);
}

require $autoload;

$views = [
    'resources/views/fee-assignments/create.blade.php',
    'resources/views/fee-assignments/renew.blade.php',
];

$failures = 0;
foreach ($views as $view) {
    try {
        $path = $root.'/'.$view;
        if (! is_file($path)) {
            throw new RuntimeException('File is missing.');
        }
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException('Unable to read the view.');
        }

        // compileString does not write to the cache directory or render the page.
        $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
        $compiled = $compiler->compileString($source);

        // Compilation alone can emit invalid PHP without throwing an exception.
        // TOKEN_PARSE checks the generated PHP without executing it.
        token_get_all($compiled, TOKEN_PARSE);
        fwrite(STDOUT, "PASS: {$view}\n");
    } catch (Throwable $exception) {
        $failures++;
        fwrite(STDERR, "FAIL: {$view}\n  ".$exception->getMessage()."\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "{$failures} view(s) failed compilation/PHP parsing.\n");
    exit(1);
}

fwrite(STDOUT, "PASS: both layout views compile and parse as PHP.\n");
fwrite(STDOUT, "This checks syntax only; it does not test live rendering or database operations.\n");
