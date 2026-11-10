<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * "Double check passwords" guard for high-impact salary actions
 * (sending a payable-salary request to Accounts, and Accounts actually
 * issuing/disbursing it). The acting user must re-type their own
 * current password; it's verified against their stored hash with
 * Hash::check(), never compared or stored in plaintext.
 */
trait ConfirmsPassword
{
    /**
     * Aborts the request with a 422 + validation error (redirect back
     * with input, like any other validation failure) if the password
     * field doesn't match the currently authenticated user's password.
     */
    protected function assertPasswordConfirmed(Request $request): void
    {
        $request->validate([
            'password_confirmation_check' => ['required', 'string'],
        ], [
            'password_confirmation_check.required' => 'Please re-enter your password to confirm this action.',
        ]);

        $user = auth()->user();

        if (!$user || !Hash::check($request->input('password_confirmation_check'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password_confirmation_check' => 'That password is incorrect. Please try again.',
            ]);
        }
    }
}
