<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Role middleware alias ──────────────────────────────────────────
        // Powers ->middleware('role:admin,accountant') etc. across both the
        // EMS side and the Accounts side of the unified app.
        $middleware->alias([
            'role'      => \App\Http\Middleware\RoleMiddleware::class,
            'staffgate' => \App\Http\Middleware\EnsureStaffGateVerified::class,
        ]);

        // ── OriByte license check on every web request ────────────────────
        // FIX 1: Was missing — nothing enforced license without this
        $middleware->web(append: [
            \App\Http\Middleware\CheckOriByteLicense::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnsureStudentIsActive::class,
            \App\Http\Middleware\ProtectInactiveStudent::class,
        ]);

        // ── Guard-aware guest redirect ─────────────────────────────────────
        // EMS and Accounts are two independently-authenticated systems now.
        // A guest hitting an /accounts/* URL is sent to the Accounts login;
        // everyone else goes to the EMS login. This is what makes it
        // impossible to "fall through" from one system to the other without
        // actually signing in on that system's own guard.
        $middleware->redirectGuestsTo(function ($request) {
            $isStaffArea = $request->is('accounts*') || $request->is('admin*') || $request->is('teacher*') || $request->is('staff*');
            return $isStaffArea ? route('staff.gate') : route('login');
        });

    })
    ->withSchedule(function (Schedule $schedule) {

        // ── Heartbeat every 5 min — keeps school "Online" in CP dashboard ──
        // FIX 9: Was missing — CP showed all schools as offline
        $schedule->call(function () {
            try {
                app(\App\Services\OriByte\LicenseConnector::class)->heartbeat();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::debug('[Acadrix] Heartbeat failed: ' . $e->getMessage());
            }
        })->everyFiveMinutes()->name('acadrix:heartbeat')->withoutOverlapping();

        // ── Usage sync every hour — keeps student/teacher counts accurate ──
        $schedule->call(function () {
            try {
                app(\App\Services\OriByte\LicenseConnector::class)->reportUsage([
                    'students' => \App\Models\Student::count(),
                    'vouchers' => \App\Models\Voucher::count(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::debug('[Acadrix] Usage sync failed: ' . $e->getMessage());
            }
        })->hourly()->name('acadrix:usage-sync')->withoutOverlapping();

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
