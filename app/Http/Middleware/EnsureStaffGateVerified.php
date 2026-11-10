<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards GET/POST /staff/login.
 *
 * Nobody reaches the staff login form just by knowing the URL — they must
 * first submit the correct PIN on /staff/gate, which stamps the session
 * with the role that PIN unlocks and a short expiry. This middleware
 * checks that stamp on every request to the staff login form itself and
 * bounces anyone without a fresh, valid one straight back to the PIN gate.
 */
class EnsureStaffGateVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $role      = $request->session()->get('staff_gate_role');
        $expiresAt = $request->session()->get('staff_gate_expires_at');

        if (! $role || ! $expiresAt || now()->timestamp > $expiresAt) {
            $request->session()->forget(['staff_gate_role', 'staff_gate_expires_at']);

            return redirect()->route('staff.gate')
                ->withErrors(['pin' => 'Your session expired. Please enter the PIN again.']);
        }

        return $next($request);
    }
}
