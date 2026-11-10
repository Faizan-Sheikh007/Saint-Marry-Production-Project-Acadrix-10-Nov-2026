<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AiInsightsController
 * ─────────────────────
 * Powers the real-time "Acadrix AI" insights panel (the crimson card with a
 * School Health Score, 7-day attendance strip, Action Required / Positive
 * Indicators lists, plus Fees / Attendance / Search tabs).
 *
 * Same philosophy as BuiltInAiTrait: fully offline, deterministic, rule-based
 * scoring over real numbers pulled from the DB — zero external API cost.
 */
class AiInsightsController extends Controller
{
    /** Main payload for the Briefing tab + header (score, alerts, chart). */
    public function data(Request $request)
    {
        $user = auth()->user();

        if ($user?->isTeacher()) {
            return response()->json($this->teacherInsights($user));
        }

        return response()->json($this->schoolInsights());
    }

    /** Fees tab: collection snapshot for today + outstanding breakdown. */
    public function fees()
    {
        $collectedToday = (float) DB::table('payments')->whereDate('paid_at', today())->sum('amount');
        $collectedMonth = (float) DB::table('payments')
            ->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount');

        $unpaid   = Voucher::whereIn('status', ['unpaid', 'partial'])->count();
        $overdue  = Voucher::where('status', 'overdue')->count();
        $outstanding = (float) Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()->sum(fn ($v) => $v->balance());

        return response()->json([
            'collected_today' => round($collectedToday),
            'collected_month' => round($collectedMonth),
            'unpaid_vouchers' => $unpaid,
            'overdue_vouchers'=> $overdue,
            'outstanding'     => round($outstanding),
        ]);
    }

    /** Attendance tab: 7-day trend + today's split by present/absent. */
    public function attendance()
    {
        return response()->json(['days' => $this->sevenDayAttendance()]);
    }

    /** Search tab: quick lookup across students & teachers by name/ID. */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['students' => [], 'teachers' => []]);
        }

        $students = Student::where('name', 'like', "%{$q}%")
            ->orWhere('roll_number', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'name', 'status']);

        $teachers = Teacher::where('name', 'like', "%{$q}%")
            ->orWhere('employee_id', 'like', "%{$q}%")
            ->limit(5)->get(['id', 'name', 'employee_id', 'status']);

        return response()->json([
            'students' => $students->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'meta' => $s->status,
                'url' => route('admin.students.show', $s->id),
            ]),
            'teachers' => $teachers->map(fn ($t) => [
                'id' => $t->id, 'name' => $t->name, 'meta' => $t->employee_id,
                'url' => route('admin.teachers.show', $t->id),
            ]),
        ]);
    }

    // ── School-wide (admin) insight payload ─────────────────────────────
    protected function schoolInsights(): array
    {
        $students   = Student::where('status', 'active')->count();
        $days       = $this->sevenDayAttendance();
        $todayPct   = collect($days)->last()['pct'] ?? 0;

        $unpaidVouchers = Voucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
        $unpaidCount    = $unpaidVouchers->count();
        $collectedTotal = (float) DB::table('payments')
            ->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount');

        $teacherCount = Teacher::where('status', 'active')->count();
        $studentTeacherRatio = $teacherCount > 0 ? round($students / $teacherCount, 1) : 0;

        $liveCourses = Course::where('status', 'published')->count();

        $unpaidRatio = $students > 0 ? min(1, $unpaidCount / max($students, 1)) : 0;

        // ── Rule-based School Health Score (0-100) ──
        $attendanceScore = round(min(100, $todayPct) * 0.4);          // up to 40
        $feeScore        = round((1 - $unpaidRatio) * 30);            // up to 30
        $opsScore        = 30;
        $alerts = [];
        $positives = [];

        if ($todayPct < 50 && $students > 0) {
            $opsScore -= 10;
            $alerts[] = ['icon' => '⚠️', 'text' => "Attendance critically low at {$todayPct}% — contact class teachers immediately."];
        } elseif ($todayPct < 75 && $students > 0) {
            $opsScore -= 5;
            $alerts[] = ['icon' => '⚠️', 'text' => "Attendance is below target today ({$todayPct}%)."];
        } else {
            $positives[] = ['icon' => '✅', 'text' => "Attendance is healthy today at {$todayPct}%."];
        }

        if ($unpaidRatio > 0.4) {
            $opsScore -= 10;
            $alerts[] = ['icon' => '💰', 'text' => "{$unpaidCount} unpaid fees (" . round($unpaidRatio * 100) . "% of students). High default risk."];
        } elseif ($unpaidCount > 0) {
            $alerts[] = ['icon' => '💰', 'text' => "{$unpaidCount} unpaid fee voucher(s) need follow-up."];
        } else {
            $positives[] = ['icon' => '✅', 'text' => 'All fee vouchers are up to date.'];
        }

        if ($studentTeacherRatio > 0 && $studentTeacherRatio <= 25) {
            $positives[] = ['icon' => '👩‍🏫', 'text' => "Student:teacher ratio {$studentTeacherRatio}:1 — within healthy range."];
        } elseif ($studentTeacherRatio > 25) {
            $opsScore -= 5;
            $alerts[] = ['icon' => '👩‍🏫', 'text' => "Student:teacher ratio is {$studentTeacherRatio}:1 — consider hiring."];
        }

        if ($liveCourses > 0) {
            $positives[] = ['icon' => '📚', 'text' => "{$liveCourses} LMS course(s) live and running."];
        }

        $opsScore = max(0, $opsScore);
        $score = max(0, min(100, $attendanceScore + $feeScore + $opsScore));

        return [
            'scope'        => 'school',
            'score'        => $score,
            'score_label'  => $this->scoreLabel($score),
            'greeting'     => $this->greeting(),
            'date_label'   => now()->format('l, F j'),
            'stats' => [
                ['label' => 'Students',  'value' => (string) $students],
                ['label' => 'Today',     'value' => $todayPct . '%'],
                ['label' => 'Collected', 'value' => 'Rs.' . $this->shortNumber($collectedTotal)],
            ],
            'attendance_days' => $days,
            'alerts'    => array_slice($alerts, 0, 4),
            'positives' => array_slice($positives, 0, 4),
        ];
    }

    // ── Teacher-scoped insight payload ──────────────────────────────────
    protected function teacherInsights($user): array
    {
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            return [
                'scope' => 'teacher', 'score' => 0, 'score_label' => 'No data',
                'greeting' => $this->greeting(), 'date_label' => now()->format('l, F j'),
                'stats' => [], 'attendance_days' => [], 'alerts' => [], 'positives' => [],
            ];
        }

        $classCount   = $teacher->classes()->count();
        $courseCount  = $teacher->courses()->count();
        $attendPct    = $teacher->attendance_percent ?? 0;

        $alerts = [];
        $positives = [];

        if ($attendPct < 75) {
            $alerts[] = ['icon' => '⚠️', 'text' => "Your attendance this month is {$attendPct}% — below the 75% mark."];
        } else {
            $positives[] = ['icon' => '✅', 'text' => "Your attendance this month is {$attendPct}%."];
        }

        if ($classCount === 0) {
            $alerts[] = ['icon' => '📋', 'text' => 'No classes are assigned to you yet — check with the admin.'];
        } else {
            $positives[] = ['icon' => '📋', 'text' => "You're teaching {$classCount} class(es) this term."];
        }

        if ($courseCount > 0) {
            $positives[] = ['icon' => '📚', 'text' => "{$courseCount} LMS course(s) under your name."];
        }

        $score = max(0, min(100, round($attendPct * 0.6) + ($classCount > 0 ? 25 : 0) + ($courseCount > 0 ? 15 : 0)));

        return [
            'scope'       => 'teacher',
            'score'       => $score,
            'score_label' => $this->scoreLabel($score),
            'greeting'    => $this->greeting(),
            'date_label'  => now()->format('l, F j'),
            'stats' => [
                ['label' => 'Classes',    'value' => (string) $classCount],
                ['label' => 'Attend.',    'value' => $attendPct . '%'],
                ['label' => 'Courses',    'value' => (string) $courseCount],
            ],
            'attendance_days' => [],
            'alerts'    => array_slice($alerts, 0, 4),
            'positives' => array_slice($positives, 0, 4),
        ];
    }

    // ── Shared helpers ───────────────────────────────────────────────────
    protected function sevenDayAttendance(): array
    {
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $agg  = Attendance::selectRaw('COUNT(*) as total, SUM(status="present") as present')
                        ->whereDate('date', $date)->first();
            $pct  = ($agg && $agg->total > 0) ? (int) round($agg->present / $agg->total * 100) : 0;
            $out[] = ['label' => $date->format('D'), 'pct' => $pct, 'is_today' => $date->isToday()];
        }
        return $out;
    }

    protected function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Fair',
            default      => 'Critical',
        };
    }

    protected function greeting(): string
    {
        $h = now()->hour;
        return $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
    }

    protected function shortNumber(float $n): string
    {
        if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
        if ($n >= 1000)    return round($n / 1000, 1) . 'K';
        return (string) round($n);
    }
}
