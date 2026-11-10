<?php

declare(strict_types=1);
namespace App\Services;

final class LearningTaskVisibility
{
    public static function visible(string $status, bool $published, ?array $targets, int $studentId): bool
    {
        return $published && in_array($status, ['active', 'closed'], true)
            && (empty($targets) || in_array($studentId, array_map('intval', $targets), true));
    }
}
