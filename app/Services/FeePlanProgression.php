<?php

namespace App\Services;

use App\Models\FeeAssignment;
use App\Models\FeeTemplate;
use App\Models\Student;
use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

/**
 * One student, one chronological fee-plan sequence, independent of template.
 * Existing plans (including legacy duplicates) are never rewritten here.
 */
class FeePlanProgression
{
    public function forStudent(Student $student, ?FeeTemplate $template = null): array
    {
        $program = $student->program ?: $student->schoolClass?->program;
        $programCycles = $program ? ($program->actualTermCount() ?: $program->duration_terms) : null;
        $plans = FeeAssignment::where('student_id', $student->id)
            ->select(['id', 'cycle_start', 'cycle_end', 'first_due_date'])
            ->withMax('vouchers', 'due_date')
            ->orderBy('cycle_end')->orderBy('id')->get();

        // The student's program, not a newly selected template, determines
        // the program length. A template is only a fallback without a program.
        $totalCycles = (int) ($programCycles ?: $template?->total_cycles ?: 0);
        if (!$totalCycles && !$template) {
            $totalCycles = (int) FeeTemplate::whereIn('id',
                FeeAssignment::where('student_id', $student->id)->select('fee_template_id')
            )->max('total_cycles');
        }

        $state = self::fromPlans($plans->map(fn ($plan) => [
            'id' => (int) $plan->id,
            'cycle_start' => (int) $plan->cycle_start,
            'cycle_end' => (int) $plan->cycle_end,
            'last_due_date' => $plan->vouchers_max_due_date
                ?: $plan->first_due_date?->toDateString(),
        ])->all(), (int) ($student->schoolClass?->term_number ?: 1), $totalCycles ?: null);

        $state['cycle_label'] = $program
            ? ($program->system_type === 'annual' ? 'Year' : 'Semester')
            : ($template?->cycleLabel() ?? 'Semester / Year');

        return $state;
    }

    /** Pure policy, also used by the dependency-free regression tests. */
    public static function fromPlans(array $plans, int $enrolledCycle = 1, ?int $totalCycles = null): array
    {
        $covered = [];
        $lastCycle = 0;
        $lastDueDate = null;
        $previousId = null;

        foreach ($plans as $plan) {
            $start = max(1, (int) ($plan['cycle_start'] ?? 1));
            $end = max($start, (int) ($plan['cycle_end'] ?? $start));
            $id = (int) $plan['id'];
            for ($cycle = $start; $cycle <= $end; $cycle++) {
                $covered[$cycle][] = $id;
            }
            if ($end > $lastCycle || ($end === $lastCycle && $id > (int) $previousId)) {
                $lastCycle = $end;
                $previousId = $id;
            }
            $date = self::isoDate($plan['last_due_date'] ?? null);
            if ($date && (!$lastDueDate || $date > $lastDueDate)) {
                $lastDueDate = $date;
            }
        }

        ksort($covered);
        // Enrollment is relevant only for the FIRST plan. Once any plan
        // exists, the last covered cycle wins even if enrollment is still 1.
        $next = $lastCycle > 0 ? $lastCycle + 1 : max(1, $enrolledCycle);
        $minimumDate = $lastDueDate
            ? (new DateTimeImmutable($lastDueDate))->modify('first day of next month')->format('Y-m-d')
            : null;

        return [
            'next_cycle' => $next,
            'last_covered_cycle' => $lastCycle,
            'covered_cycles' => array_keys($covered),
            'duplicate_cycles' => array_filter($covered, fn ($ids) => count($ids) > 1),
            'previous_assignment_id' => $previousId,
            'last_due_date' => $lastDueDate,
            'minimum_date' => $minimumDate,
            'total_cycles' => $totalCycles,
            'is_complete' => $totalCycles !== null && $next > $totalCycles,
            'remaining_cycles' => $totalCycles !== null ? max(0, $totalCycles - $next + 1) : null,
        ];
    }

    /** Retain installment indexes; never shift a rejected default into another slot. */
    public static function allowedDefaults(array $defaults, ?string $minimumDate): array
    {
        foreach ($defaults as $cycle => $dates) {
            $defaults[$cycle] = array_map(function ($date) use ($minimumDate) {
                $iso = self::isoDate($date);
                return $iso && (!$minimumDate || $iso >= $minimumDate) ? $iso : null;
            }, $dates);
        }
        return $defaults;
    }

    public static function cycleErrors(array $state, int $requestedCycle): array
    {
        $label = $state['cycle_label'] ?? 'Semester / Year';
        if ($state['is_complete']) {
            return ['cycle_no' => "All scheduled semesters/years already have fee plans through {$label} {$state['last_covered_cycle']}. No additional plan was created."];
        }
        if ($requestedCycle !== $state['next_cycle']) {
            return ['cycle_no' => "This form is for {$label} {$requestedCycle}, but the next available plan is {$label} {$state['next_cycle']}. Reload the fee-plan form before saving. Existing plans were not changed."];
        }
        return [];
    }

    /** Validate the effective calendar, including any server-loaded defaults. */
    public static function dateErrors(array $dates, int $cycle, int $parts, ?string $minimumDate): array
    {
        $errors = [];
        $previous = null;
        for ($index = 0; $index < $parts; $index++) {
            $date = self::isoDate($dates[$cycle][$index] ?? null);
            $part = $index + 1;
            $key = "installment_dates.{$cycle}.{$index}";
            if (!$date) {
                $errors[$key] = "Choose a valid due date for semester/year {$cycle}, installment {$part}.";
            } elseif ($minimumDate && $date < $minimumDate) {
                $formatted = (new DateTimeImmutable($minimumDate))->format('d/m/Y');
                $errors[$key] = "Installment {$part} must be dated {$formatted} or later. Earlier months belong to previous semester fee plans.";
            } elseif ($previous && $date < $previous) {
                $errors[$key] = "Installment {$part} must be on or after the preceding installment's date.";
            }
            if ($date) $previous = $date;
        }
        return $errors;
    }

    /**
     * Call again AFTER taking the student row lock, in the same transaction
     * that inserts the plan and vouchers. The posted cycle is an optimistic
     * token: a double submit must fail, never quietly create another semester.
     */
    public function assertNextCycle(Student $student, FeeTemplate $template, int $requestedCycle): array
    {
        $state = $this->forStudent($student, $template);
        $errors = self::cycleErrors($state, $requestedCycle);
        if ($errors) throw ValidationException::withMessages($errors);
        return $state;
    }

    public function assertDates(array $dates, int $cycle, int $parts, ?string $minimumDate): void
    {
        $errors = self::dateErrors($dates, $cycle, $parts, $minimumDate);
        if ($errors) throw ValidationException::withMessages($errors);
    }

    private static function isoDate(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
