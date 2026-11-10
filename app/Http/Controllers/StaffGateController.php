<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The PIN wall in front of the Staff Login (Admin / Teacher / Accounts).
 *
 * There is exactly one PIN field. Which of the three configured PINs it
 * matches decides which role's login form unlocks next — the visitor never
 * picks a role themselves, so the page gives no hint of which staff roles
 * exist. A wrong PIN gets the same generic error every time, and repeated
 * wrong attempts lock the requester out for a cooldown period regardless
 * of which PIN they were trying.
 */
class StaffGateController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.staff-gate');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'string', 'max:32'],
        ]);

        $throttleKey = 'staff-gate:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, config('staffgate.max_attempts'))) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'pin' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $role = $this->matchPin($request->input('pin'));

        if (! $role) {
            RateLimiter::hit($throttleKey, config('staffgate.lockout_seconds'));

            return back()->withErrors(['pin' => 'Incorrect PIN.']);
        }

        RateLimiter::clear($throttleKey);

        // Session fixation hardening — a fresh session ID for the newly
        // privileged gate state, same as after a real login.
        $request->session()->regenerate();
        $request->session()->put('staff_gate_role', $role);
        $request->session()->put('staff_gate_expires_at', now()->addMinutes(config('staffgate.ttl_minutes'))->timestamp);

        return redirect()->route('staff.login');
    }

    /**
     * Constant-time comparison against every configured PIN so that
     * response timing can't leak which digits are correct, and so a
     * partial match against one role's PIN never accidentally exposes
     * that role. Returns the matched role name or null.
     */
    private function matchPin(string $pin): ?string
    {
        foreach ((array) config('staffgate.pins') as $role => $configured) {
            if (hash_equals((string) $configured, $pin)) {
                return $role;
            }
        }

        return null;
    }
}
