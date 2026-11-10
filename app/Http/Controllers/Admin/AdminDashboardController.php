<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Voucher;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Course;
use App\Models\Activity;
use App\Models\Notification;
use App\Services\OriByte\LicenseConnector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    use \App\Traits\BuiltInAiTrait;

    public function index()
    {
        // ── Core counts ────────────────────────────────────────────────────
        // Revenue/fee figures now come from the Accounts module (Vouchers/Payments)
        // instead of the old standalone Fee module, which has been retired in
        // favour of a single source of truth shared with the Accounts side.
        $totalStudents = Student::where('status', 'active')->count();
        $totalTeachers = Teacher::where('status', 'active')->count();
        $totalRevenue  = \App\Services\CashReceiptLedger::total(now()->startOfMonth(), now()->endOfMonth());
        $totalCourses  = Course::where('status', 'published')->count();

        // ── Today's attendance (2 queries → 1) ───────────────────────────────
        $todayAgg = Attendance::selectRaw(
                'COUNT(*) as total, SUM(status = "present") as present_count'
            )
            ->whereDate('date', today())
            ->first();

        $attendanceToday = $todayAgg->total > 0
            ? round($todayAgg->present_count / $todayAgg->total * 100)
            : 0;

        // ── Eager-loaded lists ────────────────────────────────────────────────
        $recentStudents = Student::with('schoolClass')->latest()->take(5)->get();
        $activities     = Activity::latest()->take(6)->get();
        $notifications  = Notification::latest()->take(5)->get();
        $classes        = SchoolClass::withCount('students')->get();

        // ── FIX (Medium – N+1 in attendance chart) ───────────────────────────
        $from = now()->subDays(6)->toDateString();
        $to   = now()->toDateString();

        $attendanceRows = Attendance::selectRaw(
                'DATE(date) as day,
                 COUNT(*) as total,
                 SUM(status = "present") as present_count'
            )
            ->whereBetween('date', [$from, $to])
            ->groupBy(DB::raw('DATE(date)'))
            ->get()
            ->keyBy('day');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i);
            $key   = $date->toDateString();
            $row   = $attendanceRows->get($key);
            $total = $row?->total ?? 0;
            $pres  = $row?->present_count ?? 0;

            $chartData[] = [
                'day' => $date->format('D'),
                'pct' => $total > 0 ? round($pres / $total * 100) : 0,
            ];
        }

        // ── Pending resource counts (now sourced from Accounts vouchers) ────
        $pendingStudents  = Student::where('status', 'pending')->count();
        $pendingFeeCount  = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->count();
        $pendingResources = $pendingStudents + $pendingFeeCount;

        // ── License status ────────────────────────────────────────────────────
        $licenseStatus    = 'unknown';
        $licenseExpiresAt = null;
        $licenseIsTrial   = false;
        $licenseDaysLeft  = null;
        $licensePlan      = null;

        try {
            $connector        = app(LicenseConnector::class);
            $validation       = $connector->validate();
            $licenseStatus    = $validation['license_status'] ?? ($validation['valid'] ? 'active' : 'unknown');
            $licenseExpiresAt = $validation['expires_at']       ?? null;
            $licenseIsTrial   = ($validation['type']            ?? '') === 'trial';
            $licenseDaysLeft  = $validation['days_until_expiry'] ?? null;
            $licensePlan      = $validation['plan']['name']      ?? null;
        } catch (\Exception $e) {
            Log::debug('[Acadrix] Dashboard license fetch skipped: ' . $e->getMessage());
        }

        // ── Teacher engagement widgets: unread messages, pending paragraph
        // submissions, and a quick recap of what's been assigned ──────────
        $unreadTeacherMessages = \App\Models\TeacherMessage::where('is_read', false)
            ->whereHas('sender', fn ($q) => $q->where('role', 'teacher'))
            ->with('teacher')->latest()->take(5)->get();

        $pendingSubmissionsCount =
            \App\Models\Assignment::where('is_admin_request', true)->where('request_status', 'submitted')->count()
            + \App\Models\Quiz::where('is_admin_request', true)->where('request_status', 'submitted')->count()
            + \App\Models\Practical::whereNotNull('paragraph')->where('status', 'submitted')->count();

        return view('admin.dashboard', compact(
            'totalStudents', 'totalTeachers', 'totalRevenue', 'attendanceToday',
            'totalCourses', 'recentStudents', 'activities', 'notifications', 'classes', 'chartData',
            'licenseStatus', 'licenseExpiresAt', 'licenseIsTrial', 'licenseDaysLeft', 'licensePlan',
            'pendingResources', 'pendingStudents', 'pendingFeeCount',
            'unreadTeacherMessages', 'pendingSubmissionsCount'
        ));
    }

    public function aiBriefing()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $students    = Student::where('status', 'active')->count();
        $pending     = Student::where('status', 'pending')->count();
        $unpaidFees  = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->count();
        $todayAgg    = Attendance::selectRaw('COUNT(*) as total, SUM(status="present") as present')
                           ->whereDate('date', today())->first();
        $attendPct   = $todayAgg->total > 0
                           ? round($todayAgg->present / $todayAgg->total * 100) : 0;

        $prompt = "You are a school admin assistant. Write a friendly, concise morning briefing "
            . "(3 sentences max) for the principal based on today's data:\n"
            . "- Active students: {$students}, Pending admissions: {$pending}\n"
            . "- Outstanding fee vouchers: {$unpaidFees}\n"
            . "- Today's attendance rate: {$attendPct}%\n"
            . "Date: " . now()->format('l, F j Y');

        $insight = $this->builtInAI($prompt);
        return response()->json(['briefing' => $insight]);
    }

}
