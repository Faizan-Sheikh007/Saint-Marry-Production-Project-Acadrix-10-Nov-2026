<?php

/**
 * ============================================================================
 * Acadrix Plan Limit Trait
 * ============================================================================
 * Use this trait in EMS controllers that create resources (teachers, students,
 * courses) to enforce plan limitations before persisting data.
 *
 * Usage in an EMS controller:
 *
 *   use App\Services\OriByte\PlanLimitTrait;
 *
 *   class TeacherController extends Controller
 *   {
 *       use PlanLimitTrait;
 *
 *       public function store(Request $request)
 *       {
 *           $this->enforcePlanLimit('teachers');
 *           // proceed with creation ...
 *       }
 *   }
 * ============================================================================
 */

namespace App\Services\OriByte;

use Illuminate\Http\Request;

trait PlanLimitTrait
{
    /**
     * Abort with a 403 if the resource limit has been reached.
     *
     * @param string $resource  teachers|students|courses|storage
     * @param int    $requested Number of new units to add
     */
    protected function enforcePlanLimit(string $resource, int $requested = 1): void
    {
        /** @var LicenseConnector $connector */
        $connector = app(LicenseConnector::class);

        if (! $connector->canAdd($resource, $requested)) {
            abort(403, "You have reached your plan's {$resource} limit. Please upgrade your Acadrix plan.");
        }
    }

    /**
     * Get the current usage data for all resources.
     * Returns null if the license check fails.
     */
    protected function getPlanLimits(): ?array
    {
        try {
            $result = app(LicenseConnector::class)->validate();
            return $result['limits'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Report updated usage counts after a create/delete operation.
     * Pass an associative array of resource => count pairs.
     *
     * Example: $this->reportUsage(['teachers' => 12, 'students' => 240]);
     */
    protected function reportUsage(array $usage): void
    {
        app(LicenseConnector::class)->reportUsage($usage);
    }
}
