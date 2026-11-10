<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Main portal login — guard 'web', Parent & Student accounts only.
 *
 * This is now the app's single public-facing entry point (the old landing
 * page picker is gone). Admin, Teacher and Accountant accounts are refused
 * here even with a correct password — they sign in through the separate,
 * unlinked Staff Login behind its own PIN gate (see StaffGateController /
 * StaffAuthController), so a student/parent session can never be used to
 * reach staff-only areas and staff credentials typed here never work.
 */
class AuthController extends Controller
{
    private const ALLOWED_ROLES = [User::ROLE_STUDENT, User::ROLE_PARENT];

    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'portal-login:' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, config('staffgate.max_attempts'))) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many failed attempts. Please try again in {$seconds} seconds.",
            ])->withInput($request->only('email'));
        }

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));
            return back()->withErrors(['email' => 'These credentials do not match our records.'])
                         ->withInput($request->only('email'));
        }

        $user = Auth::guard('web')->user();

        if (! in_array($user->role, self::ALLOWED_ROLES, true)) {
            Auth::guard('web')->logout();
            RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }

        if ($user->isStudent()) {
            $student = $user->student()->first();
            if (! $student || ! $student->isActiveEnrollment()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));
                return redirect()->route('login')->withErrors([
                    'email' => \App\Support\StudentStatus::lockedMessage($student?->status),
                ])->withInput($request->only('email'));
            }
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('web')->user();
        $isStaff = $user && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_TEACHER], true);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($isStaff ? 'staff.gate' : 'login');
    }
}
