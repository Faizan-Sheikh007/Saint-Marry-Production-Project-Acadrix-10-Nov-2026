<?php

namespace App\Http\Middleware;

use App\Support\StudentStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Re-check the database on EVERY request, including existing/remembered sessions. */
class EnsureStudentIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('accounts*') && Auth::guard('accounts')->check()) return $next($request);
        $guard = Auth::guard('web');
        $user = $guard->user();
        if ($user && $user->isStudent()) {
            // Query the relationship, not a possibly cached relation on the User.
            $student = $user->student()->first();
            if (! $student || ! $student->isActiveEnrollment()) {
                $message = StudentStatus::lockedMessage($student?->status);
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                if ($request->expectsJson()) {
                    return response()->json(['message' => $message,
                        'student_status' => $student?->status ?? 'unknown', 'profile_locked' => true], 403);
                }
                return redirect()->route('login')->withErrors(['email' => $message]);
            }
        }
        return $next($request);
    }
}
