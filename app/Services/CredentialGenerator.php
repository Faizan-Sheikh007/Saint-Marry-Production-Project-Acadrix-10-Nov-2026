<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Issues concise institution login email addresses for new students and parents.
 * Existing issued credentials are not modified.
 * Student example: stu-1569@marians.edu.pk
 * Parent example: prnt-2121@marians.edu.pk
 */
class CredentialGenerator
{
    protected static function slug(): string
    {
        $institution = Setting::get('institution_name', 'Acadrix');
        $slug        = Str::slug($institution, '');
        return $slug === '' ? 'acadrix' : strtolower($slug);
    }

    protected static function domain(): string
    {
        return 'marians.edu.pk';
    }

    /** Unique student login, e.g. stu-1569@marians.edu.pk. */
    public static function studentEmail(): string
    {
        $domain = static::domain();

        do {
            $candidate = 'stu-' . random_int(1000, 9999) . '@' . $domain;
        } while (User::where('email', $candidate)->exists());

        return $candidate;
    }

    /** Unique parent login, e.g. prnt-2121@marians.edu.pk. */
    public static function parentEmail(): string
    {
        $domain = static::domain();

        do {
            $candidate = 'prnt-' . random_int(1000, 9999) . '@' . $domain;
        } while (User::where('email', $candidate)->exists());

        return $candidate;
    }

    /** e.g. pass@331@1189 (student). */
    public static function studentPassword(): string
    {
        return 'pass@' . random_int(100, 999) . '@' . random_int(1000, 9999);
    }

    /** e.g. prnt@122@2109 (parent). */
    public static function parentPassword(): string
    {
        return 'prnt@' . random_int(100, 999) . '@' . random_int(1000, 9999);
    }
}
