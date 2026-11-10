<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Program;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Setting;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Message;
use App\Mail\AdminStudentMessageMail;

class StudentController extends Controller
{
    use \App\Traits\PlanLimitTrait;

    public function index(Request $request)
    {
        $query = Student::with('schoolClass', 'program', 'academicYear');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->academic_year) $query->where('academic_year_id', $request->academic_year);
        if ($request->program)       $query->where('program_id', $request->program);
        if ($request->class)         $query->where('school_class_id', $request->class);
        if ($request->status)        $query->where('status', $request->status);

        // Student filtering follows the actual academic structure instead of
        // showing every class in one flat list: Session -> Program -> linked
        // Semester/Part. This removes duplicate-looking "Part 1" options that
        // belong to other programs/sessions.
        $academicYears = AcademicYear::with(['schoolClasses.program'])->ordered()->get();
        $filterStructure = $this->buildStudentFilterStructure($academicYears);

        // Session tabs above the filter card: one grouped query, honoring
        // every currently-applied filter EXCEPT the session itself, so each
        // tab's badge shows how many students you'd see if you clicked it
        // (rather than a static, filter-blind total).
        $sessionCounts = $this->studentSessionCounts($request);
        $academicYears->each(fn ($year) => $year->student_count = $sessionCounts[$year->id] ?? 0);
        $allSessionsCount = array_sum($sessionCounts);

        // Optional grouped view: "Program — Academic Year" header, then the
        // students under it. Pagination doesn't make sense once grouped, so
        // this mode pulls everything matching the filters instead.
        if ($request->boolean('grouped')) {
            $students = $query->orderBy('program_id')->orderBy('academic_year_id')->orderBy('name')->get();

            $grouped = $students->groupBy(function ($student) {
                $programName = $student->program->name ?? 'No Program';
                $yearName    = $student->academicYear->name ?? 'No Academic Year';
                return "{$programName}||{$yearName}";
            })->sortKeys();

            $data = compact('academicYears', 'filterStructure', 'grouped', 'allSessionsCount') + ['students' => $students];
        } else {
            $students = $query->latest()->paginate(120)->withQueryString();

            $data = compact('students', 'academicYears', 'filterStructure', 'allSessionsCount');
        }

        // Session-tab clicks, the filter form, and pagination links are all
        // AJAX (see the script in students/index.blade.php) — they only
        // need the panel fragment (tabs + filters + table), not the full
        // page chrome (sidebar, header, Add Student / Generate LMS IDs).
        if ($request->ajax()) {
            return view('students._panel', $data);
        }

        return view('students.index', $data);
    }

    /**
     * Student counts per Academic Session, honoring every currently-applied
     * filter except the session itself, for the session-tab badges on the
     * Students index.
     */
    private function studentSessionCounts(Request $request): array
    {
        $query = Student::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->program) $query->where('program_id', $request->program);
        if ($request->class)   $query->where('school_class_id', $request->class);
        if ($request->status)  $query->where('status', $request->status);

        return $query->select('academic_year_id', DB::raw('count(*) as cnt'))
            ->groupBy('academic_year_id')
            ->pluck('cnt', 'academic_year_id')
            ->all();
    }

    /**
     * JSON-ready cascade for the Student list filters. Each Semester/Part row
     * is the exact SchoolClass linked through the Academic Session pivot.
     */
    private function buildStudentFilterStructure($academicYears): array
    {
        $structure = [];

        foreach ($academicYears as $year) {
            $classes = $year->schoolClasses
                ->filter(fn ($class) => $class->program)
                ->sortBy(fn ($class) => [
                    $class->program->name,
                    $class->term_number ?? 999,
                    $class->section ?? '',
                    $class->name,
                ])
                ->values();

            $programs = $classes->groupBy('program_id')->map(function ($programClasses) {
                $program = $programClasses->first()->program;
                $terms = $programClasses->map(function ($class) {
                    return [
                        'id'      => $class->id,
                        'name'    => $class->section_label,
                        'term_no' => $class->term_number,
                    ];
                })->values()->all();

                return [
                    'id'    => $program->id,
                    'name'  => $program->name,
                    'terms' => $terms,
                ];
            })->sortBy('name')->values()->all();

            $structure[(string) $year->id] = [
                'name'     => $year->name,
                'programs' => $programs,
            ];
        }

        return $structure;
    }

    /** Letterhead-style printable roster — mirrors the Reporting Center's print sheets. */
    public function print(Request $request)
    {
        $query = Student::with('schoolClass', 'program', 'academicYear');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }

        if ($request->academic_year) $query->where('academic_year_id', $request->academic_year);
        if ($request->program)       $query->where('program_id', $request->program);
        if ($request->class)         $query->where('school_class_id', $request->class);
        if ($request->status)        $query->where('status', $request->status);

        $students = $query->orderBy('program_id')->orderBy('name')->get();

        $filters = [
            'search'   => $request->search,
            'session'  => $request->academic_year ? AcademicYear::find($request->academic_year)?->name : null,
            'program'  => $request->program ? Program::find($request->program)?->name : null,
            'class'    => $request->class ? SchoolClass::find($request->class)?->section_label : null,
            'status'  => $request->status ? ucfirst($request->status) : null,
        ];

        return view('students.print', compact('students', 'filters'));
    }

    public function create()
    {
        $academicLevels = \App\Models\AcademicLevel::active()->ordered()->get();
        $academicYears  = \App\Models\AcademicYear::active()->ordered()->get();
        $institutions   = \App\Models\Institution::where('is_active', true)->orderBy('name')->get();
        return view('students.form', compact('academicLevels', 'academicYears', 'institutions'));
    }

    protected function rules($id = null): array
    {
        return [
            // ── Mandatory admission-form fields ──────────────────────────
            'program_id'                  => 'required|exists:programs,id',
            'academic_level_id'           => 'required|exists:academic_levels,id',
            'academic_year_id'            => 'required|exists:academic_years,id',
            'institution_id'              => 'nullable|exists:institutions,id',
            'shift'                       => 'required|in:morning,evening',
            'name'                        => 'required|string|max:255',
            'father_name'                  => 'required|string|max:255',
            // Native date input posts ISO Y-m-d; keep the browser calendar as
            // the single source of entry instead of accepting a typed format.
            'date_of_birth'               => 'required|date_format:Y-m-d|before_or_equal:today',
            'cnic'                        => 'nullable|string|max:20',
            'permanent_address'           => 'required|string',
            'marital_status'              => 'required|in:single,married,divorced,widowed',
            'religion'                    => 'required|string|max:50',
            'gender'                      => 'required|in:male,female,other',

            // ── Program placement ────────────────────────────────────────
            'school_class_id'             => 'required|exists:school_classes,id',
            // Student mobile number uses the existing students.phone column.
            // Keeping it nullable avoids any database change for this field.
            'phone'                       => 'nullable|string|max:20',

            // ── Optional fields (kept from the previous form) ────────────
            'section'                     => 'nullable|string|max:10',
            'admission_date'              => 'nullable|date',
            'email'                       => 'nullable|email|max:255',
            'attendance_percentage'       => 'nullable|numeric|min:0|max:100',
            'status'                      => 'required|in:'.implode(',', \App\Support\StudentStatus::VALUES),
            // Student photo capped at 100KB (max is in kilobytes). Custom
            // message below overrides Laravel's default "may not be greater
            // than 100 kilobytes" wording with something more human.
            'photo'                       => 'nullable|image|max:100',
        ];
    }

    /**
     * Normalize the native calendar value to the SQL DATE representation.
     */
    protected function normalizeDateOfBirth(array &$validated): void
    {
        if (empty($validated['date_of_birth'])) {
            return;
        }

        $dob = Carbon::createFromFormat('!Y-m-d', $validated['date_of_birth']);

        $validated['date_of_birth'] = $dob->toDateString();
    }

    /** Friendlier wording for the shared rules() above — keyed by the same field/rule names. */
    protected function messages(): array
    {
        return [
            'photo.max'   => 'The photo is too large — please upload an image under 100KB.',
            'photo.image' => 'The photo must be a valid image file (JPG, PNG, etc.).',
        ];
    }

    /**
     * Mirrors the selected Academic Level / Academic Year into the legacy
     * `level` / `session` string columns (still read elsewhere in the app),
     * and enforces that an Institution is chosen whenever the selected
     * Academic Level requires one (i.e. "University").
     */
    protected function applyAcademicSelections(array &$validated): void
    {
        $level = \App\Models\AcademicLevel::find($validated['academic_level_id']);
        $year  = \App\Models\AcademicYear::find($validated['academic_year_id']);

        abort_if(! $level, 422, 'Invalid academic level selected.');
        abort_if(! $year, 422, 'Invalid academic year selected.');

        if ($level->requires_institution) {
            abort_if(empty($validated['institution_id']), 422, 'Please select an Institution for a University-level admission.');
        } else {
            $validated['institution_id'] = null;
        }

        $validated['level']   = $level->slug;
        $validated['session'] = $year->name;
    }

    protected function assertSectionBelongsToProgram(array $validated): void
    {
        $belongs = SchoolClass::where('id', $validated['school_class_id'])
            ->where('program_id', $validated['program_id'])
            ->exists();

        abort_unless($belongs, 422, 'The selected Section does not belong to the selected Program.');
    }

    /**
     * Academic Session is no longer an independent dropdown. The selected
     * Part/Semester must be one of the records explicitly linked to that
     * session under Admin -> Academic Sessions.
     */
    protected function assertSectionBelongsToAcademicYear(array $validated): void
    {
        $offered = SchoolClass::query()
            ->whereKey($validated['school_class_id'])
            ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $validated['academic_year_id']))
            ->exists();

        abort_unless($offered, 422, 'The selected Program / Part / Semester is not linked to the selected Academic Session. Manage the session links under Academic Sessions first.');
    }

    /** Generate the admission Student ID server-side; the form never accepts a manual value. */
    protected function generateStudentId(): string
    {
        $next = ((int) Student::max('id')) + 1;

        do {
            $studentId = 'STU-' . now()->year . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Student::where('student_id', $studentId)->exists());

        return $studentId;
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $this->normalizeDateOfBirth($validated);
        $this->assertSectionBelongsToProgram($validated);
        $this->assertSectionBelongsToAcademicYear($validated);
        $this->applyAcademicSelections($validated);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
        }

        $validated['student_id'] = $this->generateStudentId();


        // ── Issue an LMS login for the student ──────────────────────────
        // Every student needs a User account (role=student) to sign in to
        // the LMS side (courses, assignments, quizzes, grades).
        [$user, $loginEmail, $password] = $this->issueLmsLogin($validated['name'], $validated['email'] ?? null, $validated['student_id']);

        $validated['user_id'] = $user->id;

        // status/fee_status/legacy roll_number are admin-only fields excluded from
        // $fillable. The admission form no longer accepts a roll number; the
        // generated Student ID is the primary enrollment identifier.
        $student = new Student();
        $student->adminFill($validated);
        $student->save();

        AuditLog::record('created', 'Student', $student->id, "Admitted student {$student->name} ({$student->student_id})");

        // Admission only creates the fee plan for the Part/Semester the
        // student has just been enrolled in. Future semesters are generated
        // later from the student's profile / fee-plan renewal flow.
        return redirect()->route('fee-assignments.create', [
                'student' => $student,
                'admission' => 1,
            ])
            ->with('success', "Student admitted! Student ID: {$student->student_id}. LMS login issued — Email: {$loginEmail} | Password: {$password}. Now set up the current semester/part fee plan.")
            ->with('credentials', [
                'email'    => $loginEmail,
                'password' => $password,
                'name'     => $validated['name'],
            ]);
    }

    public function show(Student $student)
    {
        $student->load([
            'schoolClass.program.institution', 'program.institution', 'user',
            'academicLevel', 'academicYear', 'institution',
            'vouchers' => fn ($q) => $q->with('feeHead', 'feeAssignment.feeTemplate')->latest(),
            'feeAssignments' => fn ($q) => $q->with('feeTemplate', 'vouchers')->latest(),
        ]);

        $timetable = $student->school_class_id
            ? Timetable::with('subject', 'teacher')
                ->where('school_class_id', $student->school_class_id)
                ->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        $feePlanProgression = app(\App\Services\FeePlanProgression::class)->forStudent($student);
        $authoritative = $student->authoritativeVouchers();
        $authoritative->loadMissing('feeHead', 'feeAssignment.feeTemplate');
        $feeLedger = \App\Services\StudentFeeLedger::summarize($authoritative, now());
        $payments = \App\Models\Payment::with('voucher')
            ->whereIn('voucher_id', $student->vouchers->pluck('id'))
            ->latest('id')->get();
        $paymentHistory = \App\Services\VoucherPaymentHistory::forStudent($student->vouchers, $payments);
        $financeCreditTransactions = $student->balanceTransactions()->latest('id')->limit(100)->get();
        $financeAvailableCredit = (float) $student->credit_balance;
        return view('students.show', compact(
            'student', 'timetable', 'feePlanProgression', 'feeLedger', 'paymentHistory',
            'financeCreditTransactions', 'financeAvailableCredit'
        ));
    }

    /**
     * Admin-facing read-only view of a student's fee position — pending
     * (previous balance), upcoming, currently active/due, and paid
     * history. All figures come from Student::feeSummary(), the same
     * single source of truth used on the student/parent/teacher fee
     * views. Admin cannot record or edit payments here — recording and
     * reversing payments stays Accounts-only (see routes/web.php).
     */
    public function feeSummary(Student $student)
    {
        $feeAssignments = $student->feeAssignments()->with('feeTemplate', 'vouchers')->latest()->get();
        $vouchers   = $student->vouchers()->with('feeHead', 'payments')->latest('due_date')->get();
        $student->setRelation('feeAssignments', $feeAssignments);
        $student->setRelation('vouchers', $vouchers);
        $feeSummary = $student->feeSummary();
        $payments = \App\Models\Payment::with('voucher')
            ->whereIn('voucher_id', $vouchers->pluck('id'))
            ->latest('id')->get();
        $authoritative = $student->authoritativeVouchers();
        $authoritative->loadMissing('feeHead', 'feeAssignment.feeTemplate');
        $feeLedger = \App\Services\StudentFeeLedger::summarize($authoritative, now());
        $paymentHistory = \App\Services\VoucherPaymentHistory::forStudent($vouchers, $payments);
        $financeCreditTransactions = $student->balanceTransactions()->latest('id')->limit(100)->get();
        $financeAvailableCredit = (float) $student->credit_balance;

        return view('students.fee-summary', compact(
            'student', 'feeSummary', 'vouchers', 'feeLedger', 'paymentHistory', 'feeAssignments',
            'financeCreditTransactions', 'financeAvailableCredit'
        ));
    }

    public function messageForm(Student $student)
    {
        $student->load('parentGuardian');
        return view('students.message', compact('student'));
    }

    public function sendMessage(Request $request, Student $student)
    {
        $student->load('parentGuardian');

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'send_student' => 'nullable|boolean',
            'send_parent'  => 'nullable|boolean',
        ]);

        $sendToStudent = $request->boolean('send_student') && $student->email;
        $sendToParent  = $request->boolean('send_parent') && $student->parentGuardian?->email;

        abort_unless($sendToStudent || $sendToParent, 422, 'Select at least one recipient with a valid email on file.');

        if ($sendToStudent) {
            Mail::to($student->email)->send(
                new AdminStudentMessageMail($validated['title'], $validated['body'], $student->name)
            );
        }

        if ($sendToParent) {
            Mail::to($student->parentGuardian->email)->send(
                new AdminStudentMessageMail($validated['title'], $validated['body'], $student->parentGuardian->name)
            );
        }

        Message::create([
            'title'                    => $validated['title'],
            'message'                  => $validated['body'],
            'type'                     => 'email',
            'audience'                 => 'students',
            'status'                   => 'sent',
            'sent_at'                  => now(),
            'student_id'               => $student->id,
            'sent_by'                  => auth()->id(),
            'recipient_student_email'  => $sendToStudent ? $student->email : null,
            'recipient_parent_email'   => $sendToParent ? $student->parentGuardian->email : null,
        ]);

        AuditLog::record('sent', 'Message', $student->id, "Sent message '{$validated['title']}' to {$student->name}" . ($sendToParent ? ' & parent' : ''));

        return redirect()->route('students.show', $student)->with('success', 'Message sent!');
    }

    public function edit(Student $student)
    {
        $academicLevels = \App\Models\AcademicLevel::active()->ordered()->get();
        $academicYears  = \App\Models\AcademicYear::query()
            ->where(fn ($q) => $q->where('is_active', true)
                ->orWhere('id', $student->academic_year_id))
            ->ordered()->get();
        $institutions   = \App\Models\Institution::where('is_active', true)->orderBy('name')->get();
        return view('students.form', compact('student', 'academicLevels', 'academicYears', 'institutions'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate($this->rules($student->id), $this->messages());
        $this->normalizeDateOfBirth($validated);
        $this->assertSectionBelongsToProgram($validated);
        $this->assertSectionBelongsToAcademicYear($validated);
        $this->applyAcademicSelections($validated);

        if ($request->hasFile('photo')) {
            if ($student->photo) Storage::disk('public')->delete($student->photo);
            $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
        }

        $student->adminFill($validated);
        $student->save();

        AuditLog::record('updated', 'Student', $student->id, "Updated student {$student->name} ({$student->student_id})");

        return redirect()->route('students.index')->with('success', 'Student updated successfully!');
    }

    public function destroy(Student $student)
    {
        if ($student->photo) Storage::disk('public')->delete($student->photo);

        $name = $student->name;
        $student->delete();

        $this->syncUsage();
        AuditLog::record('deleted', 'Student', null, "Removed student {$name}");

        return redirect()->route('students.index')->with('success', 'Student deleted successfully!');
    }

    /**
     * Delete several students at once (checkbox selection on the index).
     * Mirrors destroy() per record: photo cleanup, usage sync, audit trail.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:students,id',
        ]);

        $students = Student::whereIn('id', $validated['ids'])->get();
        $names = [];

        DB::transaction(function () use ($students, &$names) {
            foreach ($students as $student) {
                if ($student->photo) Storage::disk('public')->delete($student->photo);
                $names[] = "{$student->name} ({$student->student_id})";
                $student->delete();
            }
        });

        $count = count($names);
        $this->syncUsage();
        AuditLog::record('deleted', 'Student', null, "Bulk removed {$count} student(s): " . Str::limit(implode(', ', $names), 400));

        $message = "{$count} student" . ($count === 1 ? '' : 's') . ' deleted successfully!';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'deleted' => $count]);
        }

        return redirect()->route('students.index')->with('success', $message);
    }

    /**
     * Quick status change from the students index (Suspend / Terminate /
     * Reactivate) — doesn't require opening the full edit form. 'status'
     * is admin-only, so this goes through adminFill() same as update().
     */
    public function updateStatus(Request $request, Student $student)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,graduated,dropped,transferred,terminated,left',
            'reason' => 'nullable|string|max:255',
        ]);

        abort_unless(auth('web')->user()?->isAdmin(), 403);
        return DB::transaction(function () use ($student, $validated) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $student->statusLabel();
            $student->adminFill(['status' => $validated['status']]);
            $student->save();

            $label = $student->statusLabel();
            AuditLog::record(
                'updated',
                'Student',
                $student->id,
                "Student {$student->name} ({$student->student_id}): {$previousStatus} → {$label}" . (($validated['reason'] ?? null) ? " — reason: {$validated['reason']}" : '')
            );

            $state = $student->isActiveEnrollment()
                ? 'Profile reactivated. Student may sign in again; existing records are retained.'
                : 'Profile disabled and read-only until an administrator reactivates it. Existing records and balances are retained.';
            return back()->with('success', "{$student->name} — Student status: {$label}. {$state}");
        });
    }

    public function export(Request $request)
    {

        $query = Student::with('schoolClass', 'program', 'academicYear');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('roll_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->academic_year) $query->where('academic_year_id', $request->academic_year);
        if ($request->program)       $query->where('program_id', $request->program);
        if ($request->class)         $query->where('school_class_id', $request->class);
        if ($request->status)        $query->where('status', $request->status);

        $students = $query->orderBy('program_id')->orderBy('name')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            // .csv opens directly in Excel; this keeps the same export button
            // but with data Excel will parse correctly (fputcsv escapes
            // commas/quotes properly — the old manual string-building broke
            // as soon as any field, e.g. an address, contained a comma).
            'Content-Disposition' => 'attachment; filename="students_' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            // BOM so Excel detects UTF-8 correctly instead of mangling names.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Student ID', 'Name', 'Program', 'Academic Year', 'Semester / Part', 'Section', 'Roll No', 'Guardian', 'Phone', 'Student Status', 'Pending Dues']);
            foreach ($students as $s) {
                fputcsv($out, [
                    $s->student_id,
                    $s->name,
                    $s->program->name ?? '',
                    $s->academicYear->name ?? '',
                    $s->schoolClass->name ?? '',
                    $s->section,
                    $s->roll_number,
                    $s->guardian_name,
                    $s->phone,
                    $s->statusLabel(),
                    $s->pendingDues(),
                ]);
            }
            fclose($out);
        }, 'students_' . now()->format('Y-m-d') . '.csv', $headers);
    }

    /**
     * Create (or reuse) the login User account for a student and return
     * [User $user, string $email, string $plainTextPassword]. Shared by
     * admission (store) and the manual "Generate LMS ID" actions below.
     */
    protected function issueLmsLogin(string $name, ?string $email, string $studentId): array
    {
        // The login ID and password are always randomly generated
        // (e.g. stu-1221-santmarry@cyberorion.pk / pass@331@1189), even if
        // the student also has a personal contact email on file — the
        // personal email stays on the Student record for contact purposes
        // only and is never used as the LMS login.
        $loginEmail = \App\Services\CredentialGenerator::studentEmail();
        $password   = \App\Services\CredentialGenerator::studentPassword();

        $user = User::updateOrCreate(
            ['email' => $loginEmail],
            [
                'name'     => $name,
                'password' => Hash::make($password),
                'role'     => User::ROLE_STUDENT,
            ]
        );

        return [$user, $loginEmail, $password];
    }

    /**
     * Manually issue an LMS login for a student who doesn't have one yet
     * (e.g. admitted before this feature existed, or admission failed to
     * generate one). Shown as a button on the student profile page.
     */
    public function generateLmsId(Student $student)
    {
        if ($student->user_id) {
            return back()->with('error', "{$student->name} already has an LMS login.");
        }

        [$user, $loginEmail, $password] = $this->issueLmsLogin($student->name, $student->email, $student->student_id);
        $student->update(['user_id' => $user->id]);

        AuditLog::record('updated', 'Student', $student->id, "Issued LMS login for {$student->name} ({$student->student_id})");

        return back()->with('success', "LMS login issued for {$student->name} — Email: {$loginEmail} | Password: {$password}")
            ->with('credentials', ['email' => $loginEmail, 'password' => $password, 'name' => $student->name]);
    }

    /**
     * Bulk-issue LMS logins for every student who doesn't have one yet.
     * Used by the "Generate Missing LMS IDs" button on the students list.
     */
    public function generateLmsIdsBulk()
    {
        $missing = Student::activeEnrollment()->whereNull('user_id')->get();

        if ($missing->isEmpty()) {
            return back()->with('success', 'Every active student already has an LMS login — nothing to do.');
        }

        $issued = [];
        foreach ($missing as $student) {
            [$user, $loginEmail, $password] = $this->issueLmsLogin($student->name, $student->email, $student->student_id);
            $student->update(['user_id' => $user->id]);
            $issued[] = ['name' => $student->name, 'email' => $loginEmail, 'password' => $password];
        }

        AuditLog::record('updated', 'Student', null, 'Bulk-issued LMS logins for ' . count($issued) . ' student(s)');

        return back()->with('success', count($issued) . ' LMS login(s) issued.')
            ->with('bulkCredentials', $issued);
    }

    /**
     * Passwords are hashed in the database, so an old one can never be
     * "shown" again — the honest option is to issue a brand new one that
     * can be viewed once. Used by the "Reset Password" button on the
     * student profile page for students who already have an LMS login.
     */
    public function resetLmsPassword(Student $student)
    {
        if (!$student->user_id) {
            return back()->with('error', "{$student->name} doesn't have an LMS login yet — generate one first.");
        }

        $password = \App\Services\CredentialGenerator::studentPassword();
        $student->user->update(['password' => Hash::make($password)]);

        AuditLog::record('updated', 'Student', $student->id, "Reset LMS password for {$student->name} ({$student->student_id})");

        return back()->with('success', "New LMS password for {$student->name} — Email: {$student->user->email} | Password: {$password}")
            ->with('credentials', ['email' => $student->user->email, 'password' => $password, 'name' => $student->name]);
    }
}
