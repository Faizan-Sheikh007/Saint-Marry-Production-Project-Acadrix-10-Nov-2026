<?php
/** Run from the Laravel project root: php tests/Regression/login_after_splash_contract.php */
$root = dirname(__DIR__, 2);
$login = file_get_contents($root.'/resources/views/auth/login.blade.php');
$welcome = file_get_contents($root.'/resources/views/partials/institution-login-welcome.blade.php');
$controller = file_get_contents($root.'/app/Http/Controllers/AuthController.php');
$loader = file_get_contents($root.'/resources/views/partials/boot-loader.blade.php');
$tests = [
    'Institution values come from existing settings' => str_contains($login, "Setting::get('institution_logo')") && str_contains($login, "Setting::get('institution_name')"),
    'Existing entry splash is intact' => str_contains($login, 'id="entry-splash"'),
    'New welcome occurs before login shell' => strpos($login, "@include('partials.institution-login-welcome')") < strpos($login, 'class="portal-shell"'),
    'New welcome includes the institution logo helper' => str_contains($welcome, 'storage_asset($loginInstitutionLogo)'),
    'Logo has configured institution name' => str_contains($welcome, '$loginInstitutionName'),
    'Acadrix uses local logo asset' => str_contains($welcome, "asset('logo.png')"),
    'Only one welcome overlay ID' => substr_count($welcome, 'id="institution-login-welcome"') === 1,
    'Welcome overlay has active visibility class' => str_contains($welcome, '#institution-login-welcome.active'),
    'Existing entry splash remains 5 seconds' => str_contains($login, 'setTimeout(showInstitutionWelcome, 5000)'),
    'Welcome stays for 3 seconds' => str_contains($login, 'setTimeout(finishIntro, 3000)'),
    'Progress bar moves for 3 seconds' => str_contains($welcome, 'portal-welcome-fill 3s'),
    'Login form only appears after welcome' => str_contains($login, "shell.classList.add('revealed')") && str_contains($login, 'function finishIntro'),
    'Login validation errors skip intro' => str_contains($login, '$errors->any()'),
    'Old wrong post-login flash removed' => !str_contains($controller, 'portal_post_login_welcome'),
    'Standard loader remains for dashboard' => !str_contains($loader, 'portal_post_login_welcome'),
    'Normal authentication redirect remains' => str_contains($controller, 'redirect()->intended(route($user->dashboardRoute()))'),
];
$failed = 0;
foreach ($tests as $label => $ok) { echo ($ok ? 'PASS' : 'FAIL')." — {$label}\n"; $failed += (int) !$ok; }
if ($failed) exit(1);
echo 'All '.count($tests)." static regression checks passed.\n";
