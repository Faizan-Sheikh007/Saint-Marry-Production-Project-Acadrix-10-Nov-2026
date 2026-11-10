<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeAssignment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Voucher;
use Illuminate\Support\Collection;

/**
 * Read-only selection of challans already issued under students' saved fee plans.
 * Never calculates a new amount, due date, voucher number or fee-plan installment.
 */
class BulkInstallmentVoucherSelector
{
    /** Check the complete Session > Program > Semester cascade server-side. */
    public function scope(int $yearId, int $programId, int $classId): array
    {
        $year = AcademicYear::findOrFail($yearId);
        $class = SchoolClass::query()
            ->where('program_id', $programId)
            ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $yearId))
            ->with('program')
            ->findOrFail($classId);

        $cycle = (int) $class->term_number;
        abort_if($cycle < 1, 422, 'The selected semester/part has no identifiable semester number.');

        return compact('year', 'class', 'cycle', 'yearId', 'programId', 'classId');
    }

    /** The cohort is the exact class within the selected academic session. */
    private function students(array $scope)
    {
        return Student::query()
            ->where('academic_year_id', $scope['yearId'])
            ->where('program_id', $scope['programId'])
            ->where('school_class_id', $scope['classId']);
    }

    /**
     * Resolve the installment number *within the selected semester*, not
     * the plan-wide sequence. Null means the voucher cannot safely be
     * associated with that semester; we never guess across semesters.
     *
     * For current plans, cycle_no + part_no are authoritative. Older
     * single-cycle plans may only have installment_no. Multi-cycle legacy
     * rows without cycle_no are deliberately excluded as ambiguous.
     */
    public static function slotInCycle(
        ?int $voucherCycle, ?int $voucherPart, ?int $voucherInstallment,
        int $planCycleStart, int $planCycleEnd, int $planPartsPerCycle, int $requestedCycle
    ): ?int {
        if ($requestedCycle < $planCycleStart || $requestedCycle > $planCycleEnd) return null;
        if ($voucherCycle !== null && $voucherCycle !== $requestedCycle) return null;
        if ($voucherCycle === null && $planCycleStart !== $planCycleEnd) return null;

        if ($voucherPart !== null) return $voucherPart > 0 ? $voucherPart : null;
        if ($voucherInstallment === null || $voucherInstallment < 1) return null;

        if ($voucherCycle === null) return $voucherInstallment;

        $perCycle = max(1, $planPartsPerCycle);
        $part = $voucherInstallment - (($requestedCycle - $planCycleStart) * $perCycle);
        return $part >= 1 && $part <= $perCycle ? $part : null;
    }

    private function slot(Voucher $voucher, int $cycle): ?int
    {
        $plan = $voucher->feeAssignment;
        if (! $plan || (int) $plan->student_id !== (int) $voucher->student_id) return null;

        return self::slotInCycle(
            $voucher->cycle_no === null ? null : (int) $voucher->cycle_no,
            $voucher->part_no === null ? null : (int) $voucher->part_no,
            $voucher->installment_no === null ? null : (int) $voucher->installment_no,
            (int) $plan->cycle_start,
            (int) $plan->cycle_end,
            (int) ($plan->parts_per_cycle ?: $plan->installments),
            $cycle
        );
    }

    /**
     * Match the student directory's semester ownership rule: when legacy
     * data has multiple plans for the same semester, the highest cycle_start
     * wins (and newest ID on ties). Superseded plans do not print twice.
     */
    private function owningPlans(array $scope): Collection
    {
        return FeeAssignment::query()
            ->whereIn('student_id', $this->students($scope)->select('id'))
            ->where('cycle_start', '<=', $scope['cycle'])
            ->where('cycle_end', '>=', $scope['cycle'])
            ->orderByDesc('cycle_start')
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->values();
    }

    /** Collect actual fee-plan rows only, excluding cancelled challans. */
    private function savedRows(array $scope, Collection $owningPlans): Collection
    {
        if ($owningPlans->isEmpty()) return collect();

        return Voucher::query()
            ->whereIn('fee_assignment_id', $owningPlans->pluck('id'))
            ->whereIn('student_id', $this->students($scope)->select('id'))
            ->whereNotNull('fee_assignment_id')
            ->where('status', '!=', 'cancelled')
            ->whereHas('feeAssignment', fn ($q) => $q
                ->where('cycle_start', '<=', $scope['cycle'])
                ->where('cycle_end', '>=', $scope['cycle']))
            ->with('feeAssignment')
            ->orderBy('student_id')->orderBy('installment_no')->orderBy('id')
            ->get()
            ->filter(fn (Voucher $v) => $this->slot($v, $scope['cycle']) !== null)
            ->values();
    }

    /** Max comes from each student's stored plan, not a global setting. */
    public function options(array $scope): array
    {
        $plans = $this->owningPlans($scope);
        $rows = $this->savedRows($scope, $plans);
        $maxPlan = (int) $plans->max(fn (FeeAssignment $plan) => (int) ($plan->parts_per_cycle ?: $plan->installments));
        $maxSavedSlot = (int) $rows->max(fn (Voucher $v) => $this->slot($v, $scope['cycle']));
        $max = min(255, max($maxPlan, $maxSavedSlot));

        $installments = [];
        for ($part = 1; $part <= $max; $part++) {
            $matching = $rows->filter(fn (Voucher $v) => $this->slot($v, $scope['cycle']) === $part);
            $installments[] = [
                'number' => $part,
                'student_count' => $matching->unique('student_id')->count(),
                'voucher_count' => $matching->unique(fn (Voucher $v) => $v->student_id . '|' . $v->voucher_no)->count(),
            ];
        }

        return [
            'max_installments' => $max,
            'total_students' => $this->students($scope)->count(),
            'students_with_plans' => $plans->unique('student_id')->count(),
            'installments' => $installments,
        ];
    }

    /** Existing challans only: one print per unique student + voucher number. */
    public function vouchers(array $scope, int $installmentNo): Collection
    {
        return $this->savedRows($scope, $this->owningPlans($scope))
            ->filter(fn (Voucher $v) => $this->slot($v, $scope['cycle']) === $installmentNo)
            ->unique(fn (Voucher $v) => $v->student_id . '|' . $v->voucher_no)
            ->values();
    }
}
