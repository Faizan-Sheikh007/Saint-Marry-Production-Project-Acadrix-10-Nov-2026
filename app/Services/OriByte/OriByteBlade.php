<?php

/**
 * ============================================================================
 * Acadrix Blade Directives
 * ============================================================================
 * Register these in your EMS AppServiceProvider::boot() method:
 *
 *   app/Providers/AppServiceProvider.php
 *
 *   public function boot(): void
 *   {
 *       \App\Services\OriByte\OriByteBlade::register();
 *   }
 *
 * Then in any EMS blade view:
 *
 *   @oribyteFeature('lms_module')
 *       <a href="/lms">LMS</a>
 *   @endoribyteFeature
 *
 *   @oribyteLimit('students', 500)
 *       {{-- Only renders if student limit > 500 --}}
 *   @endoribyteLimit
 *
 *   @oribyteExpiring
 *       <div class="alert">License expiring soon</div>
 *   @endoribyteExpiring
 * ============================================================================
 */

namespace App\Services\OriByte;

use Illuminate\Support\Facades\Blade;

class OriByteBlade
{
    public static function register(): void
    {
        // ── @oribyteFeature('flag_key') ... @endoribyteFeature ─────────────
        Blade::if('oribyteFeature', function (string $flagKey, bool $default = false): bool {
            try {
                /** @var \App\Services\OriByte\LicenseConnector $connector */
                $connector = app(LicenseConnector::class);
                return $connector->isFeatureEnabled($flagKey, $default);
            } catch (\Exception $e) {
                return $default;
            }
        });

        // ── @oribyteNotFeature('flag_key') ... @endoribyteNotFeature ───────
        Blade::if('oribyteNotFeature', function (string $flagKey): bool {
            try {
                return ! app(LicenseConnector::class)->isFeatureEnabled($flagKey);
            } catch (\Exception $e) {
                return true;
            }
        });

        // ── @oribyteLimit('resource', int) ... @endoribyteLimit ─────────────
        // Renders content only if the plan limit for $resource is >= $threshold
        Blade::if('oribyteLimit', function (string $resource, int $threshold = 1): bool {
            try {
                $result = app(LicenseConnector::class)->validate();
                $limits = $result['limits'] ?? [];
                $max    = $limits[$resource]['max'] ?? 0;
                return $max === -1 || $max >= $threshold; // -1 = unlimited
            } catch (\Exception $e) {
                return false;
            }
        });

        // ── @oribyteExpiring ... @endoribyteExpiring ────────────────────────
        // Renders content when license is expiring within 7 days or in grace period
        Blade::if('oribyteExpiring', function (): bool {
            try {
                $result = app(LicenseConnector::class)->validate();
                return ! empty($result['warning']) || ! empty($result['grace_period']);
            } catch (\Exception $e) {
                return false;
            }
        });

        // ── @oriBytePlan('plan_slug') ... @endoriBytePlan ───────────────────
        // Renders content only on the specified plan
        Blade::if('oriBytePlan', function (string ...$slugs): bool {
            try {
                $result = app(LicenseConnector::class)->validate();
                $plan   = $result['license']->plan->slug ?? null;
                return in_array($plan, $slugs);
            } catch (\Exception $e) {
                return false;
            }
        });
    }
}
