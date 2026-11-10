<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Staff login — only reachable after StaffGateController has stamped the
 * session with which role's PIN was entered. This controller trusts that
 * stamp for which login form to render, but re-validates it again at
 * submit time and, critically, re-checks that the account which just
 * authenticated actually holds that exact role — so someone who guessed
 * the Teacher PIN can never ride a Teacher-labelled form into an Admin or
 * Accountant session even if they somehow knew those credentials too.
 *
 * Accountant accounts authenticate on the separate 'accounts' guard, kept
 * fully apart from the 'web' guard used by admin/teacher/student/parent —
 * matching the rest of the app's guard split (see routes/web.php).
 */
class StaffAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $role = $request->session()->get('staff_gate_role');

        return view('auth.staff-login', ['staffRole' => $role]);
    }

    public function login(Request $request)
    {
        $role = $request->session()->get('staff_gate_role');
        $expiresAt = $request->session()->get('staff_gate_expires_at');

        if (! $role || ! $expiresAt || now()->timestamp > $expiresAt) {
            $request->session()->forget(['staff_gate_role', 'staff_gate_expires_at']);
            return redirect()->route('staff.gate')
                ->withErrors(['pin' => 'Your session expired. Please enter the PIN again.']);
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'staff-login:' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, config('staffgate.max_attempts'))) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many failed attempts. Please try again in {$seconds} seconds.",
            ])->withInput($request->only('email'));
        }

        $guard = $role === User::ROLE_ACCOUNTANT ? 'accounts' : 'web';

        if (! Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));
            return back()->withErrors(['email' => 'These credentials do not match our records.'])
                         ->withInput($request->only('email'));
        }

        $user = Auth::guard($guard)->user();

        // The account must hold exactly the role that this PIN unlocked —
        // an admin's valid password typed into a teacher-gated form (or
        // vice versa) is rejected, not silently upgraded or downgraded.
        if ($user->role !== $role) {
            Auth::guard($guard)->logout();
            RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));
            return back()->withErrors([
                'email' => 'This account is not authorized for this login.',
            ])->withInput($request->only('email'));
        }

        RateLimiter::clear($throttleKey);

        $request->session()->forget(['staff_gate_role', 'staff_gate_expires_at']);
        $request->session()->regenerate();

        // Staff roles must land on their own dashboard after login.
        // An old `url.intended` value may point to a deleted/admin URL and
        // cause a 404 even when the teacher dashboard route exists.
        $request->session()->forget('url.intended');
        return redirect()->route($user->dashboardRoute());
    }
}
