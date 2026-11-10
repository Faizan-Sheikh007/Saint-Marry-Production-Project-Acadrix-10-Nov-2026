<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Thin wrapper around the existing `app_notifications` table used to power
 * the bell icon + notification sound in the topbar. Kept framework-light on
 * purpose so it can be called from any controller without extra ceremony.
 */
class NotifierService
{
    public static function notify(int $userId, string $message, string $icon = 'fa-bell', string $color = 'primary'): void
    {
        Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'icon'    => $icon,
            'color'   => $color,
            'read'    => false,
        ]);
    }

    /** Notify every admin user (there's usually a small handful). */
    public static function notifyAdmins(string $message, string $icon = 'fa-bell', string $color = 'primary'): void
    {
        User::where('role', User::ROLE_ADMIN)->pluck('id')->each(
            fn ($id) => self::notify($id, $message, $icon, $color)
        );
    }
}
