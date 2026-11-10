<?php
/** Real Blade compilation/PHP parse checks; requires installed vendor, no DB. */
declare(strict_types=1);
$root=dirname(__DIR__,2);
if(!is_file($root.'/vendor/autoload.php')){fwrite(STDERR,"Missing vendor/autoload.php; run on the installed staging application.\n");exit(2);}
require $root.'/vendor/autoload.php';
$views=[
 'partials/student-status-badge','partials/student-status-banner','errors/student-locked',
 'layouts/app','students/_row','students/_panel','students/index','students/form','students/print',
 'accounts/_students-row','vouchers/_browse-panel','vouchers/index','guardian/dashboard',
 'teacher/student-fees','reports/students','classes/show','admin/reporting/student-search',
 'dashboard/_student-explorer-table','id-cards/verify',
];
foreach($views as $view){
 $compiler=new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem,sys_get_temp_dir());
 $compiled=$compiler->compileString(file_get_contents($root.'/resources/views/'.$view.'.blade.php'));
 token_get_all($compiled,TOKEN_PARSE);echo "PASS: $view\n";
}
echo 'PASS: '.count($views)." real Blade compilation/PHP parse checks; not HTTP/database rendering.\n";
