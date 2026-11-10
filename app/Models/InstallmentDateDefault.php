<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentDateDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'program_id',
        'cycle_no',
        'dates',
    ];

    protected $casts = [
        'dates' => 'array',
        'cycle_no' => 'integer',
        'academic_year_id' => 'integer',
        'program_id' => 'integer',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Return Academic Session ids that can legitimately represent this
     * student's saved Session. The stored FK remains first priority; the
     * visible Session name supplies compatibility candidates after it. This also
     * handles older databases that accidentally contain duplicate Academic
     * Session rows with the same displayed name.
     *
     * @return array<int, int>
     */
    public static function academicYearIdsForStudent(Student $student): array
    {
        $ids = [];
        $push = static function ($id) use (&$ids): void {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        };

        // The FK is the source of truth for newer admissions. If that row was
        // recreated/duplicated later, the visible Session-name candidates below
        // provide a safe fallback only when the exact FK has no matching default.
        if ($student->academic_year_id) {
            $push($student->academic_year_id);
        }

        $sessionName = trim((string) ($student->session ?? ''));
        if ($sessionName !== '') {
            $normalized = static::normalizeSessionName($sessionName);

            // Do the comparison in PHP so dash variants, spaces, 2023-27 vs
            // 2023-2027, and duplicate rows with the same display name are all
            // handled consistently.
            AcademicYear::query()
                ->get(['id', 'name'])
                ->filter(fn ($year) => static::normalizeSessionName($year->name) === $normalized)
                ->each(fn ($year) => $push($year->id));
        }

        // Legacy fallback only when there is no usable Session/FK. A Semester
        // can be linked to many Sessions over time, so never guess between
        // multiple linked Sessions.
        if (!$ids && $student->school_class_id) {
            $linkedIds = AcademicYear::whereHas('schoolClasses', function ($q) use ($student) {
                    $q->where('school_classes.id', $student->school_class_id);
                })
                ->pluck('academic_years.id')
                ->unique()
                ->values();

            if ($linkedIds->count() === 1) {
                $push($linkedIds->first());
            }
        }

        return $ids;
    }

    /**
     * Backwards-compatible single-id helper used by any existing callers.
     */
    public static function academicYearIdForStudent(Student $student): ?int
    {
        return static::academicYearIdsForStudent($student)[0] ?? null;
    }

    /**
     * Return Program ids that can represent this student's enrolled Program.
     * The Semester/Part's Program is strongest because the fee plan is being
     * generated for that exact enrollment; student.program_id is retained as
     * a compatibility fallback for older records.
     *
     * @return array<int, int>
     */
    public static function programIdsForStudent(Student $student): array
    {
        $ids = [];
        $push = static function ($id) use (&$ids): void {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        };

        if ($student->school_class_id) {
            $schoolClassProgramId = $student->relationLoaded('schoolClass')
                ? $student->schoolClass?->program_id
                : $student->schoolClass()->value('program_id');

            if ($schoolClassProgramId) {
                $push($schoolClassProgramId);
            }
        }

        if ($student->program_id) {
            $push($student->program_id);
        }

        return $ids;
    }

    public static function programIdForStudent(Student $student): ?int
    {
        return static::programIdsForStudent($student)[0] ?? null;
    }

    private static function normalizeSessionName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['–', '—', '−', '‑', '‐'], '-', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        // Canonicalize common year-range shorthand, e.g. 2023-27 and
        // 2023/2027 both become 2023-2027.
        if (preg_match('/(\d{4})\D*(\d{4}|\d{2})/', $value, $m)) {
            $start = $m[1];
            $end = $m[2];
            if (strlen($end) === 2) {
                $end = substr($start, 0, 2) . $end;
            }
            return $start . '-' . $end;
        }

        return $value;
    }

    private static function normalizeProgramName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        // Program names sometimes differ only by spacing/punctuation after
        // imports. Keep letters/digits so an otherwise identical Program can
        // still find its saved Session schedule.
        return preg_replace('/[^a-z0-9]+/i', '', $value) ?? $value;
    }

    /**
     * Return exact installment due dates for a student's cycle range.
     *
     * Resolution order per cycle:
     *  1. stored academic_year_id, then equivalent visible Session-name rows
     *  2. exact enrolled Program id
     *  3. equivalent Program name (legacy/duplicate Program compatibility)
     *  4. Session-wide "All Programs" default
     *
     * @return array<int, array<int, string>> keyed by cycle number
     */
    /**
     * Human-readable explanation of why datesForStudentRange() did or didn't
     * find a match for this student, for the "No matching default dates..."
     * notice on the Fee Assignment screen. Not used for any actual matching
     * decision — read-only diagnostics so a mismatch can be root-caused from
     * the UI instead of guessed at from the code.
     */
    public static function diagnose(Student $student, int $cycleNo): array
    {
        $academicYearIds = static::academicYearIdsForStudent($student);
        $programIds = static::programIdsForStudent($student);

        $sessionRows = $academicYearIds
            ? AcademicYear::whereIn('id', $academicYearIds)->pluck('name', 'id')->all()
            : [];
        $programRows = $programIds
            ? Program::whereIn('id', $programIds)->pluck('name', 'id')->all()
            : [];

        $rowsForSession = $academicYearIds
            ? static::whereIn('academic_year_id', $academicYearIds)->with('program:id,name')->get()
            : collect();

        return [
            'student_academic_year_id' => $student->academic_year_id,
            'student_session_text'     => $student->session,
            'student_school_class_id'  => $student->school_class_id,
            'student_program_id'       => $student->program_id,
            'resolved_session_ids'     => $sessionRows,
            'resolved_program_ids'     => $programRows,
            'cycle_no'                 => $cycleNo,
            'session_resolved'         => (bool) $academicYearIds,
            'program_resolved'         => (bool) $programIds,
            'any_defaults_for_session' => $rowsForSession->isNotEmpty(),
            'defaults_for_session'     => $rowsForSession->map(fn ($r) => [
                'academic_year_id' => $r->academic_year_id,
                'program_id'       => $r->program_id,
                'program_name'     => $r->program?->name,
                'cycle_no'         => $r->cycle_no,
            ])->all(),
            'default_exists_for_this_cycle' => $rowsForSession->where('cycle_no', $cycleNo)->isNotEmpty(),
        ];
    }

    public static function datesForStudentRange(Student $student, int $cycleStart, int $cycleEnd): array
    {
        $academicYearIds = static::academicYearIdsForStudent($student);
        if (!$academicYearIds || $cycleEnd < $cycleStart) {
            return [];
        }

        $programIds = static::programIdsForStudent($student);
        $programNames = Program::query()
            ->whereIn('id', $programIds)
            ->pluck('name')
            ->map(fn ($name) => static::normalizeProgramName($name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Intentionally fetch all Program scopes for these candidate Sessions.
        // Exact ids are preferred below, but this lets older/duplicated Program
        // records with the same name still resolve instead of silently falling
        // back to generated dates.
        $records = static::query()
            ->with('program:id,name')
            ->whereIn('academic_year_id', $academicYearIds)
            ->whereBetween('cycle_no', [$cycleStart, $cycleEnd])
            ->get()
            ->groupBy('cycle_no');

        $resolved = [];

        for ($cycle = $cycleStart; $cycle <= $cycleEnd; $cycle++) {
            $cycleRecords = $records->get($cycle, collect());
            if ($cycleRecords->isEmpty()) {
                continue;
            }

            $chosen = null;

            // Preserve Session priority. We only fall through to a secondary
            // candidate Session if the preferred one has no matching Program
            // or Session-wide row for this cycle.
            foreach ($academicYearIds as $academicYearId) {
                $candidates = $cycleRecords
                    ->where('academic_year_id', (int) $academicYearId)
                    ->values();

                if ($candidates->isEmpty()) {
                    continue;
                }

                foreach ($programIds as $programId) {
                    $chosen = $candidates->firstWhere('program_id', (int) $programId);
                    if ($chosen) {
                        break;
                    }
                }

                if (!$chosen && $programNames) {
                    $chosen = $candidates->first(function ($candidate) use ($programNames) {
                        if (!$candidate->program_id || !$candidate->program) {
                            return false;
                        }

                        return in_array(
                            static::normalizeProgramName($candidate->program->name),
                            $programNames,
                            true
                        );
                    });
                }

                if (!$chosen) {
                    $chosen = $candidates->firstWhere('program_id', null);
                }

                if ($chosen) {
                    break;
                }
            }

            if (!$chosen) {
                continue;
            }

            $dates = collect($chosen->dates ?? [])
                ->filter()
                ->map(fn ($date) => date('Y-m-d', strtotime((string) $date)))
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($dates) {
                $resolved[$cycle] = $dates;
            }
        }

        return $resolved;
    }
}
