<?php

/**
 * ============================================================================
 * Acadrix — Staff Access Gate Configuration
 * ============================================================================
 * The special Staff Login (Admin / Teacher / Accounts) is never linked from
 * anywhere public-facing on the main portal. It sits behind a one-time PIN
 * wall: the PIN typed in decides which staff role's login form unlocks
 * next. Each role has its own PIN, so knowing one role's PIN reveals
 * nothing about the others and nothing about which roles exist.
 *
 * IMPORTANT: change these in your .env for every real deployment. They are
 * compared with hash_equals() (constant-time) so timing differences can't
 * be used to guess them digit-by-digit, and every attempt is rate-limited
 * and logged.
 * ============================================================================
 */

return [

    'pins' => [
        'admin'      => env('STAFF_PIN_ADMIN', '482913'),
        'teacher'    => env('STAFF_PIN_TEACHER', '157246'),
        'accountant' => env('STAFF_PIN_ACCOUNTS', '930518'),
    ],

    // How long a verified PIN keeps the staff login form unlocked before it
    // locks again and asks for the PIN once more (minutes).
    'ttl_minutes' => (int) env('STAFF_GATE_TTL', 10),

    // Failed-attempt lockouts, shared by the PIN gate and both login forms.
    'max_attempts'    => (int) env('AUTH_MAX_ATTEMPTS', 5),
    'lockout_seconds' => (int) env('AUTH_LOCKOUT_SECONDS', 60),

];
