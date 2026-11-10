<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Examination;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * AiAssistController
 * ───────────────────
 * The single AI Assist endpoint used by the topbar/sidebar "AI Assist"
 * panel for EVERY role (admin, accountant, teacher, student, parent).
 * Previously this briefing only existed for admins
 * (Admin\AdminDashboardController::aiBriefing); this controller keeps that
 * behaviour for admins and adds an equivalent, role-appropriate briefing
 * for every other role so the same button works everywhere in the app.
 *
 * Uses the existing offline, rule-based BuiltInAiTrait — no external API,
 * no extra cost, same "Rule-based, runs instantly" panel copy stays true.
 */
class AiAssistController extends Controller
{
    use \App\Traits\BuiltInAiTrait;

    public function briefing()
    {
        $user = auth()->user();

        $prompt = match (true) {
            $user?->isAdmin()      => $this->adminPrompt(),
            $user?->isAccountant() => $this->accountantPrompt(),
            $user?->isTeacher()    => $this->teacherPrompt($user),
            $user?->isStudent()    => $this->studentPrompt($user),
            $user?->isParent()     => $this->parentPrompt($user),
            default                 => null,
        };

        if (! $prompt) {
            return response()->json(['briefing' => 'Not enough data yet to generate an insight — check back once there\'s more activity recorded.']);
        }

        return response()->json(['briefing' => $this->builtInAI($prompt)]);
    }

    protected function adminPrompt(): string
    {
        $students   = Student::where('status', 'active')->count();
        $pending    = Student::where('status', 'pending')->count();
        $unpaidFees = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->count();
        $todayAgg   = Attendance::selectRaw('COUNT(*) as total, SUM(status="present") as present')
                          ->whereDate('date', today())->first();
        $attendPct  = $todayAgg->total > 0
                          ? round($todayAgg->present / $todayAgg->total * 100) : 0;

        return "You are a school admin assistant. Write a friendly, concise morning briefing "
            . "(3 sentences max) for the principal based on today's data:\n"
            . "- Active students: {$students}, Pending admissions: {$pending}\n"
            . "- Outstanding fee vouchers: {$unpaidFees}\n"
            . "- Today's attendance rate: {$attendPct}%\n"
            . 'Date: ' . now()->format('l, F j Y');
    }

    protected function accountantPrompt(): string
    {
        $overdue      = Voucher::where('status', 'overdue')->count();
        $unpaid       = Voucher::whereIn('status', ['unpaid', 'partial'])->count();
        $collectedToday = (float) DB::table('payments')->whereDate('paid_at', today())->sum('amount');
        $outstanding  = (float) Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])
                            ->get()->sum(fn ($v) => $v->balance());

        return "You are an accounts assistant. Write a friendly, concise briefing "
            . "(3 sentences max) for the accountant based on today's data:\n"
            . "- Overdue vouchers: {$overdue}, Unpaid/partial vouchers: {$unpaid}\n"
            . "- Collected today: Rs. {$collectedToday}\n"
            . "- Total outstanding balance: Rs. {$outstanding}\n"
            . 'Date: ' . now()->format('l, F j Y');
    }

    protected function teacherPrompt($user): string
    {
        $teacher = $user->teacher ?? Teacher::where('user_id', $user->id)->first();

        $classesToday = $teacher ? $teacher->classes()->count() : 0;

        $pendingResults = 0;
        if ($teacher && $teacher->subject_id) {
            $classIds = $teacher->classes()->pluck('id');
            $pendingResults = Examination::where('subject_id', $teacher->subject_id)
                ->whereIn('school_class_id', $classIds)
                ->whereDoesntHave('results')
                ->count();
        }

        return "You are a teaching assistant. Write a friendly, concise briefing "
            . "(3 sentences max) for the teacher based on today's data:\n"
            . "- Classes assigned: {$classesToday}\n"
            . "- Exams pending result entry: {$pendingResults}\n"
            . 'Date: ' . now()->format('l, F j Y');
    }

    protected function studentPrompt($user): string
    {
        $student = $user->student;
        if (! $student) {
            return '';
        }

        $attendPct = (float) ($student->attendance_percentage ?? 0);
        $dueVouchers = Voucher::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->count();
        $overdue = Voucher::where('student_id', $student->id)->where('status', 'overdue')->count();

        return "You are a student assistant. Write a friendly, concise briefing "
            . "(3 sentences max) for the student based on their data:\n"
            . "- Attendance: {$attendPct}%\n"
            . "- Outstanding fee vouchers: {$dueVouchers}\n"
            . "- Overdue vouchers: {$overdue}\n"
            . 'Date: ' . now()->format('l, F j Y');
    }

    protected function parentPrompt($user): string
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return '';
        }

        $children = $parent->students()->get();
        $avgAttendance = $children->count()
            ? round($children->avg('attendance_percentage'), 1)
            : 0;
        $dueVouchers = Voucher::whereIn('student_id', $children->pluck('id'))
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->count();

        return "You are a parent-portal assistant. Write a friendly, concise briefing "
            . "(3 sentences max) for the parent based on their children's data:\n"
            . "- Children: {$children->count()}\n"
            . "- Average attendance: {$avgAttendance}%\n"
            . "- Outstanding fee vouchers: {$dueVouchers}\n"
            . 'Date: ' . now()->format('l, F j Y');
    }
}
