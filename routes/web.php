<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffGateController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Guardian\GuardianDashboardController;
use App\Http\Controllers\LMS\CourseController;
use App\Http\Controllers\LMS\LessonController;
use App\Http\Controllers\LMS\AssignmentController;
use App\Http\Controllers\LMS\QuizController;
use App\Http\Controllers\LMS\AnnouncementController;
use App\Http\Controllers\LMS\MaterialController;
use App\Http\Controllers\LMS\QuestionController;
use App\Http\Controllers\StudentIdCardController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\AcademicLevelController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\FeeHeadController;
use App\Http\Controllers\FeeTemplateController;
use App\Http\Controllers\FeeAssignmentController;
use App\Http\Controllers\InstallmentDateDefaultController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\DailyLedgerController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\LicenseActivationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\TeacherMessageController as AdminTeacherMessageController;
use App\Http\Controllers\Admin\TeacherAnnouncementController;
use App\Http\Controllers\Admin\TeacherActivityController as AdminActivityController;
use App\Http\Controllers\Teacher\TeacherMessageController as TeacherTeacherMessageController;
use App\Http\Controllers\Teacher\ActivityController as TeacherActivityController;
use App\Http\Controllers\{
    StudentController, TeacherController, ClassController, SubjectController,
    TimetableController, AttendanceController, ExaminationController,
    ParentController, LibraryController,
    InventoryController, ReportController, SettingController,
    StaffController, ResultController,
    MedicalLabInventoryController, ComputerLabInventoryController, ExamPaperController
};

// ═══════════════════════════════════════════════════════════════════════════
// MAIN PORTAL — Parent & Student login. This is the app's single public
// entry point at "/". Admin, Teacher and Accountant credentials are
// rejected here even if correct — see StaffGateController below.
// ═══════════════════════════════════════════════════════════════════════════
Route::middleware('guest:web')->group(function () {
    Route::get('/',       [AuthController::class, 'showLogin'])->name('portal.home');
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web')->name('logout');

// ═══════════════════════════════════════════════════════════════════════════
// STAFF LOGIN (Admin / Teacher / Accounts) — never linked from the main
// portal beyond a small "Login as Staff" link. Reaching the actual login
// form requires the correct PIN first; which PIN decides which role's
// form unlocks, and the account that logs in must hold that exact role
// (see StaffGateController / StaffAuthController for the full flow).
// Accountant accounts authenticate on the separate 'accounts' guard —
// there is still no shared session and no way to "switch" between EMS
// and Accounts without signing in again on that system's own guard.
// ═══════════════════════════════════════════════════════════════════════════
Route::middleware('guest:web,accounts')->prefix('staff')->name('staff.')->group(function () {
    Route::get('/gate',  [StaffGateController::class, 'show'])->name('gate');
    Route::post('/gate', [StaffGateController::class, 'verify'])->middleware('throttle:10,1')->name('gate.verify');

    Route::middleware('staffgate')->group(function () {
        Route::get('/login',  [StaffAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [StaffAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    });
});
Route::post('/accounts/logout', [\App\Http\Controllers\AccountsAuthController::class, 'logout'])->middleware('auth:accounts')->name('accounts.logout');

// ── Authenticated root: send people straight to their home screen ───────
Route::middleware('auth:web,accounts')->get('/home', function () {
    return redirect()->route(auth()->user()->dashboardRoute());
})->name('home');

// ── AI Assist — shared across every role (admin, accountant, teacher,
// student, parent), reachable from the topbar button and the sidebar link
// in every module, not just the admin dashboard. ────────────────────────
Route::middleware('auth')->post('/ai-assist/briefing', [App\Http\Controllers\AiAssistController::class, 'briefing'])
    ->name('ai-assist.briefing');

// ── AI Insights — the richer real-time panel (health score, 7-day
// attendance strip, action-required / positive indicators, fees & search
// tabs). Available to admins (school-wide) and teachers (scoped to them).
Route::middleware('auth')->prefix('ai-insights')->name('ai-insights.')->group(function () {
    Route::get('/data',       [App\Http\Controllers\AiInsightsController::class, 'data'])->name('data');
    Route::get('/fees',       [App\Http\Controllers\AiInsightsController::class, 'fees'])->name('fees');
    Route::get('/attendance', [App\Http\Controllers\AiInsightsController::class, 'attendance'])->name('attendance');
    Route::get('/search',     [App\Http\Controllers\AiInsightsController::class, 'search'])->name('search');
});

// ── Global header search — every role (admin, accountant, teacher, ...).
// Record matches are role-scoped inside the controller. ──────────────────
Route::middleware('auth:web,accounts')->get('/global-search', [App\Http\Controllers\GlobalSearchController::class, 'search'])
    ->name('global-search');

// ── Public media & signed verification links (no login required) ───────
Route::get('/media/{path}', [App\Http\Controllers\MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');

Route::get('/verify/student/{student}', [StudentIdCardController::class, 'verify'])
    ->name('students.id-card.verify')
    ->middleware(['signed', 'throttle:30,1']);

Route::get('/verify/fee-plan/{feeAssignment}', [FeeAssignmentController::class, 'verify'])
    ->name('fee-assignments.verify')
    ->middleware(['signed', 'throttle:30,1']);

// ── Notification bell — shared by every logged-in role ─────────────────────
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/poll', [NotificationController::class, 'poll'])->name('poll');
    Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
});

// ═══════════════════════════════════════════════════════════════════════════
// EMS — University Management System (admin, teacher, student, parent, LMS)
// ═══════════════════════════════════════════════════════════════════════════
// Students / Programs / Classes / Subjects keep their original short route names
// (students.index, programs.index, ...) even though they now live under /admin,
// since the existing CRUD views self-reference those names extensively.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
    Route::delete('/students/bulk-delete', [StudentController::class, 'bulkDestroy'])->name('students.bulk-destroy');
    Route::get('/students/print', [StudentController::class, 'print'])->name('students.print');
    Route::post('/students/generate-lms-ids', [StudentController::class, 'generateLmsIdsBulk'])->name('students.generate-lms-ids');
    Route::post('/students/{student}/generate-lms-id', [StudentController::class, 'generateLmsId'])->name('students.generate-lms-id');
    Route::post('/students/{student}/reset-lms-password', [StudentController::class, 'resetLmsPassword'])->name('students.reset-lms-password');
    Route::get('/students/exit-report', [\App\Http\Controllers\StudentExitReportController::class, 'index'])->name('students.exit-report');
    Route::post('/students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.update-status');
    Route::post('/students/{student}/fee-assignments/{feeAssignment}/relief', [\App\Http\Controllers\PostAdmissionAidController::class, 'store'])->name('students.relief.store');
    Route::resource('students', StudentController::class);
    Route::get('/students/{student}/id-card', [StudentIdCardController::class, 'show'])->name('students.id-card');
    Route::post('/students/{student}/id-card/issue', [StudentIdCardController::class, 'issue'])->name('students.id-card.issue');
    Route::post('/students/{student}/id-card/reissue', [StudentIdCardController::class, 'reissue'])->name('students.id-card.reissue');
    Route::get('/classes/{schoolClass}/id-cards/bulk', [StudentIdCardController::class, 'bulk'])->name('classes.id-cards.bulk');
    Route::get('/students/{student}/timetable/print', [TimetableController::class, 'forStudent'])->name('students.timetable.print');

    Route::resource('programs', ProgramController::class);
    Route::get('/programs/{program}/sections', [ProgramController::class, 'sections'])->name('programs.sections');
    Route::get('/programs/{program}/info',     [ProgramController::class, 'info'])->name('programs.info');
    Route::resource('institutions', InstitutionController::class)->except(['show']);
    Route::get('/institutions/{institution}/programs', [InstitutionController::class, 'programs'])->name('institutions.programs');
    Route::resource('academic-levels', AcademicLevelController::class)->except(['show']);
    Route::get('/academic-years/{academicYear}/programs', [AcademicYearController::class, 'programs'])->name('academic-years.programs');
    Route::resource('academic-years', AcademicYearController::class)->except(['show']);
    Route::get('/students/{student}/fee-summary', [StudentController::class, 'feeSummary'])->name('students.fee-summary');
    Route::get('/students/{student}/message', [StudentController::class, 'messageForm'])->name('students.message');
    Route::post('/students/{student}/message', [StudentController::class, 'sendMessage'])->name('students.message.send');
    Route::resource('classes', ClassController::class)->parameters(['classes' => 'schoolClass']);
    Route::resource('subjects', SubjectController::class);
    Route::post('/subjects/assign', [SubjectController::class, 'assign'])->name('subjects.assign');
    Route::put('/subjects/assignment/{assignment}', [SubjectController::class, 'updateAssignment'])->name('subjects.assignment.update');
    Route::delete('/subjects/assignment/{assignment}', [SubjectController::class, 'unassign'])->name('subjects.assignment.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/ai-briefing', [AdminDashboardController::class, 'aiBriefing'])->name('dashboard.ai-briefing');

    // ── Internships — admin posts internships (with an image + challan fee),
    // picks who can see them (all / specific programs / specific students),
    // reviews applications, approves (auto-issues the challan) or rejects. ──
    Route::get('/internships',                     [\App\Http\Controllers\Admin\InternshipController::class, 'index'])->name('internships.index');
    Route::get('/internships/create',               [\App\Http\Controllers\Admin\InternshipController::class, 'create'])->name('internships.create');
    Route::post('/internships',                     [\App\Http\Controllers\Admin\InternshipController::class, 'store'])->name('internships.store');
    Route::get('/internships/{internship}/edit',     [\App\Http\Controllers\Admin\InternshipController::class, 'edit'])->name('internships.edit');
    Route::put('/internships/{internship}',          [\App\Http\Controllers\Admin\InternshipController::class, 'update'])->name('internships.update');
    Route::delete('/internships/{internship}',       [\App\Http\Controllers\Admin\InternshipController::class, 'destroy'])->name('internships.destroy');
    Route::get('/internships/{internship}',          [\App\Http\Controllers\Admin\InternshipController::class, 'show'])->name('internships.show');
    Route::post('/internship-applications/{application}/approve', [\App\Http\Controllers\Admin\InternshipController::class, 'approve'])->name('internship-applications.approve');
    Route::post('/internship-applications/{application}/reject',  [\App\Http\Controllers\Admin\InternshipController::class, 'reject'])->name('internship-applications.reject');

    Route::resource('teachers', TeacherController::class);
    Route::get('/teachers/{teacher}/timetable/print', [TimetableController::class, 'forTeacher'])->name('teachers.timetable.print');
    Route::post('/teachers/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.reset-password');
    Route::get('/teachers/{teacher}/id-card', [TeacherController::class, 'idCard'])->name('teachers.idcard');

    // LMS monitoring — read-only drill-down: Teachers -> their subjects -> classes/courses -> course detail
    // (course detail itself is the shared lms.courses.show page, gated read-only for admin there).
    Route::get('/lms/teachers', [\App\Http\Controllers\Admin\LmsMonitorController::class, 'teachers'])->name('lms.teachers');
    Route::get('/lms/teachers/{teacher}/subjects', [\App\Http\Controllers\Admin\LmsMonitorController::class, 'teacherSubjects'])->name('lms.teacher-subjects');
    Route::get('/lms/teachers/{teacher}/subjects/{subject}/courses', [\App\Http\Controllers\Admin\LmsMonitorController::class, 'teacherCourses'])->name('lms.teacher-courses')->where('subject', '[0-9]+');

    // Timetable
    Route::get('/timetable',                [TimetableController::class, 'index'])->name('timetable.index');
    Route::get('/timetable/print',          [TimetableController::class, 'printAll'])->name('timetable.print');
    Route::put('/timetable/break',          [TimetableController::class, 'updateBreak'])->name('timetable.break.update');
    Route::post('/timetable',               [TimetableController::class, 'store'])->name('timetable.store');
    Route::put('/timetable/{timetable}',    [TimetableController::class, 'update'])->name('timetable.update');
    Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy');
    Route::put('/timetable/teacher-color/{teacher}', [TimetableController::class, 'updateTeacherColor'])->name('timetable.teacher-color.update');

    // Student Attendance
    Route::get('/attendance',        [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/save',  [AttendanceController::class, 'save'])->name('attendance.save');
    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('/attendance/print',  [AttendanceController::class, 'print'])->name('attendance.print');

    // Examinations — bulk scheduling wizard (program -> classes -> subjects/teachers -> date & time)
    Route::post('/examinations/controller-signature',    [ExaminationController::class, 'updateControllerSignature'])->name('examinations.controller-signature.update');
    Route::get('/examinations/schedule',                [ExaminationController::class, 'scheduleForm'])->name('examinations.schedule');
    Route::post('/examinations/schedule',                [ExaminationController::class, 'storeSchedule'])->name('examinations.schedule.store');
    Route::get('/examinations/class-subjects',           [ExaminationController::class, 'classSubjects'])->name('examinations.class-subjects');
    Route::get('/examinations/date-sheet/print',         [ExaminationController::class, 'printDateSheet'])->name('examinations.date-sheet.print');
    Route::get('/examinations/report',                   [ExaminationController::class, 'report'])->name('examinations.report');
    Route::get('/examinations/report/export',             [ExaminationController::class, 'reportExport'])->name('examinations.report.export');
    Route::delete('/examinations/batch/{batchId}',        [ExaminationController::class, 'destroyBatch'])->name('examinations.batch.destroy')->where('batchId', '[0-9a-fA-F-]{36}');

    // Examination papers — teacher-wise review of submitted question papers.
    Route::get('/examinations/papers',                    [ExamPaperController::class, 'adminIndex'])->name('examinations.papers.index');
    Route::get('/examinations/papers/{examPaper}/print',  [ExamPaperController::class, 'print'])->name('examinations.papers.print');
    Route::patch('/examinations/{examination}/date',      [ExaminationController::class, 'updateExamDate'])->name('examinations.date.update');
    Route::post('/examinations/{examination}/lock',       [ExaminationController::class, 'lock'])->name('examinations.lock');
    Route::post('/examinations/{examination}/unlock',     [ExaminationController::class, 'unlock'])->name('examinations.unlock');
    Route::get('/examinations/{examination}/results',    [ExaminationController::class, 'results'])->name('examinations.results');
    Route::post('/examinations/{examination}/results',   [ExaminationController::class, 'storeResults'])->name('examinations.results.store');
    Route::get('/examinations',                          [ExaminationController::class, 'index'])->name('examinations.index');
    Route::get('/examinations/{examination}',             [ExaminationController::class, 'show'])->name('examinations.show');
    Route::get('/examinations/{examination}/edit',        [ExaminationController::class, 'edit'])->name('examinations.edit');
    Route::put('/examinations/{examination}',              [ExaminationController::class, 'update'])->name('examinations.update');
    Route::delete('/examinations/{examination}',           [ExaminationController::class, 'destroy'])->name('examinations.destroy');

    // Results & Marksheets — compile per-subject marks (entered by teachers above) into a
    // single combined marksheet per class, previewed/downloaded individually or in bulk,
    // and only shown to students/parents once explicitly published.
    Route::get('/results',                                     [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/by-student',                           [ResultController::class, 'byStudent'])->name('results.by-student');
    Route::get('/results/{batchId}/class/{schoolClass}',         [ResultController::class, 'classShow'])->name('results.class')->where('batchId', '[A-Za-z0-9-]+');
    Route::post('/results/{batchId}/class/{schoolClass}/publish',   [ResultController::class, 'publish'])->name('results.publish')->where('batchId', '[A-Za-z0-9-]+');
    Route::post('/results/{batchId}/class/{schoolClass}/unpublish', [ResultController::class, 'unpublish'])->name('results.unpublish')->where('batchId', '[A-Za-z0-9-]+');
    Route::get('/results/{batchId}/class/{schoolClass}/download',   [ResultController::class, 'classBulk'])->name('results.class.download')->where('batchId', '[A-Za-z0-9-]+');
    Route::get('/results/{batchId}/class/{schoolClass}/student/{student}', [ResultController::class, 'studentMarksheet'])->name('results.student')->where('batchId', '[A-Za-z0-9-]+');

    // Grading Scale — admin's custom grading formula, linked into every result marksheet.
    Route::get('/grading-scale',                [\App\Http\Controllers\Admin\GradingScaleController::class, 'index'])->name('grading-scale.index');
    Route::post('/grading-scale',                [\App\Http\Controllers\Admin\GradingScaleController::class, 'store'])->name('grading-scale.store');
    Route::put('/grading-scale/{gradingScale}',  [\App\Http\Controllers\Admin\GradingScaleController::class, 'update'])->name('grading-scale.update');
    Route::delete('/grading-scale/{gradingScale}', [\App\Http\Controllers\Admin\GradingScaleController::class, 'destroy'])->name('grading-scale.destroy');

    // Daily Ledger — Admin can view the module (entries + remarks) but can
    // never add an entry or a remark; those routes only exist on the
    // Accounts ('accounts' guard) group above.
    Route::get('/daily-ledger',              [DailyLedgerController::class, 'index'])->name('daily-ledger.index');
    Route::get('/daily-ledger/weekly',       [DailyLedgerController::class, 'weekly'])->name('daily-ledger.weekly');
    Route::get('/daily-ledger/yearly',       [DailyLedgerController::class, 'yearly'])->name('daily-ledger.yearly');
    Route::get('/daily-ledger/print/daily',  [DailyLedgerController::class, 'printDaily'])->name('daily-ledger.print.daily');
    Route::get('/daily-ledger/print/weekly', [DailyLedgerController::class, 'printWeekly'])->name('daily-ledger.print.weekly');
    Route::get('/daily-ledger/print/yearly', [DailyLedgerController::class, 'printYearly'])->name('daily-ledger.print.yearly');

    // Staff (non-teaching employees)
    Route::resource('staff', StaffController::class);

    // Parents (guardian portal accounts)
    Route::resource('parents', ParentController::class);
    Route::post('/parents/{parent}/reset-password', [ParentController::class, 'resetPassword'])->name('parents.reset-password');

    // Library / Inventory
    Route::get('/library/students-lookup',      [LibraryController::class, 'studentLookup'])->name('library.students.lookup');
    Route::get('/library/issued-books',         [LibraryController::class, 'issuedBooks'])->name('library.issued');
    Route::get('/library/{library}/issue',      [LibraryController::class, 'issueForm'])->name('library.issue.form');
    Route::post('/library/{library}/issue',     [LibraryController::class, 'issue'])->name('library.issue');
    Route::post('/library/issues/{bookIssue}/return', [LibraryController::class, 'returnIssue'])->name('library.issues.return');
    Route::resource('library', LibraryController::class);

    Route::resource('inventory', InventoryController::class);
    Route::get('/medical-lab-inventory/export/pdf', [MedicalLabInventoryController::class, 'exportPdf'])->name('medical-lab-inventory.export-pdf');
    Route::resource('medical-lab-inventory', MedicalLabInventoryController::class);
    Route::resource('computer-lab-inventory', ComputerLabInventoryController::class);

    // EMS-side reports (student directory & attendance — financial reports live on Accounts)
    Route::get('/reports/general', [\App\Http\Controllers\GeneralStudentReportController::class, 'index'])->name('reports.general');
    Route::get('/reports/general/export', [\App\Http\Controllers\GeneralStudentReportController::class, 'export'])->name('reports.general.export');
    Route::get('/reports',            [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/students',   [ReportController::class, 'studentsReport'])->name('reports.students');
    Route::get('/reports/student-status', [ReportController::class, 'studentStatusReport'])->name('reports.student-status');
    Route::get('/reports/student-status/print', [ReportController::class, 'studentStatusReportPrint'])->name('reports.student-status.print');
    Route::get('/reports/students/{student}', [ReportController::class, 'studentReportShow'])->name('reports.students.show');
    Route::get('/reports/students/{student}/print', [ReportController::class, 'studentReportPrint'])->name('reports.students.print');
    Route::get('/reports/students/{student}/pdf', [ReportController::class, 'studentReportPdf'])->name('reports.students.pdf');
    Route::post('/reports/students/{student}/remarks', [ReportController::class, 'storeStudentRemark'])->name('reports.students.remarks.store');
    Route::delete('/reports/students/{student}/remarks/{remark}', [ReportController::class, 'destroyStudentRemark'])->name('reports.students.remarks.destroy');
    Route::get('/reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance');
    Route::get('/reports/attendance/print', [ReportController::class, 'attendanceReportPrint'])->name('reports.attendance.print');
    Route::get('/reports/attendance/pdf', [ReportController::class, 'attendanceReportPdf'])->name('reports.attendance.pdf');
    Route::get('/reports/attendance/student/{student}', [ReportController::class, 'studentAttendanceReport'])->name('reports.attendance.student');
    Route::get('/reports/attendance/student/{student}/pdf', [ReportController::class, 'studentAttendancePdf'])->name('reports.attendance.student.pdf');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    Route::post('/reports/ai-summary',[ReportController::class, 'aiSummary'])->name('reports.ai-summary');

    // ── Reporting Center: scholarships, fee (pending/paid), student search, attendance ──
    Route::prefix('reporting')->name('reporting.')->group(function () {
        Route::get('/scholarship',        [\App\Http\Controllers\Admin\ReportingController::class, 'scholarship'])->name('scholarship');
        Route::get('/scholarship/print',  [\App\Http\Controllers\Admin\ReportingController::class, 'scholarshipPrint'])->name('scholarship.print');
        Route::get('/fee-pending',        [\App\Http\Controllers\Admin\ReportingController::class, 'feePending'])->name('fee-pending');
        Route::get('/fee-pending/print',  [\App\Http\Controllers\Admin\ReportingController::class, 'feePendingPrint'])->name('fee-pending.print');
        Route::get('/fee-paid',           [\App\Http\Controllers\Admin\ReportingController::class, 'feePaid'])->name('fee-paid');
        Route::get('/fee-paid/print',     [\App\Http\Controllers\Admin\ReportingController::class, 'feePaidPrint'])->name('fee-paid.print');
        Route::post('/fee-remarks/{type}/{feeAssignment}', [\App\Http\Controllers\Admin\ReportingController::class, 'saveFeeReportRemark'])->name('fee-remarks.save');
        Route::get('/students/search',    [\App\Http\Controllers\Admin\ReportingController::class, 'studentSearch'])->name('student-search');
        Route::get('/students/{student}/profile-print', [\App\Http\Controllers\Admin\ReportingController::class, 'studentProfilePrint'])->name('student-profile.print');
        Route::get('/attendance-summary',       [\App\Http\Controllers\Admin\ReportingController::class, 'attendanceSummary'])->name('attendance-summary');
        Route::get('/attendance-summary/print', [\App\Http\Controllers\Admin\ReportingController::class, 'attendanceSummaryPrint'])->name('attendance-summary.print');
    });

    // ── Chat: message a specific teacher ────────────────────────────────
    Route::get('/messages',                    [AdminTeacherMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{teacher}',          [AdminTeacherMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{teacher}',         [AdminTeacherMessageController::class, 'store'])->name('messages.store');

    // ── Announcements for teachers (shown on their dashboard) ───────────
    Route::get('/announcements',               [TeacherAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements',              [TeacherAnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [TeacherAnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // ── Assign Tours / Projects / Practicals / Assignments / Surveys ────
    Route::get('/activities',                  [AdminActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/requests',         [AdminActivityController::class, 'requests'])->name('activities.requests');
    Route::post('/activities/tours',           [AdminActivityController::class, 'storeTour'])->name('activities.tours.store');
    Route::post('/activities/projects',        [AdminActivityController::class, 'storeProject'])->name('activities.projects.store');
    Route::post('/activities/practicals',      [AdminActivityController::class, 'storePractical'])->name('activities.practicals.store');
    Route::post('/activities/practicals/{practical}/review', [AdminActivityController::class, 'reviewPractical'])->name('activities.practicals.review');
    Route::post('/activities/surveys',         [AdminActivityController::class, 'storeSurvey'])->name('activities.surveys.store');
    Route::post('/activities/assignments',     [AdminActivityController::class, 'storeAssignmentRequest'])->name('activities.assignments.store');
    Route::post('/activities/assignments/{assignment}/review', [AdminActivityController::class, 'reviewAssignment'])->name('activities.assignments.review');
    Route::post('/activities/quizzes',         [AdminActivityController::class, 'storeQuizRequest'])->name('activities.quizzes.store');
    Route::post('/activities/quizzes/{quiz}/review', [AdminActivityController::class, 'reviewQuiz'])->name('activities.quizzes.review');
});

// ── Teacher Portal ────────────────────────────────────────────
Route::middleware(['auth', 'role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () {

    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::get('/timetable',       [TimetableController::class, 'myTeacherSchedulePage'])->name('timetable.index');
    Route::get('/timetable/print', [TimetableController::class, 'myTeacherSchedulePrint'])->name('timetable.print');

    Route::get('/attendance',        [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/save',  [AttendanceController::class, 'save'])->name('attendance.save');
    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('/attendance/print',  [AttendanceController::class, 'print'])->name('attendance.print');

    // Examinations — teachers only ever see and mark their own assigned exam duties.
    // Scheduling, editing, and deleting examinations is admin-only.
    Route::get('/examinations',                        [ExaminationController::class, 'index'])->name('examinations.index');
    Route::get('/examinations/{examination}/results',  [ExaminationController::class, 'results'])->name('examinations.results');
    Route::post('/examinations/{examination}/results', [ExaminationController::class, 'storeResults'])->name('examinations.results.store');

    // Examination papers — build/submit the question paper for each of my assigned exams.
    Route::get('/examinations/papers',                    [ExamPaperController::class, 'teacherIndex'])->name('examinations.papers.index');
    Route::get('/examinations/papers/{examPaper}/build',  [ExamPaperController::class, 'build'])->name('examinations.papers.build');
    Route::post('/examinations/papers/{examPaper}',       [ExamPaperController::class, 'save'])->name('examinations.papers.save');
    Route::get('/examinations/papers/{examPaper}/print',  [ExamPaperController::class, 'print'])->name('examinations.papers.print');

    Route::get('/examinations/{examination}',          [ExaminationController::class, 'show'])->name('examinations.show');

    // Read-only fee visibility for the teacher's own students ("Student Fee
    // without Heads": Upcoming / Due / Previous balance, highlighted).
    Route::get('/students/fees',            [TeacherDashboardController::class, 'studentFees'])->name('students.fees');
    Route::get('/students/{student}/fees',  [TeacherDashboardController::class, 'studentFeeDetail'])->name('students.fee-detail');

    // ── Chat with Admin ──────────────────────────────────────────────────
    Route::get('/messages',  [TeacherTeacherMessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [TeacherTeacherMessageController::class, 'store'])->name('messages.store');

    // ── Activities assigned by Admin (Tours/Projects/Practicals/Assignments/Surveys) ──
    Route::get('/activities', [TeacherActivityController::class, 'index'])->name('activities.index');
    Route::post('/activities/tours/{tour}/acknowledge',       [TeacherActivityController::class, 'acknowledgeTour'])->name('activities.tours.acknowledge');
    Route::post('/activities/projects/{project}/submit',      [TeacherActivityController::class, 'submitProject'])->name('activities.projects.submit');
    Route::post('/activities/practicals/{practical}/submit',  [TeacherActivityController::class, 'submitPractical'])->name('activities.practicals.submit');
    Route::post('/activities/surveys/{survey}/submit',        [TeacherActivityController::class, 'submitSurvey'])->name('activities.surveys.submit');
    Route::post('/activities/assignments/{assignment}/submit',[TeacherActivityController::class, 'submitAssignment'])->name('activities.assignments.submit');
    Route::post('/activities/assignments/{assignment}/link', [TeacherActivityController::class, 'linkAssignment'])->name('activities.assignments.link');
    Route::post('/activities/quizzes/{quiz}/link', [TeacherActivityController::class, 'linkQuiz'])->name('activities.quizzes.link');
    Route::post('/activities/quizzes/{quiz}/submit',          [TeacherActivityController::class, 'submitQuiz'])->name('activities.quizzes.submit');

});

// ── LMS ───────────────────────────────────────────────────────
// Read-only access: teachers, students, AND admin (admin monitors everything
// but must never be able to create/edit/delete/grade — enforced by keeping
// every mutating route out of this group and only into the role-scoped
// groups below).
Route::middleware(['web', 'auth', 'role:teacher,admin,student'])->prefix('lms')->name('lms.')->group(function () {
    Route::get('/courses/browse',                 [CourseController::class, 'browse'])->name('courses.browse');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show')->whereNumber('course');
    Route::get('/courses/{course}/lessons/{lesson}',       [LessonController::class, 'show'])->name('lessons.show')->whereNumber('lesson');
    Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show')->whereNumber('assignment');
    Route::get('/courses/{course}/quizzes/{quiz}',         [QuizController::class, 'show'])->name('quizzes.show')->whereNumber('quiz');
});

// Teacher-only: full course/content management + grading + answering questions.
// Admin is intentionally excluded from this group — read-only per spec.
Route::middleware(['web', 'auth', 'role:teacher'])->prefix('lms')->name('lms.')->group(function () {
    Route::resource('courses', CourseController::class)->except(['index', 'show']);

    Route::get('/courses/{course}/lessons/create',         [LessonController::class, 'create'])->name('lessons.create');
    Route::post('/courses/{course}/lessons',               [LessonController::class, 'store'])->name('lessons.store');
    Route::get('/courses/{course}/lessons/{lesson}/edit',  [LessonController::class, 'edit'])->name('lessons.edit');
    Route::put('/courses/{course}/lessons/{lesson}',       [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('/courses/{course}/lessons/{lesson}',    [LessonController::class, 'destroy'])->name('lessons.destroy');

    Route::get('/courses/{course}/assignments/create',                                    [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/courses/{course}/assignments',                                          [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/courses/{course}/assignments/{assignment}/edit',                         [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/courses/{course}/assignments/{assignment}',                              [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/courses/{course}/assignments/{assignment}',                           [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::post('/courses/{course}/assignments/{assignment}/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');

    Route::get('/courses/{course}/quizzes/create',    [QuizController::class, 'create'])->name('quizzes.create');
    Route::get('/courses/{course}/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit')->whereNumber('quiz');
    Route::put('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update')->whereNumber('quiz');
    Route::post('/courses/{course}/quizzes',          [QuizController::class, 'store'])->name('quizzes.store');
    Route::delete('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    Route::post('/courses/{course}/announcements',                  [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/courses/{course}/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::post('/courses/{course}/materials',                 [MaterialController::class, 'store'])->name('materials.store');
    Route::delete('/courses/{course}/materials/{material}',    [MaterialController::class, 'destroy'])->name('materials.destroy');

    Route::post('/courses/{course}/questions/{question}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
});

// Student-only: enrolling, taking quizzes, submitting work, marking lessons
// complete, and asking the teacher questions (capped at 3/day).
Route::middleware(['web', 'auth', 'role:student'])->prefix('lms')->name('lms.')->group(function () {
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');

    Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'markComplete'])->name('lessons.complete');

    Route::post('/courses/{course}/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');

    Route::get('/courses/{course}/quizzes/{quiz}/take',    [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/courses/{course}/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');

    Route::post('/courses/{course}/questions', [QuestionController::class, 'store'])->name('questions.store');
});

// ── Student Portal ────────────────────────────────────────────
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/timetable',       [TimetableController::class, 'myStudentSchedulePage'])->name('timetable.index');
    Route::get('/timetable/print', [TimetableController::class, 'myStudentSchedulePrint'])->name('timetable.print');

    // Exams & Results — read-only: upcoming exam schedule + published results for this student's class.
    Route::get('/exams', [StudentDashboardController::class, 'exams'])->name('exams.index');

    // My Marksheets — compiled, published results only. Downloadable (print-to-PDF).
    Route::get('/results',              [ResultController::class, 'myResults'])->name('results.index');
    Route::get('/results/{batchId}',    [ResultController::class, 'myMarksheet'])->name('results.show')->where('batchId', '[A-Za-z0-9-]+');

    Route::get('/learning-tasks', [\App\Http\Controllers\Student\LearningTaskController::class, 'index'])->name('learning-tasks.index');

    // LMS grades — assignment marks, quiz results, and overall marks per course.
    Route::get('/grades', [StudentDashboardController::class, 'grades'])->name('grades');

    // Read-only Accounts data: installment plans + vouchers for this student only.
    Route::get('/fees',                          [StudentDashboardController::class, 'fees'])->name('fees');
    Route::get('/fee-plans/{feeAssignment}',      [StudentDashboardController::class, 'feePlan'])->name('fee-plan');
    Route::get('/vouchers/{voucher}',             [StudentDashboardController::class, 'voucher'])->name('voucher');

    // ── Internships — see what admin has posted for you, apply, and track
    // status (pending / approved+awaiting payment / confirmed / rejected). ──
    Route::get('/internships',                    [\App\Http\Controllers\Student\InternshipController::class, 'index'])->name('internships.index');
    Route::post('/internships/{internship}/apply', [\App\Http\Controllers\Student\InternshipController::class, 'apply'])->name('internships.apply');

});

// ── Parent Portal ─────────────────────────────────────────────
Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/dashboard',       [GuardianDashboardController::class, 'index'])->name('dashboard');
    Route::get('/child/{student}', [GuardianDashboardController::class, 'childDetail'])->name('child');
    Route::get('/child/{student}/exams', [GuardianDashboardController::class, 'childExams'])->name('child.exams');
    Route::get('/child/{student}/fees',  [GuardianDashboardController::class, 'childFees'])->name('child.fees');

    // My Child's Marksheets — compiled, published results only. Downloadable (print-to-PDF).
    Route::get('/child/{student}/results',            [ResultController::class, 'childResults'])->name('child.results');
    Route::get('/child/{student}/results/{batchId}',  [ResultController::class, 'childMarksheet'])->name('child.results.show')->where('batchId', '[A-Za-z0-9-]+');

    // Read-only Accounts data for this parent's own children.
    Route::get('/fee-plans/{feeAssignment}', [GuardianDashboardController::class, 'feePlan'])->name('fee-plan');
    Route::get('/vouchers/{voucher}',        [GuardianDashboardController::class, 'voucher'])->name('voucher');
});

// ═══════════════════════════════════════════════════════════════════════════
// ACCOUNTS — University Accounts Management System
//
// Two distinct middleware groups below, deliberately:
//
// (A) Admin still generates installment plans and vouchers/challans as part
//     of the admission flow, and can view (read-only) every voucher and
//     payment status — this happens entirely inside admin's own EMS ('web')
//     session; it is NOT "entering the Accounts module" and never requires
//     the Accounts login. An accountant, once separately signed in on the
//     'accounts' guard, can also generate challans/installments from the
//     already-defined fee structure (never manual amounts).
//
// (B) The actual Accounts department module — fee structure (Fee Heads /
//     Fee Templates), receiving & verifying payments, editing/cancelling
//     vouchers, bank accounts, the Ledger, Financial Reports, the Audit Log,
//     and Live Payments — is reachable ONLY on the 'accounts' guard, i.e.
//     only after signing in separately at /accounts/login as an accountant.
//     Admin has no route into this group at all — no shared/unauthenticated
//     switching, and it is "accessible and manageable only by Accounts".
// ═══════════════════════════════════════════════════════════════════════════
Route::middleware(['auth:web,accounts', 'role:admin,accountant'])->prefix('accounts')->group(function () {

    // ── Fee Assignment (installment plans) — part of the admission flow ──
    Route::get('/students/{student}/fee-assignment/create', [FeeAssignmentController::class, 'create'])->name('fee-assignments.create');
    Route::post('/students/{student}/fee-assignment',       [FeeAssignmentController::class, 'store'])->name('fee-assignments.store');
    Route::get('/fee-assignments/{feeAssignment}',           [FeeAssignmentController::class, 'show'])->name('fee-assignments.show');
    Route::get('/fee-assignments/{feeAssignment}/plan',      [FeeAssignmentController::class, 'plan'])->name('fee-assignments.plan');
    Route::get('/fee-assignments/{feeAssignment}/summary',   [FeeAssignmentController::class, 'summary'])->name('fee-assignments.summary');
    Route::get('/fee-assignments/{feeAssignment}/cycles/{cycle}/summary', [FeeAssignmentController::class, 'summary'])->name('fee-assignments.cycle-summary');
    Route::get('/fee-assignments/{feeAssignment}/renew',     [FeeAssignmentController::class, 'renew'])->name('fee-assignments.renew');
    Route::post('/fee-assignments/{feeAssignment}/renew',    [FeeAssignmentController::class, 'renewStore'])->name('fee-assignments.renew.store');
    Route::post('/fee-assignments/{feeAssignment}/cycles/{cycle}/split', [FeeAssignmentController::class, 'splitCycle'])->name('fee-assignments.split-cycle');
    Route::post('/fee-assignments/{feeAssignment}/cycles/{cycle}/toggle-lock', [FeeAssignmentController::class, 'toggleCycleLock'])->name('fee-assignments.toggle-cycle-lock');
    Route::put('/fee-assignments/{feeAssignment}/cycles/{cycle}/due-dates', [FeeAssignmentController::class, 'updateCycleDueDates'])->name('fee-assignments.update-cycle-due-dates');

    // ── Default installment due dates — shared by Admin + Accounts ──
    Route::get('/installment-date-defaults', [InstallmentDateDefaultController::class, 'index'])->name('installment-date-defaults.index');
    Route::post('/installment-date-defaults', [InstallmentDateDefaultController::class, 'store'])->name('installment-date-defaults.store');
    Route::get('/installment-date-defaults/{installmentDateDefault}/edit', [InstallmentDateDefaultController::class, 'edit'])->name('installment-date-defaults.edit');
    Route::put('/installment-date-defaults/{installmentDateDefault}', [InstallmentDateDefaultController::class, 'update'])->name('installment-date-defaults.update');
    Route::delete('/installment-date-defaults/{installmentDateDefault}', [InstallmentDateDefaultController::class, 'destroy'])->name('installment-date-defaults.destroy');

    // ── Vouchers / Challans — generate, view (read-only for admin), print ──
    Route::get('/vouchers/{voucher}/receipt',      [VoucherController::class, 'receipt'])->name('vouchers.receipt');
    Route::get('/vouchers/{voucher}/print',        [VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('/vouchers/bulk-receipts',          [VoucherController::class, 'bulkReceipts'])->name('vouchers.bulk-receipts');
    Route::get('/vouchers/generated-batch/{batchId}/print', [VoucherController::class, 'printGeneratedBatch'])->name('vouchers.generated-batch.print');
    Route::post('/vouchers/generate-installments', [VoucherController::class, 'generateInstallments'])->name('vouchers.generate-installments');
    Route::get('/vouchers',                        [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/export',                 [VoucherController::class, 'export'])->name('vouchers.export');
    Route::post('/vouchers/export-selected',        [VoucherController::class, 'exportSelectedStudents'])->name('vouchers.export-selected');
    Route::get('/vouchers/create',                 [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/vouchers',                       [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/vouchers/{voucher}',              [VoucherController::class, 'show'])->name('vouchers.show');
});

// ── Accounts module proper — accountant-only, guard 'accounts' only ─────
Route::middleware(['auth:accounts', 'role:accountant'])->prefix('accounts')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('accounts.dashboard');
    Route::get('/dashboard/student-explorer', [DashboardController::class, 'studentExplorer'])->name('accounts.dashboard.student-explorer');
    Route::post('/dashboard/student-explorer/print', [DashboardController::class, 'printSelectedStudents'])->name('accounts.dashboard.student-explorer.print');

    // ── Fee Structure — moved out of Admin entirely, Accounts-managed ──
    Route::resource('fee-heads', FeeHeadController::class);
    Route::resource('fee-templates', FeeTemplateController::class);

    // ── Program → Parts/Semesters lookup, for the Fee Template form's
    //    cascading dropdown. Accounts signs in on its own 'accounts' guard,
    //    fully separate from the Admin 'web' guard that admin/programs.sections
    //    lives under, so that route is unreachable here — this is the
    //    accountant-accessible equivalent of the same read-only endpoint.
    Route::get('/programs/{program}/sections', [\App\Http\Controllers\ProgramController::class, 'sections'])->name('accounts.programs.sections');

    // ── Live Payments feed ──
    Route::get('/live-payments',       [\App\Http\Controllers\LivePaymentController::class, 'index'])->name('live-payments.index');
    Route::get('/live-payments/feed',  [\App\Http\Controllers\LivePaymentController::class, 'feed'])->name('live-payments.feed');

    // ── Bank Accounts ──
    Route::post('/bank-accounts',              [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // ── Vouchers — corrections stay with Accounts ──
    Route::post('/vouchers/{voucher}/cancel', [VoucherController::class, 'cancel'])->name('vouchers.cancel');
    Route::post('/vouchers/{voucher}/split-challan', [VoucherController::class, 'splitChallan'])->name('vouchers.split-challan');
    Route::get('/vouchers/{voucher}/edit',    [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::put('/vouchers/{voucher}',         [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/vouchers/{voucher}',      [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    // ── Payments — this is where the fee is actually submitted/received ──
    Route::get('/receive-payment',              [PaymentController::class, 'receiveForm'])->name('payments.receive-form');
    Route::get('/vouchers-lookup',               [VoucherController::class, 'lookup'])->name('vouchers.lookup');
    Route::post('/vouchers/{voucher}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/students/{student}/payments',  [PaymentController::class, 'storeForStudent'])->name('payments.store-for-student');
    // Exactly one collection, even if its money was split across vouchers.
    Route::get('/students/{student}/received-payments/{payment}/receipt', [\App\Http\Controllers\ReceivedPaymentReceiptController::class, 'show'])
        ->whereNumber('payment')->name('payments.received-receipt.show');
    Route::post('/students/{student}/balance-credit', [PaymentController::class, 'storeBalanceCredit'])->name('payments.balance-credit');
    Route::post('/students/{student}/apply-credit', [PaymentController::class, 'applyBalanceCredit'])->name('payments.apply-credit');
    // Persisted extra-payment receipts: Accounts-only, read-only, student-scoped.
    Route::get('/students/{student}/extra-payment-receipts', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'index'])->name('payments.extra-receipts.index');
    Route::get('/students/{student}/extra-payment-receipts/ledger/{transaction}', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'show'])->whereNumber('transaction')->name('payments.extra-receipts.show');
    Route::get('/students/{student}/extra-payment-receipts/legacy/{auditLog}', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'legacy'])->whereNumber('auditLog')->name('payments.extra-receipts.legacy');
    Route::get('/students/{student}/extra-payment-receipts/ledger/{transaction}/download', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'download'])->whereNumber('transaction')->name('payments.extra-receipts.download');
    Route::get('/students/{student}/extra-payment-receipts/legacy/{auditLog}/download', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'legacyDownload'])->whereNumber('auditLog')->name('payments.extra-receipts.legacy-download');
    // Paid voucher-format copies of existing receipts only; these GET actions cannot receive money.
    Route::get('/students/{student}/extra-payment-receipts/ledger/{transaction}/voucher', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'voucher'])->whereNumber('transaction')->name('payments.extra-receipts.voucher');
    Route::get('/students/{student}/extra-payment-receipts/legacy/{auditLog}/voucher', [\App\Http\Controllers\ExtraPaymentReceiptController::class, 'legacyVoucher'])->whereNumber('auditLog')->name('payments.extra-receipts.legacy-voucher');
    Route::post('/students/{student}/fee-assignments/{feeAssignment}/relief', [\App\Http\Controllers\PostAdmissionAidController::class, 'store'])->name('accounts.students.relief.store');
    Route::delete('/payments/{payment}',        [PaymentController::class, 'destroy'])->name('payments.destroy');

    // ── Online payment proof — student-submitted, awaiting verification ──
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    // ── Ledger & Accounting ──
    Route::get('/ledger',                    [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/students/{student}', [LedgerController::class, 'student'])->name('ledger.student');
    Route::get('/ledger/general',            [LedgerController::class, 'general'])->name('ledger.general');

    // ── Daily Ledger (cash book: In / Out / Expense) — Accounts only ──
    // Entry details can be edited only on the same calendar date. From the
    // next date onward they are read-only and corrections use remarks. Entries
    // can never be deleted. New-entry availability still follows the day lock.
    Route::get('/daily-ledger',              [DailyLedgerController::class, 'index'])->name('daily-ledger.index');
    Route::post('/daily-ledger',             [DailyLedgerController::class, 'store'])->name('daily-ledger.store');
    Route::put('/daily-ledger/{entry}',       [DailyLedgerController::class, 'update'])->name('daily-ledger.update');
    Route::post('/daily-ledger/{entry}/remark', [DailyLedgerController::class, 'remark'])->name('daily-ledger.remark');
    Route::post('/daily-ledger/closing',     [DailyLedgerController::class, 'saveClosing'])->name('daily-ledger.closing.store');
    Route::get('/daily-ledger/weekly',       [DailyLedgerController::class, 'weekly'])->name('daily-ledger.weekly');
    Route::get('/daily-ledger/yearly',       [DailyLedgerController::class, 'yearly'])->name('daily-ledger.yearly');
    Route::get('/daily-ledger/print/daily',  [DailyLedgerController::class, 'printDaily'])->name('daily-ledger.print.daily');
    Route::get('/daily-ledger/print/weekly', [DailyLedgerController::class, 'printWeekly'])->name('daily-ledger.print.weekly');
    Route::get('/daily-ledger/print/yearly', [DailyLedgerController::class, 'printYearly'])->name('daily-ledger.print.yearly');

    // ── Financial Reports ──
    Route::get('/reports/general', [\App\Http\Controllers\GeneralStudentReportController::class, 'index'])->name('accounts.reports.general');
    Route::get('/reports/general/export', [\App\Http\Controllers\GeneralStudentReportController::class, 'export'])->name('accounts.reports.general.export');
    Route::get('/reports',                [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/collection',     [ReportController::class, 'collection'])->name('reports.collection');
    Route::get('/reports/defaulters',     [ReportController::class, 'defaulters'])->name('reports.defaulters');
    Route::get('/reports/class-revenue',  [ReportController::class, 'classRevenue'])->name('reports.class-revenue');
    Route::get('/reports/installments',   [ReportController::class, 'installments'])->name('reports.installments');
    Route::get('/reports/discounts',      [ReportController::class, 'discounts'])->name('reports.discounts');
    Route::get('/reports/income-expense', [ReportController::class, 'incomeExpense'])->name('reports.income-expense');
    Route::get('/reports/export/{type}',  [ReportController::class, 'export'])->name('reports.export');

    // ── Institution Reporting Center — same read-only reports available to Admin ──
    Route::post('/reports/ai-summary', [ReportController::class, 'aiSummary'])->name('accounts.reports.ai-summary');
    Route::get('/reports/students',    [ReportController::class, 'studentsReport'])->name('accounts.reports.students');
    Route::get('/reports/students/{student}', [ReportController::class, 'studentReportShow'])->name('accounts.reports.students.show');
    Route::get('/reports/students/{student}/print', [ReportController::class, 'studentReportPrint'])->name('accounts.reports.students.print');
    Route::get('/reports/students/{student}/pdf', [ReportController::class, 'studentReportPdf'])->name('accounts.reports.students.pdf');
    Route::post('/reports/students/{student}/remarks', [ReportController::class, 'storeStudentRemark'])->name('accounts.reports.students.remarks.store');
    Route::delete('/reports/students/{student}/remarks/{remark}', [ReportController::class, 'destroyStudentRemark'])->name('accounts.reports.students.remarks.destroy');
    Route::get('/reports/attendance',  [ReportController::class, 'attendanceReport'])->name('accounts.reports.attendance');
    Route::get('/reports/attendance/print', [ReportController::class, 'attendanceReportPrint'])->name('accounts.reports.attendance.print');
    Route::get('/reports/attendance/pdf', [ReportController::class, 'attendanceReportPdf'])->name('accounts.reports.attendance.pdf');
    Route::get('/reports/attendance/student/{student}', [ReportController::class, 'studentAttendanceReport'])->name('accounts.reports.attendance.student');
    Route::get('/reports/attendance/student/{student}/pdf', [ReportController::class, 'studentAttendancePdf'])->name('accounts.reports.attendance.student.pdf');

    Route::prefix('reporting')->name('accounts.reporting.')->group(function () {
        Route::get('/scholarship',        [\App\Http\Controllers\Admin\ReportingController::class, 'scholarship'])->name('scholarship');
        Route::get('/scholarship/print',  [\App\Http\Controllers\Admin\ReportingController::class, 'scholarshipPrint'])->name('scholarship.print');
        Route::get('/fee-pending',        [\App\Http\Controllers\Admin\ReportingController::class, 'feePending'])->name('fee-pending');
        Route::get('/fee-pending/print',  [\App\Http\Controllers\Admin\ReportingController::class, 'feePendingPrint'])->name('fee-pending.print');
        Route::get('/fee-paid',           [\App\Http\Controllers\Admin\ReportingController::class, 'feePaid'])->name('fee-paid');
        Route::get('/fee-paid/print',     [\App\Http\Controllers\Admin\ReportingController::class, 'feePaidPrint'])->name('fee-paid.print');
        Route::post('/fee-remarks/{type}/{feeAssignment}', [\App\Http\Controllers\Admin\ReportingController::class, 'saveFeeReportRemark'])->name('fee-remarks.save');
        Route::get('/students/search',    [\App\Http\Controllers\Admin\ReportingController::class, 'studentSearch'])->name('student-search');
        Route::get('/students/{student}/profile-print', [\App\Http\Controllers\Admin\ReportingController::class, 'studentProfilePrint'])->name('student-profile.print');
        Route::get('/attendance-summary',       [\App\Http\Controllers\Admin\ReportingController::class, 'attendanceSummary'])->name('attendance-summary');
        Route::get('/attendance-summary/print', [\App\Http\Controllers\Admin\ReportingController::class, 'attendanceSummaryPrint'])->name('attendance-summary.print');
    });

    // ── Printable reports — same letterhead / style as Admin Reporting Center ──
    Route::get('/reports/collection/print',     [ReportController::class, 'collectionPrint'])->name('reports.collection.print');
    Route::get('/reports/defaulters/print',     [ReportController::class, 'defaultersPrint'])->name('reports.defaulters.print');
    Route::get('/reports/class-revenue/print',  [ReportController::class, 'classRevenuePrint'])->name('reports.class-revenue.print');
    Route::get('/reports/installments/print',   [ReportController::class, 'installmentsPrint'])->name('reports.installments.print');
    Route::get('/reports/discounts/print',      [ReportController::class, 'discountsPrint'])->name('reports.discounts.print');
    Route::get('/reports/income-expense/print', [ReportController::class, 'incomeExpensePrint'])->name('reports.income-expense.print');

    // ── Audit Log ──
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    // ── Read-only Student / Teacher directory (financial scope only) ──
    // Accounts can see profiles and fee/salary info but can never create,
    // edit, or delete a student or teacher record — that stays with Admin.
    Route::get('/students/exit-report', [\App\Http\Controllers\StudentExitReportController::class, 'index'])->name('accounts.students.exit-report');
    Route::get('/students/bulk-installments/options', [VoucherController::class, 'studentInstallmentOptions'])->name('accounts.students.bulk-installments.options');
    Route::get('/students/bulk-installments/print', [VoucherController::class, 'printStudentInstallments'])->name('accounts.students.bulk-installments.print');
    Route::get('/students',             [\App\Http\Controllers\AccountsDirectoryController::class, 'students'])->name('accounts.students.index');
    Route::get('/students/print',       [\App\Http\Controllers\AccountsDirectoryController::class, 'studentsPrint'])->name('accounts.students.print');
    Route::get('/students/export',      [\App\Http\Controllers\AccountsDirectoryController::class, 'studentsExport'])->name('accounts.students.export');
    Route::get('/students-lookup',      [\App\Http\Controllers\AccountsDirectoryController::class, 'studentsLookup'])->name('accounts.students.lookup');
    Route::get('/students/{student}',   [\App\Http\Controllers\AccountsDirectoryController::class, 'studentShow'])->name('accounts.students.show');

    // ── Ledger Number — the one student-record field Accounts owns outright.
    //    A manual cross-reference into the physical/legacy ledger register,
    //    not part of the EMS admission record, so it's exempt from the
    //    "read-only" rule above. Update sets/changes it; destroy clears it. ──
    Route::put('/students/{student}/ledger-number',    [\App\Http\Controllers\AccountsDirectoryController::class, 'updateLedgerNumber'])->name('accounts.students.ledger-number.update');
    Route::delete('/students/{student}/ledger-number', [\App\Http\Controllers\AccountsDirectoryController::class, 'destroyLedgerNumber'])->name('accounts.students.ledger-number.destroy');

    Route::get('/teachers',             [\App\Http\Controllers\AccountsDirectoryController::class, 'teachers'])->name('accounts.teachers.index');
    Route::get('/teachers/{teacher}',   [\App\Http\Controllers\AccountsDirectoryController::class, 'teacherShow'])->name('accounts.teachers.show');
});

// ── Settings — EMS admin manages institution config from their own EMS
// session; an accountant can also view/update shared settings from their
// own separate Accounts session. Neither needs the other's login for this. ──
Route::middleware(['auth:web,accounts', 'role:admin,accountant'])->group(function () {
    Route::get('/settings',        [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings',       [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/reset', [SettingController::class, 'reset'])->name('settings.reset');
});

// ═══════════════════════════════════════════════════════════════════════════
// ACADRIX ↔ ORIBYTE CONNECTION ROUTES (license activation + webhook receiver)
// ═══════════════════════════════════════════════════════════════════════════
Route::post('/oribyte/activate', [LicenseActivationController::class, 'activate'])
    ->name('oribyte.activate')
    ->withoutMiddleware([\App\Http\Middleware\CheckOriByteLicense::class]);

Route::get('/oribyte/activate', function () {
    return view('license-expired', [
        'is_trial'       => false,
        'license_status' => 'inactive',
        'plan_name'      => 'Acadrix — EMS + Accounts',
        'expired_at'     => null,
        'days_overdue'   => 0,
        'plans'          => [],
        'message'        => 'Enter your license key to activate.',
    ]);
})->name('oribyte.activate.form')
  ->withoutMiddleware([\App\Http\Middleware\CheckOriByteLicense::class]);

Route::post('/webhooks/acadrix', function (\Illuminate\Http\Request $request) {
    $connector = app(\App\Services\OriByte\LicenseConnector::class);

    if (! $connector->verifyWebhook($request)) {
        \Illuminate\Support\Facades\Log::warning('[Acadrix] Webhook signature verification failed from IP: ' . $request->ip());
        abort(403, 'Invalid webhook signature.');
    }

    $event = $request->input('event');
    $data  = $request->input('data', []);

    \Illuminate\Support\Facades\Log::info("[Acadrix] Received webhook event: {$event}");

    match ($event) {
        'license.expired',
        'license.suspended'  => \Illuminate\Support\Facades\Cache::forget('acadrix_license_valid'),

        'license.renewed',
        'license.activated'  => (function () {
            \Illuminate\Support\Facades\Cache::forget('acadrix_license_valid');
            $block = storage_path('app/acadrix_block.lock');
            if (file_exists($block)) @unlink($block);
        })(),

        'announcement.created',
        'announcement.updated' => \Illuminate\Support\Facades\Cache::put(
            'acadrix_announcement', $data, now()->addHours(6)
        ),

        'announcement.deleted' => \Illuminate\Support\Facades\Cache::forget('acadrix_announcement'),

        'feature_flag.updated' => \Illuminate\Support\Facades\Cache::forget('acadrix_license_valid'),

        'client.suspended' => (function () use ($data) {
            \Illuminate\Support\Facades\Cache::put('acadrix_suspended', true, now()->addDays(30));
            $block = storage_path('app/acadrix_block.lock');
            file_put_contents($block, json_encode([
                'reason'     => $data['reason'] ?? 'Suspended by administrator.',
                'blocked_at' => now()->toIso8601String(),
            ]));
        })(),

        default => \Illuminate\Support\Facades\Log::debug("[Acadrix] Unhandled webhook event: {$event}"),
    };

    return response()->json(['received' => true, 'event' => $event]);

})->withoutMiddleware([\App\Http\Middleware\CheckOriByteLicense::class])
  ->name('webhooks.acadrix');