<?php

namespace App\Models\Concerns;

use App\Models\Student;
use App\Services\LearningTaskVisibility;

trait HasStudentAudience
{
    public function scopeVisibleToStudent($query, Student $student)
    {
        return $query->whereNotNull('published_at')->whereIn('status', ['active', 'closed'])
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->whereHas('course.enrollments', fn ($q) => $q->where('student_id', $student->id)->where('status', 'active'))
            ->where(function ($q) use ($student) {
                $q->whereNull('target_student_ids')->orWhereJsonLength('target_student_ids', 0)
                    ->orWhereJsonContains('target_student_ids', (int) $student->id);
            });
    }

    public function visibleFor(Student $student): bool
    {
        return LearningTaskVisibility::visible($this->status, $this->published_at !== null,
            $this->target_student_ids, (int) $student->id);
    }
}
