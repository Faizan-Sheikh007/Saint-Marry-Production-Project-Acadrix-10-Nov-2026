<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExaminationController extends Controller
{
    use \App\Traits\BuildsAcademicCascade;

    private function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    private function currentTeacher(): ?Teacher
    {
        return auth()->user()->teacher;
    }

    private function redirectToIndex()
    {
        return $this->isAdmin()
            ? redirect()->route('admin.examinations.index')
            : redirect()->route('teacher.examinations.index');
    }

    /* ──────────────────── Examination controller's signature ─────────────────── */

    /**
     * Persist the name, title, and signature (drawn or uploaded) of the
     * examination controller. This is shown on every printed marksheet,
     * independent of the institution's general "authorized signatory"
     * used on ID cards.
     */
    public function updateControllerSignature(Request $request)
    {
        abort_unless($this->isAdmin(), 403);

        $validated = $request->validate([
            'exam_controller_name'           => 'nullable|string|max:255',
            'exam_controller_title'          => 'nullable|string|max:255',
            'exam_controller_signature'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'exam_controller_signature_data' => 'nullable|string',
            'remove_exam_controller_signature' => 'nullable|boolean',
        ]);

        unset($validated['exam_controller_signature_data']); // never persisted as its own setting

        if ($request->boolean('remove_exam_controller_signature')) {
            $validated['exam_controller_signature'] = '';
        } elseif ($sigDataUri = $this->storeControllerSignature($request)) {
            // Guard against silently truncating (and thereby corrupting) the
            // image if the `settings.value` column hasn't been widened yet
            // via the widen_settings_value_column migration on this
            // database — MySQL is configured with strict mode off here, so
            // an over-long value would otherwise be cut short instead of
            // rejected, leaving a broken image on every marksheet.
            $columnLimit = $this->settingsValueColumnByteLimit();
            if ($columnLimit !== null && strlen($sigDataUri) > $columnLimit) {
                return back()->withErrors([
                    'exam_controller_signature' => 'This signature is too large to store — please run "php artisan migrate" to apply the pending database update, then try saving the signature again.',
                ])->withInput();
            }
            $validated['exam_controller_signature'] = $sigDataUri;
        } else {
            unset($validated['exam_controller_signature']);
        }
        unset($validated['remove_exam_controller_signature']);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return $this->redirectToIndex()->with('success', 'Examination controller signature updated — it will now appear on every marksheet.');
    }

    /**
     * Byte limit of the `settings.value` column on this database, so we can
     * refuse to save a signature that would otherwise be silently
     * truncated. Returns null (no known limit) for non-MySQL connections
     * or if the column type can't be determined.
     */
    protected function settingsValueColumnByteLimit(): ?int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return null;
        }

        try {
            $column = collect(DB::select('SHOW COLUMNS FROM settings LIKE \'value\''))->first();
            $type   = strtolower($column->Type ?? '');
        } catch (\Throwable $e) {
            return null;
        }

        return match (true) {
            str_contains($type, 'longtext')   => null, // effectively unlimited for our purposes
            str_contains($type, 'mediumtext') => 16 * 1024 * 1024 - 1,
            str_contains($type, 'tinytext')   => 255,
            str_contains($type, 'text')       => 65535,
            default => null,
        };
    }

    /**
     * Build the examination controller's signature as a base64 data URI —
     * either from the on-screen drawing pad (already submitted as a data
     * URI in `exam_controller_signature_data`) or from an uploaded image
     * file — and return it ready to save straight into the `settings`
     * table. Storing the image itself in the database (rather than a path
     * to a file on disk) means it isn't lost if the storage disk ever
     * gets wiped or isn't persistent. Returns null if nothing new was
     * submitted.
     */
    protected function storeControllerSignature(Request $request): ?string
    {
        if ($request->hasFile('exam_controller_signature')) {
            $file = $request->file('exam_controller_signature');
            $mime = $file->getMimeType() ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }

        if ($request->filled('exam_controller_signature_data')) {
            $data = $request->input('exam_controller_signature_data');
            if (preg_match('/^data:image\/(png|jpe?g);base64,[A-Za-z0-9+\/=]+$/', $data)) {
                return $data;
            }
        }

        return null;
    }

    /* ───────────────────────────── Listing ───────────────────────────── */

    public function index(Request $request)
    {
        $academicYears = $this->academicCascadeYears(true);
        $filterStructure = $this->buildAcademicCascade($academicYears, true);

        $query = Examination::with(['academicYear', 'schoolClass.program', 'schoolClass.academicYears', 'program', 'subject', 'teacher'])
            ->withCount('results');

        if (!$this->isAdmin()) {
            // Teachers only ever see exams they are personally responsible for,
            // but the same academic filters remain usable on their own list.
            $teacher = $this->currentTeacher();
            $query->where('teacher_id', $teacher?->id ?? 0);
        }

        $query->when($request->academic_year_id, function ($q) use ($request) {
                    $q->where(function ($sessionQuery) use ($request) {
                        $sessionQuery->where('academic_year_id', $request->academic_year_id)
                            ->orWhere(function ($legacy) use ($request) {
                                $legacy->whereNull('academic_year_id')->whereHas('schoolClass.academicYears',
                                    fn ($yearQuery) => $yearQuery->where('academic_years.id', $request->academic_year_id));
                            });
                    });
                })
              ->when($request->program_id, fn ($q) => $q->where(function ($programQuery) use ($request) {
                  $programQuery->where('program_id', $request->program_id)->orWhere(function ($legacy) use ($request) {
                      $legacy->whereNull('program_id')->whereHas('schoolClass', fn ($c) => $c->where('program_id', $request->program_id));
                  });
              }))
              ->when($request->school_class_id, fn ($q) => $q->where('school_class_id', $request->school_class_id))
              ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
              ->when($this->isAdmin() && $request->teacher_id, fn ($q) => $q->where('teacher_id', $request->teacher_id))
              ->when($request->status, fn ($q) => $q->where('status', $request->status))
              ->when($request->search, fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'));

        // Paginate schedule groups, not subject rows. A semester's subjects must never
        // disappear onto the next page simply because a batch crossed a 20-row boundary.
        $items = (clone $query)->toBase()->select('batch_id')
            ->selectRaw('CASE WHEN batch_id IS NULL THEN id ELSE NULL END AS standalone_id')
            ->selectRaw('MAX(exam_date) AS latest_date')->groupBy('batch_id', 'standalone_id')
            ->orderByDesc('latest_date')->orderBy('batch_id')->orderBy('standalone_id')
            ->paginate(10)->withQueryString();
        $batchIds = $items->getCollection()->pluck('batch_id')->filter()->values();
        $singleIds = $items->getCollection()->pluck('standalone_id')->filter()->values();
        $visibleExams = $items->isEmpty() ? collect() : (clone $query)
            ->where(fn ($q) => $q->whereIn('batch_id', $batchIds)->orWhereIn('id', $singleIds))
            ->orderByDesc('exam_date')->orderBy('start_time')->orderBy('id')->get();
        $batches = $visibleExams->groupBy(fn ($e) => $e->batch_id ?: 'single-'.$e->id);

        $classIds = $visibleExams->pluck('school_class_id');
        if ($request->filled('school_class_id')) $classIds->push($request->integer('school_class_id'));
        $curriculumClasses = SchoolClass::whereIn('id', $classIds->unique())
            ->with('program', 'subjectAssignments.subject', 'subjectAssignments.teacher', 'timetableEntries.subject', 'timetableEntries.teacher')->get()->keyBy('id');
        $curriculumRows = $curriculumClasses->map(function ($class) {
            $rows = app(\App\Services\SemesterCurriculum::class)->forClass($class);
            return $this->isAdmin() ? $rows : $rows->filter(fn ($row) => (int) $row->teacher_id === (int) ($this->currentTeacher()?->id ?? 0))->values();
        });
        $selectedCurriculumClass = $curriculumClasses->get($request->integer('school_class_id'));
        $selectedCurriculum = $curriculumRows->get($request->integer('school_class_id'), collect());

        $stats = [
            'total'     => Examination::when(!$this->isAdmin(), fn ($q) => $q->where('teacher_id', $this->currentTeacher()?->id ?? 0))->count(),
            'upcoming'  => Examination::where('exam_date', '>=', now()->toDateString())
                            ->when(!$this->isAdmin(), fn ($q) => $q->where('teacher_id', $this->currentTeacher()?->id ?? 0))->count(),
            'completed' => Examination::where('status', 'completed')
                            ->when(!$this->isAdmin(), fn ($q) => $q->where('teacher_id', $this->currentTeacher()?->id ?? 0))->count(),
            'pending_marks' => Examination::where('exam_date', '<', now()->toDateString())
                            ->whereDoesntHave('results')
                            ->when(!$this->isAdmin(), fn ($q) => $q->where('teacher_id', $this->currentTeacher()?->id ?? 0))->count(),
        ];

        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();

        return view('examinations.index', compact(
            'items', 'batches', 'stats', 'academicYears', 'filterStructure', 'teachers',
            'curriculumRows', 'selectedCurriculumClass', 'selectedCurriculum'
        ));
    }

    /** Print the current schedule or one original scheduling batch. */
    public function printDateSheet(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        $filters = $request->validate([
            'batch_id' => 'nullable|uuid',
            'academic_year_id' => 'nullable|integer',
            'program_id' => 'nullable|integer',
            'school_class_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'teacher_id' => 'nullable|integer',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'search' => 'nullable|string|max:255',
        ]);
        $query = Examination::with(['schoolClass.program', 'program', 'subject']);
        if (!empty($filters['batch_id'])) $query->where('batch_id', $filters['batch_id']);
        if (!empty($filters['academic_year_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('academic_year_id', $filters['academic_year_id'])
                    ->orWhere(fn ($legacy) => $legacy->whereNull('academic_year_id')->whereHas('schoolClass.academicYears',
                        fn ($year) => $year->where('academic_years.id', $filters['academic_year_id'])));
            });
        }
        foreach (['program_id', 'school_class_id', 'subject_id', 'teacher_id', 'status'] as $field) {
            if (!empty($filters[$field])) $query->where($field, $filters[$field]);
        }
        if (!empty($filters['search'])) $query->where('name', 'like', '%'.$filters['search'].'%');
        $examinations = $query->orderBy('exam_date')->orderBy('start_time')->get();
        $first = $examinations->first();
        $examTitle = $first?->name ?? 'Examination Schedule';
        $session = !empty($filters['academic_year_id'])
            ? \App\Models\AcademicYear::find($filters['academic_year_id']) : null;
        // One column per program + semester, as in a traditional exam date-sheet.
        // Exams for the same date and class column occupy the same table cell.
        $programSemesters = $examinations->map(function ($exam) {
            $class = $exam->schoolClass;
            $programId = $class?->program_id ?? $exam->program_id;
            $termNumber = $class?->term_number;
            // When a semester cannot be inferred, keep individual classes separate.
            $columnKey = $programId.'|'.($termNumber !== null ? 'term-'.$termNumber : 'class-'.($class?->id ?? $exam->school_class_id));
            return (object) [
                'key' => $columnKey,
                'program_id' => $programId,
                'program_name' => $class?->program?->name ?? $exam->program?->name ?? 'General',
                'term_number' => $termNumber,
                'term_label' => $class?->section_label ?? $class?->name ?? 'Class',
            ];
        })->unique('key')->sortBy(fn ($row) => strtolower($row->program_name).'|'.str_pad((string) ($row->term_number ?? 999), 4, '0', STR_PAD_LEFT).'|'.$row->term_label)->values();

        $byDate = $examinations->groupBy(fn ($exam) => $exam->exam_date?->format('Y-m-d'));
        $dateGrid = $byDate->map(function ($dayExams) {
            return $dayExams->groupBy(function ($exam) {
                $class = $exam->schoolClass;
                $programId = $class?->program_id ?? $exam->program_id;
                $termNumber = $class?->term_number;
                return $programId.'|'.($termNumber !== null ? 'term-'.$termNumber : 'class-'.($class?->id ?? $exam->school_class_id));
            });
        });
        $starts = $examinations->pluck('start_time')->filter()->unique();
        $ends = $examinations->pluck('end_time')->filter()->unique();
        $commonStart = $starts->count() === 1 ? Carbon::parse($starts->first())->format('g:i A') : null;
        $commonEnd = $ends->count() === 1 ? Carbon::parse($ends->first())->format('g:i A') : null;
        $totalSubjects = $examinations->count();
        return view('examinations.date-sheet-print', compact('examinations', 'examTitle', 'session', 'programSemesters', 'byDate', 'dateGrid', 'commonStart', 'commonEnd', 'totalSubjects'));
    }

    /** Change only the scheduled date; retain all other examination details. */
    public function updateExamDate(Request $request, Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);
        $validated = $request->validate(['exam_date' => 'required|date_format:Y-m-d']);
        $clashes = $this->detectClashes([[
            'school_class_id' => $examination->school_class_id,
            'subject_id' => $examination->subject_id,
            'teacher_id' => $examination->teacher_id,
            'exam_date' => $validated['exam_date'],
            'start_time' => $examination->start_time,
            'end_time' => $examination->end_time,
            'room' => $examination->room,
        ]], excludeExaminationId: $examination->id);
        if ($clashes) return back()->withErrors(['exam_date' => reset($clashes)]);
        $examination->update(['exam_date' => $validated['exam_date']]);
        return back()->with('success', 'Exam date updated. The new date replaces the previous date on the schedule and printed date sheet.');
    }

    /* ─────────────────────── Bulk scheduling wizard ────────────────────── */

    public function scheduleForm()
    {
        $programs = Program::where('is_active', true)
            ->with(['sections' => fn ($q) => $q->withCount('students')])
            ->orderBy('name')->get();

        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $allClasses = SchoolClass::orderBy('name')->get();

        $academicYears = \App\Models\AcademicYear::ordered()->get();
        return view('examinations.schedule', compact('programs', 'teachers', 'subjects', 'allClasses', 'academicYears'));
    }

    /** AJAX: for the chosen classes, return each class's subjects + assigned teacher (from the curriculum). */
    public function classSubjects(Request $request)
    {
        $classIds = collect(explode(',', (string) $request->query('class_ids')))
            ->filter()->map(fn ($v) => (int) $v)->values();

        $classes = SchoolClass::with(['program', 'subjectAssignments.subject', 'subjectAssignments.teacher'])
            ->whereIn('id', $classIds)
            ->get();

        $data = $classes->map(function ($class) {
            return [
                'id'    => $class->id,
                'name'  => $class->display_name ?? $class->name,
                'program_id' => $class->program_id,
                'subjects' => app(\App\Services\SemesterCurriculum::class)->forClass($class)->map(function ($assignment) {
                    return [
                        'assignment_id' => $assignment->id,
                        'subject_id'    => $assignment->subject_id,
                        'subject_name'  => $assignment->subject->name ?? 'Unnamed Subject',
                        'teacher_id'    => $assignment->teacher_id,
                        'teacher_name'  => $assignment->teacher->name ?? null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['classes' => $data]);
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'academic_year_id'        => 'required|exists:academic_years,id',
            'exam_name'               => 'required|string|max:255',
            'type'                    => 'required|in:midterm,final,quiz,unit_test',
            'rows'                    => 'required|array|min:1',
            'rows.*.school_class_id'  => 'required|exists:school_classes,id',
            'rows.*.subject_id'       => 'required|exists:subjects,id',
            'rows.*.teacher_id'       => 'nullable|exists:teachers,id',
            'rows.*.exam_date'        => 'required|date',
            'rows.*.start_time'       => 'required',
            'rows.*.end_time'         => 'required',
            'rows.*.room'             => 'nullable|string|max:100',
            'rows.*.total_marks'      => 'required|integer|min:1',
            'rows.*.passing_marks'    => 'required|integer|min:1',
        ]);

        // ── Per-row sanity checks (time order, passing <= total) ──
        $errors = [];
        foreach ($data['rows'] as $i => $row) {
            $rowClass = SchoolClass::find($row['school_class_id']);
            if (! $rowClass->academicYears()->where('academic_years.id', $data['academic_year_id'])->exists()) {
                $errors["rows.$i.school_class_id"] = 'This semester is not linked to the selected session.';
            }
            if (! app(\App\Services\SemesterCurriculum::class)->forClass($rowClass)->contains('subject_id', (int) $row['subject_id'])) {
                $errors["rows.$i.subject_id"] = 'This subject is not assigned to the selected semester.';
            }
            if (Carbon::parse($row['start_time']) >= Carbon::parse($row['end_time'])) {
                $errors["rows.$i"] = 'End time must be after start time.';
            } elseif ((int) $row['passing_marks'] > (int) $row['total_marks']) {
                $errors["rows.$i"] = 'Passing marks cannot exceed total marks.';
            }
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors)->with('scheduleError', 'Please fix the highlighted rows before continuing.');
        }

        // ── Exam-only clash detection: protect class and room bookings.
        //    Teacher overlaps and regular timetable periods are allowed. ──
        $clashErrors = $this->detectClashes($data['rows']);
        if ($clashErrors) {
            return back()->withInput()->withErrors($clashErrors)->with('scheduleError', 'Examination scheduling conflicts were found. See the specific reasons below.');
        }

        $batchId = (string) Str::uuid();
        $notifyTeacherIds = [];

        DB::transaction(function () use ($data, $batchId, &$notifyTeacherIds) {
            foreach ($data['rows'] as $row) {
                $class = SchoolClass::find($row['school_class_id']);
                $examination = Examination::create([
                    'academic_year_id'=> $data['academic_year_id'],
                    'batch_id'        => $batchId,
                    'name'            => $data['exam_name'],
                    'school_class_id' => $row['school_class_id'],
                    'program_id'      => $class->program_id,
                    'subject_id'      => $row['subject_id'],
                    'teacher_id'      => $row['teacher_id'] ?: null,
                    'exam_date'       => $row['exam_date'],
                    'start_time'      => $row['start_time'],
                    'end_time'        => $row['end_time'],
                    'room'            => $row['room'] ?? null,
                    'total_marks'     => $row['total_marks'],
                    'passing_marks'   => $row['passing_marks'],
                    'type'            => $data['type'],
                    'status'          => 'scheduled',
                ]);

                // Every scheduled examination gets its own paper to fill in — the
                // teacher's dashboard will nudge them until it's uploaded.
                // (ExaminationObserver::created() also creates this automatically;
                // firstOrCreate here just keeps it idempotent either way.)
                \App\Models\ExamPaper::firstOrCreate(
                    ['examination_id' => $examination->id],
                    ['teacher_id' => $examination->teacher_id, 'status' => 'due']
                );

                if ($examination->teacher_id) {
                    $notifyTeacherIds[$examination->teacher_id] = true;
                }
            }
        });

        // Notify each affected teacher once, even if they have several papers due from this batch.
        foreach (array_keys($notifyTeacherIds) as $teacherId) {
            $teacher = Teacher::find($teacherId);
            if ($teacher?->user_id) {
                \App\Services\NotifierService::notify(
                    $teacher->user_id,
                    "New examination \"{$data['exam_name']}\" scheduled — please create or upload your question paper.",
                    'fa-file-pen', 'yellow'
                );
            }
        }

        $count = count($data['rows']);
        return redirect()->route('admin.examinations.index')
            ->with('success', "\"{$data['exam_name']}\" scheduled successfully across {$count} class/subject pairing" . ($count === 1 ? '' : 's') . "!");
    }

    /**
     * Check candidate exams for class and room clashes, both within the submitted
     * schedule and against existing exams. Teacher overlaps and ordinary teaching
     * timetable periods do not block examination scheduling.
     * Returns an array of ["rows.$i" => "message"] suitable for withErrors().
     */
    private function detectClashes(array $rows, ?int $excludeExaminationId = null, ?string $excludeBatchId = null): array
    {
        $errors = [];

        $classIds   = collect($rows)->pluck('school_class_id')->filter()->unique()->values();
        $dates      = collect($rows)->pluck('exam_date')->unique()->values();
        $rooms      = collect($rows)->pluck('room')->filter(fn ($room) => trim((string) $room) !== '')->unique()->values();

        $existingExams = Examination::where('status', '!=', 'cancelled')
            ->whereIn('exam_date', $dates)
            ->where(function ($q) use ($classIds, $rooms) {
                $q->whereIn('school_class_id', $classIds);
                if ($rooms->isNotEmpty()) $q->orWhereIn('room', $rooms);
            })
            ->when($excludeExaminationId, fn ($q) => $q->where('id', '!=', $excludeExaminationId))
            ->when($excludeBatchId, fn ($q) => $q->where(fn ($qq) => $qq->whereNull('batch_id')->orWhere('batch_id', '!=', $excludeBatchId)))
            ->get();

        $classCache   = SchoolClass::whereIn('id', $classIds)->get()->keyBy('id');

        $overlaps = function ($aStart, $aEnd, $bStart, $bEnd) {
            return Carbon::parse($aStart) < Carbon::parse($bEnd) && Carbon::parse($bStart) < Carbon::parse($aEnd);
        };

        foreach ($rows as $i => $row) {
            $rowStart = $row['start_time'];
            $rowEnd   = $row['end_time'];
            $rowDate  = $row['exam_date'];

            // 1) Against already-scheduled examinations.
            foreach ($existingExams as $exam) {
                if ($exam->exam_date->toDateString() !== $rowDate) continue;
                if (!$overlaps($rowStart, $rowEnd, $exam->start_time, $exam->end_time ?? $exam->start_time)) continue;

                if ((int) $exam->school_class_id === (int) $row['school_class_id']) {
                    $className = $classCache[$row['school_class_id']]->display_name ?? $classCache[$row['school_class_id']]->name ?? 'This class';
                    $errors["rows.$i"] = "{$className} already has \"{$exam->name}\" scheduled at an overlapping time on " . Carbon::parse($rowDate)->format('d M Y') . '.';
                    continue 2;
                }
                if (!empty($row['room']) && $exam->room && strcasecmp($exam->room, $row['room']) === 0) {
                    $errors["rows.$i"] = "Room \"{$row['room']}\" is already booked for \"{$exam->name}\" at an overlapping time.";
                    continue 2;
                }
            }

            // 2) Against other rows within this same submission (self-clash).
            foreach ($rows as $j => $other) {
                if ($i === $j) continue;
                if ($other['exam_date'] !== $rowDate) continue;
                if (!$overlaps($rowStart, $rowEnd, $other['start_time'], $other['end_time'])) continue;

                if (!empty($row['room']) && !empty($other['room'])
                    && strcasecmp(trim($row['room']), trim($other['room'])) === 0) {
                    $errors["rows.$i"] = "Room \"{$row['room']}\" is assigned to two overlapping exams in this schedule.";
                    break;
                }
                if ((int) $other['school_class_id'] === (int) $row['school_class_id']) {
                    $className = $classCache[$row['school_class_id']]->display_name ?? $classCache[$row['school_class_id']]->name ?? 'This class';
                    $errors["rows.$i"] = "{$className} has two overlapping exams in this schedule.";
                    break;
                }
            }
            if (isset($errors["rows.$i"])) continue;

        }

        return $errors;
    }

    /* ───────────────────────────── CRUD (single exam) ───────────────────────────── */

    public function show(Examination $examination)
    {
        if (! $this->isAdmin()) abort_unless((int) $examination->teacher_id === (int) ($this->currentTeacher()?->id ?? 0), 403);
        $examination->load('schoolClass.program', 'academicYear', 'program', 'subject', 'teacher', 'results.student');
        $semesterSubjects = $examination->schoolClass
            ? app(\App\Services\SemesterCurriculum::class)->forClass($examination->schoolClass) : collect();
        if (! $this->isAdmin()) $semesterSubjects = $semesterSubjects->filter(fn ($row) => (int) $row->teacher_id === (int) ($this->currentTeacher()?->id ?? 0))->values();

        $batchSiblings = $examination->batch_id
            ? Examination::where('batch_id', $examination->batch_id)
                ->where('id', '!=', $examination->id)
                ->when(! $this->isAdmin(), fn ($q) => $q->where('teacher_id', $this->currentTeacher()?->id ?? 0))
                ->with('schoolClass', 'subject', 'teacher')
                ->get()
            : collect();

        $teacher = $this->currentTeacher();
        $canEnterMarks = $this->isAdmin()
            ? false
            : $examination->canBeMarkedBy($teacher);

        return view('examinations.show', compact('examination', 'batchSiblings', 'canEnterMarks', 'semesterSubjects'));
    }

    public function edit(Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get();
        $academicYears = $examination->schoolClass?->academicYears ?? collect();
        return view('examinations.edit', compact('examination', 'teachers', 'academicYears'));
    }

    public function update(Request $request, Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name'          => 'required|string|max:255',
            'teacher_id'    => 'nullable|exists:teachers,id',
            'exam_date'     => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'room'          => 'nullable|string|max:100',
            'total_marks'   => 'required|integer|min:1',
            'passing_marks' => 'required|integer|min:1',
            'type'          => 'required|in:midterm,final,quiz,unit_test',
            'status'        => 'required|in:scheduled,completed,cancelled',
        ]);

        if (Carbon::parse($validated['start_time']) >= Carbon::parse($validated['end_time'])) {
            return back()->withInput()->withErrors(['end_time' => 'End time must be after start time.']);
        }
        if ($validated['passing_marks'] > $validated['total_marks']) {
            return back()->withInput()->withErrors(['passing_marks' => 'Passing marks cannot exceed total marks.']);
        }

        $clashRow = [
            'school_class_id' => $examination->school_class_id,
            'subject_id'      => $examination->subject_id,
            'teacher_id'      => $validated['teacher_id'] ?? null,
            'exam_date'       => $validated['exam_date'],
            'start_time'      => $validated['start_time'],
            'end_time'        => $validated['end_time'],
            'room'            => $validated['room'] ?? null,
        ];
        $clashErrors = $this->detectClashes([$clashRow], excludeExaminationId: $examination->id);
        if ($clashErrors) {
            return back()->withInput()->withErrors(['exam_date' => reset($clashErrors)]);
        }

        if (! $examination->schoolClass->academicYears()->where('academic_years.id', $validated['academic_year_id'])->exists()) {
            return back()->withInput()->withErrors(['academic_year_id' => 'Choose a session linked to this semester.']);
        }
        $examination->update($validated);

        return $this->redirectToIndex()->with('success', 'Examination updated successfully!');
    }

    public function destroy(Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);
        $examination->delete();
        return $this->redirectToIndex()->with('success', 'Examination deleted successfully!');
    }

    /** Delete every examination created together in one scheduling run. */
    public function destroyBatch(string $batchId)
    {
        abort_unless($this->isAdmin(), 403);
        $count = Examination::where('batch_id', $batchId)->count();
        Examination::where('batch_id', $batchId)->delete();
        return $this->redirectToIndex()->with('success', "Removed {$count} examination(s) from that schedule.");
    }

    public function lock(Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);
        $examination->update(['locked_at' => now(), 'status' => 'completed']);
        return back()->with('success', 'Results locked. Marks can no longer be edited by the teacher.');
    }

    public function unlock(Examination $examination)
    {
        abort_unless($this->isAdmin(), 403);
        $examination->update(['locked_at' => null]);
        return back()->with('success', 'Results unlocked for editing.');
    }

    /* ───────────────────────────── Marks entry ───────────────────────────── */

    public function results(Examination $examination)
    {
        $examination->load('schoolClass', 'subject', 'teacher');

        $results  = $examination->results()->with('student')->get();
        $students = Student::where('school_class_id', $examination->school_class_id)
                           ->where('status', 'active')->orderBy('name')->get();

        $teacher = $this->currentTeacher();
        $canEdit = $this->isAdmin() ? false : $examination->canBeMarkedBy($teacher);

        if (!$this->isAdmin() && !$canEdit) {
            abort(403, 'You are not the assigned teacher for this examination.');
        }

        return view('examinations.results', compact('examination', 'results', 'students', 'canEdit'));
    }

    public function storeResults(Request $request, Examination $examination)
    {
        $teacher = $this->currentTeacher();
        abort_unless($examination->canBeMarkedBy($teacher), 403, 'Only the assigned teacher can enter marks for this examination, and only before it is locked.');

        $request->validate(['marks' => 'required|array']);

        $allowedStudentIds = Student::activeEnrollment()
            ->where('school_class_id', $examination->school_class_id)->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach (array_keys($request->marks) as $studentId) {
            abort_unless(ctype_digit((string) $studentId) && in_array((int) $studentId, $allowedStudentIds, true),
                422, 'Marks can only be entered for active students in this examination class.');
        }

        foreach ($request->marks as $studentId => $marks) {
            if ($marks === null || $marks === '') continue;

            $marks = min((float) $marks, (float) $examination->total_marks);
            $percentage = round(($marks / $examination->total_marks) * 100, 2);
            // Grade computed from Admin's custom grading formula (Settings -> Grading Scale),
            // so a subject-level grade and the compiled marksheet's overall grade always agree.
            $grade = \App\Models\GradingScale::gradeFor($percentage);

            ExamResult::updateOrCreate(
                ['examination_id' => $examination->id, 'student_id' => $studentId],
                ['marks_obtained' => $marks, 'percentage' => $percentage, 'grade' => $grade, 'graded_by' => $teacher->id]
            );
        }

        if ($request->boolean('mark_completed')) {
            $examination->update(['status' => 'completed']);
        }

        return back()->with('success', 'Results saved successfully!');
    }

    /* ───────────────────────────── Admin reporting ───────────────────────────── */

    public function report(Request $request)
    {
        abort_unless($this->isAdmin(), 403);

        $query = ExamResult::with(['student.schoolClass', 'examination.subject', 'examination.teacher', 'examination.program'])
            ->when($request->program_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('program_id', $request->program_id)))
            ->when($request->school_class_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('school_class_id', $request->school_class_id)))
            ->when($request->subject_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('subject_id', $request->subject_id)))
            ->when($request->teacher_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('teacher_id', $request->teacher_id)))
            ->when($request->examination_id, fn ($q) => $q->where('examination_id', $request->examination_id));

        $results = (clone $query)->latest()->paginate(30)->withQueryString();

        $summary = [
            'entries'   => (clone $query)->count(),
            'avg'       => round((clone $query)->avg('percentage') ?? 0, 1),
            'passed'    => (clone $query)->whereColumn('marks_obtained', '>=', DB::raw('(select passing_marks from examinations where examinations.id = exam_results.examination_id)'))->count(),
        ];

        $programs     = Program::orderBy('name')->get();
        $classes      = SchoolClass::orderBy('name')->get();
        $subjects     = Subject::orderBy('name')->get();
        $teachers     = Teacher::where('status', 'active')->orderBy('name')->get();
        $examinations = Examination::orderByDesc('exam_date')->get();

        return view('examinations.report', compact('results', 'summary', 'programs', 'classes', 'subjects', 'teachers', 'examinations'));
    }

    public function reportExport(Request $request)
    {
        abort_unless($this->isAdmin(), 403);

        $rows = ExamResult::with(['student', 'examination.subject', 'examination.schoolClass', 'examination.teacher'])
            ->when($request->program_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('program_id', $request->program_id)))
            ->when($request->school_class_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('school_class_id', $request->school_class_id)))
            ->when($request->subject_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('subject_id', $request->subject_id)))
            ->when($request->teacher_id, fn ($q) => $q->whereHas('examination', fn ($e) => $e->where('teacher_id', $request->teacher_id)))
            ->when($request->examination_id, fn ($q) => $q->where('examination_id', $request->examination_id))
            ->get();

        $filename = 'examination-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'Class', 'Subject', 'Examination', 'Teacher', 'Marks Obtained', 'Total Marks', 'Percentage', 'Grade', 'Student Status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->student->name ?? '',
                    $r->examination->schoolClass->name ?? '',
                    $r->examination->subject->name ?? '',
                    $r->examination->name ?? '',
                    $r->examination->teacher->name ?? '',
                    $r->marks_obtained,
                    $r->examination->total_marks ?? '',
                    $r->percentage,
                    $r->grade,
                    \App\Support\StudentStatus::label($r->student?->status),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
