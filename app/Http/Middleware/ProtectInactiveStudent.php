<?php

namespace App\Http\Middleware;

use App\Models\{Payment, Student};
use App\Support\StudentStatus;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only archive lock for non-active students. UI disabling is supplementary:
 * posted URLs, bulk selections, voucher/payment routes and stale forms are gated here.
 * Bindings have run before this web-group middleware.
 */
class ProtectInactiveStudent
{
    private const READ_ONLY_POSTS = [
        'vouchers.export-selected', 'accounts.dashboard.student-explorer.print',
        'admin.dashboard.student-explorer.print',
    ];
    private const WRITE_FORMS = [
        'students.edit', 'students.id-card', 'students.message', 'fee-assignments.create',
        'fee-assignments.renew', 'vouchers.create', 'vouchers.edit', 'admin.library.issue.form',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Existing auth/role middleware still owns authorization; never grant it here.
        if (! Auth::guard('web')->check() && ! Auth::guard('accounts')->check()) {
            return $next($request);
        }
        $name = $request->route()?->getName();
        if ($name === 'students.update-status') {
            return $next($request); // admin-only route; validated, transactional transition
        }
        if (in_array($name, self::READ_ONLY_POSTS, true)) return $next($request);
        if ($request->isMethodSafe() && ! in_array($name, self::WRITE_FORMS, true)) return $next($request);

        $ids = $this->studentIds($request);
        if (! $ids) return $next($request);
        $check = function () use ($ids, $request, $next) {
            $query = Student::whereIn('id', $ids)->orderBy('id');
            if (! $request->isMethodSafe()) $query->lockForUpdate();
            foreach ($query->get() as $student) {
                if (! $student->isActiveEnrollment()) {
                    $message = StudentStatus::lockedMessage($student->status);
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message,
                            'student_status' => $student->status, 'profile_locked' => true], 423);
                    }
                    return response()->view('errors.student-locked', compact('student', 'message'), 423);
                }
            }
            return $next($request);
        };
        // Serialize targeted mutations with status transitions; do not retry HTTP
        // controllers (they may send notifications or perform filesystem work).
        return $request->isMethodSafe() ? $check() : DB::transaction($check);
    }

    private function studentIds(Request $request): array
    {
        $ids = [];
        $add = static function ($id) use (&$ids): void {
            if ((is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0) {
                $ids[(int) $id] = (int) $id;
            }
        };
        foreach (($request->route()?->parameters() ?? []) as $key => $value) {
            if ($value instanceof Student) $add($value->getKey());
            elseif ($value instanceof Payment) $add($value->voucher?->student_id);
            elseif ($value instanceof Model) $add($value->getAttribute('student_id'));
            elseif ($key === 'student') $add($value);
        }
        $add($request->input('student_id'));
        foreach (['student_ids', 'target_student_ids'] as $key) {
            foreach ((array) $request->input($key, []) as $id) $add($id);
        }
        if ($request->routeIs('students.bulk-destroy')) {
            foreach ((array) $request->input('ids', []) as $id) $add($id);
        }
        foreach (['marks', 'attendance'] as $key) {
            foreach (array_keys((array) $request->input($key, [])) as $id) $add($id);
        }
        return array_values($ids);
    }
}
