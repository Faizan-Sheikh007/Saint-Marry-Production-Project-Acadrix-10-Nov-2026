<?php

namespace App\Support;

/** One enrollment status; never infer it from paid/unpaid fee status. */
final class StudentStatus
{
    public const VALUES = ['active', 'inactive', 'suspended', 'graduated', 'dropped', 'transferred', 'terminated', 'left'];

    public static function normalize(?string $status): string
    {
        return strtolower(trim((string) $status));
    }

    public static function isActive(?string $status): bool
    {
        return self::normalize($status) === 'active';
    }

    public static function label(?string $status): string
    {
        $value = self::normalize($status);
        return in_array($value, self::VALUES, true) ? ucfirst($value) : 'Unknown';
    }

    public static function badge(?string $status): string
    {
        return match (self::normalize($status)) {
            'active' => 'success', 'suspended' => 'warning', 'graduated' => 'info',
            'left', 'dropped', 'terminated' => 'danger', 'transferred' => 'purple',
            default => 'gray',
        };
    }

    public static function lockedMessage(?string $status): string
    {
        return 'Student status: '.self::label($status).'. This profile is disabled. An administrator must reactivate it before changes or student portal access are allowed.';
    }
}
