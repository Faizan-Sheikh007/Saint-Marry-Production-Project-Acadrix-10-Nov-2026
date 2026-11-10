<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Accounts logout — guard 'accounts'.
 *
 * Sign-in for accountant accounts now happens exclusively through the
 * Staff Login (StaffAuthController), reachable only after the PIN gate.
 * This controller only tears the session down again; it exists as its own
 * class purely so the accountant dashboard's logout button/form (already
 * wired to the 'accounts.logout' route name across the views) keeps
 * working unchanged.
 */
class AccountsAuthController extends Controller
{
    public function logout(Request $request)
    {
        Auth::guard('accounts')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('staff.gate');
    }
}
