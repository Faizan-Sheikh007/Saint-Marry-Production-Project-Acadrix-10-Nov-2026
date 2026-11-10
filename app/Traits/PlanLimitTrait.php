<?php

/**
 * ============================================================================
 * Acadrix PlanLimitTrait v2
 * ============================================================================
 * Add to any controller that creates/deletes students, teachers, or courses.
 *
 * Usage:
 *   use App\Traits\PlanLimitTrait;
 *   class StudentController extends Controller {
 *       use PlanLimitTrait;
 *
 *       public function store(Request $request) {
 *           $this->enforcePlanLimit('students');  // throws 403 if at limit
 *           // ... create student
 *           $this->syncUsage();
 *       }
 *   }
 * ============================================================================
 */

namespace App\Traits;

use App\Services\OriByte\LicenseConnector;

trait PlanLimitTrait
{
    /**
     * Abort with 403 if the given resource is at or over its plan limit.
     *
     * @param  string  $resource  One of: students, teachers, courses, storage
     * @param  int     $requested Number of new units being added (default 1)
     */
    protected function enforcePlanLimit(string $resource, int $requested = 1): void
    {
        $connector = app(LicenseConnector::class);

        if (! $connector->canAdd($resource, $requested)) {
            $limits = $connector->validate()['limits'][$resource] ?? [];

            abort(403, sprintf(
                'Plan limit reached for %s. You have used %d of %d. Please upgrade your plan.',
                $resource,
                $limits['used'] ?? '?',
                $limits['max'] ?? '?'
            ));
        }
    }

    /**
     * Soft check — returns false instead of aborting. Use for conditional UI.
     */
    protected function withinPlanLimit(string $resource, int $requested = 1): bool
    {
        try {
            return app(LicenseConnector::class)->canAdd($resource, $requested);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all plan limits for the current installation.
     *
     * Returns: ['students' => ['used' => 45, 'max' => 100], ...]
     */
    protected function getPlanLimits(): array
    {
        try {
            return app(LicenseConnector::class)->validate()['limits'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the current limits as a percentage for a given resource.
     */
    protected function usagePercent(string $resource): int
    {
        $limits = $this->getPlanLimits();
        $limit  = $limits[$resource] ?? null;

        if (! $limit || $limit['max'] <= 0 || $limit['max'] === -1) {
            return 0;
        }

        return (int) min(100, round(($limit['used'] / $limit['max']) * 100));
    }

    /**
     * Check whether a feature flag is enabled for this installation.
     */
    protected function featureEnabled(string $flagKey, bool $default = false): bool
    {
        return app(LicenseConnector::class)->isFeatureEnabled($flagKey, $default);
    }

    /**
     * Abort if a feature flag is disabled.
     */
    protected function requireFeature(string $flagKey): void
    {
        if (! $this->featureEnabled($flagKey)) {
            abort(403, "The feature '{$flagKey}' is not enabled on your plan. Please upgrade to access this feature.");
        }
    }

    /**
     * Push the latest resource counts to the SaaS dashboard.
     * Call this after bulk operations (imports, deletions, etc).
     */
    protected function syncUsage(): void
    {
        try {
            $connector = app(LicenseConnector::class);

            $connector->reportUsage([
                'students'   => \App\Models\Student::count(),
                'vouchers'   => \App\Models\Voucher::count(),
                'storage_gb' => $this->calculateStorageUsage(),
            ]);
        } catch (\Exception $e) {
            // Non-fatal — log but don't break the user's request
            \Illuminate\Support\Facades\Log::warning('[Acadrix] Usage sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Calculate disk usage in GB for the application storage directory.
     */
    private function calculateStorageUsage(): float
    {
        try {
            $bytes = 0;
            $path  = storage_path('app/public');

            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    $bytes += $file->getSize();
                }
            }

            return round($bytes / 1073741824, 3); // Convert bytes to GB
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
