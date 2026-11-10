<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\InstallmentDateDefault;
use App\Models\Program;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InstallmentDateDefaultController extends Controller
{
    public function index(Request $request)
    {
        $academicYears = AcademicYear::with(['schoolClasses.program'])->ordered()->get();
        $programs = Program::orderBy('name')->get();
        $sessionStructure = $this->buildSessionStructure($academicYears);

        $defaults = InstallmentDateDefault::with(['academicYear', 'program'])
            ->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when($request->filled('program_id'), function ($q) use ($request) {
                if ($request->program_id === 'all') {
                    $q->whereNull('program_id');
                } else {
                    $q->where('program_id', $request->program_id);
                }
            })
            ->orderBy('academic_year_id')
            ->orderByRaw('program_id IS NOT NULL')
            ->orderBy('program_id')
            ->orderBy('cycle_no')
            ->get();

        return view('installment-date-defaults.index', compact(
            'academicYears', 'programs', 'defaults', 'sessionStructure'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'program_id'       => 'nullable|exists:programs,id',
            'schedules'        => 'required|array|min:1|max:30',
            'schedules.*.cycle_no' => 'required|integer|min:1|max:30',
            'schedules.*.dates'    => 'required|array|min:1|max:12',
            'schedules.*.dates.*'  => 'required|date|distinct',
        ]);

        $seenCycles = [];
        foreach ($validated['schedules'] as $i => $schedule) {
            $cycle = (int) $schedule['cycle_no'];
            if (isset($seenCycles[$cycle])) {
                throw ValidationException::withMessages([
                    "schedules.$i.cycle_no" => "Semester/Part {$cycle} is listed more than once in this save.",
                ]);
            }
            $seenCycles[$cycle] = true;
            $this->assertLinkedScope(
                (int) $validated['academic_year_id'],
                isset($validated['program_id']) ? (int) $validated['program_id'] : null,
                $cycle,
                "schedules.$i.cycle_no"
            );
        }

        $saved = 0;
        foreach ($validated['schedules'] as $schedule) {
            $dates = $this->normalizeDates($schedule['dates']);

            $default = InstallmentDateDefault::updateOrCreate([
                'academic_year_id' => $validated['academic_year_id'],
                'program_id'       => $validated['program_id'] ?? null,
                'cycle_no'         => (int) $schedule['cycle_no'],
            ], [
                'dates' => $dates,
            ]);

            AuditLog::record(
                $default->wasRecentlyCreated ? 'created' : 'updated',
                'InstallmentDateDefault',
                $default->id,
                'Saved default installment dates for session #' . $validated['academic_year_id'] .
                ', ' . (($validated['program_id'] ?? null) ? 'program #' . $validated['program_id'] : 'all linked programs') .
                ', cycle ' . $schedule['cycle_no']
            );
            $saved++;
        }

        return redirect()->route('installment-date-defaults.index', ['academic_year_id' => $validated['academic_year_id']])
            ->with('success', "Saved {$saved} session-linked default installment schedule(s).");
    }

    public function edit(InstallmentDateDefault $installmentDateDefault)
    {
        $academicYears = AcademicYear::with(['schoolClasses.program'])->ordered()->get();
        $programs = Program::orderBy('name')->get();
        $sessionStructure = $this->buildSessionStructure($academicYears);
        $installmentDateDefault->load(['academicYear', 'program']);

        return view('installment-date-defaults.edit', compact(
            'installmentDateDefault', 'academicYears', 'programs', 'sessionStructure'
        ));
    }

    public function update(Request $request, InstallmentDateDefault $installmentDateDefault)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'program_id'       => 'nullable|exists:programs,id',
            'cycle_no'         => 'required|integer|min:1|max:30',
            'dates'            => 'required|array|min:1|max:12',
            'dates.*'          => 'required|date|distinct',
        ]);

        $this->assertLinkedScope(
            (int) $validated['academic_year_id'],
            isset($validated['program_id']) ? (int) $validated['program_id'] : null,
            (int) $validated['cycle_no'],
            'cycle_no'
        );

        $conflict = InstallmentDateDefault::query()
            ->whereKeyNot($installmentDateDefault->id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('cycle_no', $validated['cycle_no'])
            ->when(
                isset($validated['program_id']) && $validated['program_id'] !== null,
                fn ($q) => $q->where('program_id', $validated['program_id']),
                fn ($q) => $q->whereNull('program_id')
            )
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'cycle_no' => 'A default schedule already exists for this session, program scope, and semester/part.',
            ]);
        }

        $installmentDateDefault->update([
            'academic_year_id' => $validated['academic_year_id'],
            'program_id'       => $validated['program_id'] ?? null,
            'cycle_no'         => (int) $validated['cycle_no'],
            'dates'            => $this->normalizeDates($validated['dates']),
        ]);

        AuditLog::record('updated', 'InstallmentDateDefault', $installmentDateDefault->id,
            'Updated session-linked default installment dates for cycle ' . $installmentDateDefault->cycle_no);

        return redirect()->route('installment-date-defaults.index', ['academic_year_id' => $installmentDateDefault->academic_year_id])
            ->with('success', 'Default installment dates updated. Existing students can still have their own dates adjusted separately.');
    }

    public function destroy(InstallmentDateDefault $installmentDateDefault)
    {
        $description = 'Deleted default installment dates for session #' . $installmentDateDefault->academic_year_id .
            ', cycle ' . $installmentDateDefault->cycle_no;
        $id = $installmentDateDefault->id;
        $installmentDateDefault->delete();

        AuditLog::record('deleted', 'InstallmentDateDefault', $id, $description);

        return back()->with('success', 'Default installment schedule removed.');
    }

    /**
     * Ensure Accounts/Admin can only configure dates against the academic
     * structure defined by Admin. With no program selected, the term must be
     * offered by at least one Program in the session. With a Program selected,
     * that exact Program + term must be linked to the session.
     */
    private function assertLinkedScope(int $academicYearId, ?int $programId, int $cycleNo, string $field): void
    {
        $classes = SchoolClass::query()
            ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $academicYearId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->get(['id', 'program_id', 'name']);

        if ($programId && $classes->isEmpty()) {
            throw ValidationException::withMessages([
                'program_id' => 'This Program is not linked to the selected Academic Session.',
            ]);
        }

        $allowed = $classes->contains(fn ($class) => $class->term_number === $cycleNo);
        if (!$allowed) {
            throw ValidationException::withMessages([
                $field => 'This Semester / Part is not linked to the selected Academic Session' . ($programId ? ' and Program.' : '.'),
            ]);
        }
    }

    /**
     * JSON-ready structure used by both Admin and Accounts UI:
     * Session -> linked Programs -> linked Parts/Semesters.
     */
    private function buildSessionStructure($academicYears): array
    {
        $structure = [];

        foreach ($academicYears as $year) {
            $classes = $year->schoolClasses
                ->filter(fn ($class) => $class->program)
                ->sortBy(fn ($class) => [$class->program->name, $class->term_number ?? 999, $class->section, $class->name])
                ->values();

            $programs = $classes->groupBy('program_id')->map(function ($programClasses) {
                $program = $programClasses->first()->program;
                $terms = $programClasses
                    ->filter(fn ($class) => $class->term_number !== null)
                    ->groupBy(fn ($class) => $class->term_number)
                    ->map(function ($sameNumber, $cycleNo) {
                        return [
                            'cycle_no' => (int) $cycleNo,
                            'label'    => $sameNumber->pluck('name')->unique()->implode(' / '),
                        ];
                    })
                    ->sortBy('cycle_no')
                    ->values()
                    ->all();

                return [
                    'id'    => $program->id,
                    'name'  => $program->name,
                    'terms' => $terms,
                ];
            })->sortBy('name')->values()->all();

            $allTerms = $classes
                ->filter(fn ($class) => $class->term_number !== null)
                ->groupBy(fn ($class) => $class->term_number)
                ->map(function ($sameNumber, $cycleNo) {
                    return [
                        'cycle_no' => (int) $cycleNo,
                        'label'    => $sameNumber->pluck('name')->unique()->implode(' / '),
                    ];
                })
                ->sortBy('cycle_no')
                ->values()
                ->all();

            $structure[(string) $year->id] = [
                'session_name' => $year->name,
                'programs'     => $programs,
                'all_terms'    => $allTerms,
            ];
        }

        return $structure;
    }

    private function normalizeDates(array $dates): array
    {
        return collect($dates)
            ->filter()
            ->map(fn ($date) => date('Y-m-d', strtotime($date)))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
