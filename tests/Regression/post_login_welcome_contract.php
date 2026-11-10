<?php
/**
 * Standalone regression checks for the one-time student/parent login splash.
 * Run from Laravel root with: php tests/Regression/post_login_welcome_contract.php
 * Does not need to boot Laravel or touch the database.
 */
$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/AuthController.php');
$loader = file_get_contents($root.'/resources/views/partials/boot-loader.blade.php');
$welcome = file_get_contents($root.'/resources/views/partials/portal-post-login-welcome.blade.php');

$checks = [
    'Flash flag is created on successful login' => str_contains($controller, "->session()->flash('portal_post_login_welcome', true)"),
    'Flash happens after successful session regeneration' =>
        strpos($controller, "->session()->regenerate();") < strpos($controller, "->session()->flash('portal_post_login_welcome', true)"),
    'Redirect to intended page is preserved' => str_contains($controller, 'redirect()->intended(route($user->dashboardRoute()))'),
    'Welcome shown only while signed into web guard' => str_contains($loader, "auth('web')->check()"),
    'Welcome restricted to parent/student roles' => str_contains($loader, "['student', 'parent']"),
    'Welcome partial included conditionally' => str_contains($loader, "@include('partials.portal-post-login-welcome')"),
    'Other screens use original boot loader' => str_contains($loader, '@else') && str_contains($loader, 'Preparing your workspace'),
    'Institution logo read from settings' => str_contains($welcome, "Setting::get('institution_logo')"),
    'Institution name read from settings' => str_contains($welcome, "Setting::get('institution_name')"),
    'Institution logo served from existing media helper' => str_contains($welcome, 'storage_asset($portalWelcomeInstitutionLogo)'),
    'Acadrix brand kept from existing local asset' => str_contains($welcome, "asset('logo.png')"),
    'Three-second on-screen hold' => str_contains($welcome, 'window.setTimeout(finishWelcome, 3000)'),
    'Progress bar synchronized to same three seconds' => str_contains($welcome, 'portal-welcome-fill 3s'),
    'Browser back cache does not replay splash' => str_contains($welcome, 'event.persisted'),
    'Responsive phone screen styles included' => str_contains($welcome, '@media (max-width: 620px)'),
    'Visible brand text matches reference' => str_contains($welcome, 'Parent &amp; Student Portal'),
];
$failed = 0;
foreach ($checks as $name => $pass) {
    echo ($pass ? "PASS" : "FAIL")." — {$name}\n";
    if (! $pass) $failed++;
}
if ($failed) { exit(1); }
echo "\nAll ".count($checks)." static checks passed. Live login test still required.\n";
