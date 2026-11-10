<?php

/**
 * ============================================================================
 * Acadrix EMS — CheckOriByteLicense Middleware v2
 * ============================================================================
 * Add to your EMS kernel's $middlewareGroups['web']:
 *   \App\Http\Middleware\CheckOriByteLicense::class
 *
 * What it does:
 *  1. Fast-path: checks local block file (set on 403 from SaaS API)
 *  2. Reads license state from the SaaS dashboard (cached 5 min)
 *  3. Shows lock screen if trial ended OR license expired/suspended
 *  4. Injects plan limits + feature flags into every request
 *  5. Flashes warnings for expiring / announcement / update banners
 * ============================================================================
 */

namespace App\Http\Middleware;

use App\Services\OriByte\LicenseConnector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CheckOriByteLicense
{
    // Routes that bypass the license check entirely
    private const BYPASS_ROUTES = [
        'oribyte.*',
        'login', 'login.*',
        'logout',
        'staff.*',
        'accounts.logout',
        'password.*',
        'register',
        'students.id-card.verify',
        'media.show',
    ];

    public function __construct(private LicenseConnector $connector) {}

    public function handle(Request $request, Closure $next)
    {
        // ── Skip entirely on local development ─────────────────────────────
        // Acardix Accounts is a standalone product now; don't block local
        // dev work behind a network call to the (separate) Acadrix SaaS API.
        if (app()->environment('local')) {
            return $next($request);
        }

        // ── Always allow bypass routes ────────────────────────────────────
        foreach (self::BYPASS_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // ── Fast-path: local block file (written on 403) ──────────────────
        $blockFile = storage_path('app/acadrix_block.lock');
        if (file_exists($blockFile)) {
            $blockData = json_decode(file_get_contents($blockFile), true) ?? [];
            return $this->showLockScreen($request, 'suspended', $blockData);
        }

        // ── Validate license ──────────────────────────────────────────────
        try {
            $validation = $this->connector->validate();
        } catch (\RuntimeException $e) {
            // Connector throws RuntimeException when license is definitively invalid
            return $this->showLockScreen($request, 'invalid', ['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            // Network errors → fail open (don't block users on connectivity issues)
            return $next($request);
        }

        $valid          = $validation['valid'] ?? false;
        $licenseStatus  = $validation['license_status'] ?? 'unknown';
        $isTrial        = ($validation['type'] ?? '') === 'trial';
        $expiresAt      = $validation['expires_at'] ?? null;
        $daysUntilExpiry = $validation['days_until_expiry'] ?? null;

        // ── Block if invalid ──────────────────────────────────────────────
        if (! $valid) {
            return $this->showLockScreen($request, $licenseStatus, $validation);
        }

        // ── Flash banners for valid licenses ──────────────────────────────

        // Expiry warning (≤ 7 days left)
        if ($daysUntilExpiry !== null && $daysUntilExpiry <= 7 && $daysUntilExpiry >= 0) {
            $msg = $isTrial
                ? "Your free trial ends in {$daysUntilExpiry} day(s). Purchase a plan to continue."
                : "Your license expires in {$daysUntilExpiry} day(s). Renew to avoid interruption.";
            session()->flash('license_warning', $msg);
        }

        // Announcement
        if ($ann = ($validation['announcement'] ?? null)) {
            session()->flash('acadrix_announcement', $ann);
        }

        // Mandatory / optional update
        if ($upd = ($validation['update'] ?? null)) {
            if ($upd['update_available'] ?? false) {
                $key = ($upd['mandatory'] ?? false) ? 'acadrix_mandatory_update' : 'acadrix_update_available';
                session()->flash($key, $upd);
            }
        }

        // ── Inject limits + flags into request & views ────────────────────
        $limits = $validation['limits'] ?? [];
        $flags  = $validation['feature_flags'] ?? [];

        $request->merge([
            '_acadrix_limits'        => $limits,
            '_acadrix_feature_flags' => $flags,
            '_acadrix_valid'         => true,
            '_acadrix_trial'         => $isTrial,
            '_acadrix_expires_at'    => $expiresAt,
            '_acadrix_days_left'     => $daysUntilExpiry,
        ]);

        View::share('_acadrixFlags',  $flags);
        View::share('_acadrixLimits', $limits);
        View::share('_acadrixTrial',  $isTrial);
        View::share('_acadrixExpiry', $expiresAt);

        return $next($request);
    }

    private function showLockScreen(Request $request, string $status, array $data): \Illuminate\Http\Response
    {
        // Fetch available plans from SaaS to show on lock screen
        $plans = [];
        try {
            $plans = $this->connector->getAvailablePlans();
        } catch (\Exception $e) {
            // Silently fail — lock screen still shows without plans
        }

        $isTrial       = ($data['type'] ?? $status) === 'trial' || $status === 'trial_expired';
        $expiredAt     = isset($data['expires_at']) ? date('F j, Y', strtotime($data['expires_at'])) : null;
        $daysOverdue   = 0;
        if (isset($data['expires_at'])) {
            $daysOverdue = max(0, (int) now()->diffInDays($data['expires_at'], false) * -1);
        }

        return response(view('license-expired', [
            'is_trial'       => $isTrial,
            'license_status' => $status,
            'plan_name'      => $data['plan']['name'] ?? config('oribyte_client.app_name', 'Acadrix EMS'),
            'expired_at'     => $expiredAt,
            'days_overdue'   => $daysOverdue,
            'plans'          => $plans,
            'message'        => $data['message'] ?? null,
        ]), 402);
    }
}
