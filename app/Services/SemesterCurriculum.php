<?php

namespace App\Services;

use App\Models\{ClassSubject, Course, SchoolClass, Timetable};
use Illuminate\Support\Collection;

/** Resolve actual semester subjects, including subjects assigned through the timetable. */
class SemesterCurriculum
{
    public function forClass(SchoolClass $class): Collection
    {
        $class->loadMissing('subjectAssignments.subject', 'subjectAssignments.teacher', 'program');
        $rows = $class->subjectAssignments->filter(fn ($row) => $row->subject)->keyBy('subject_id');
        // Class Subjects is authoritative when both entry points define the same subject.
        $periods = $class->relationLoaded('timetableEntries') ? $class->timetableEntries
            : Timetable::with('subject', 'teacher')->where('school_class_id', $class->id)->orderBy('id')->get();
        foreach ($periods as $period) {
            if (! $period->subject || $rows->has($period->subject_id)) continue;
            $assignment = new ClassSubject();
            $assignment->forceFill(['school_class_id' => $class->id, 'subject_id' => $period->subject_id,
                'teacher_id' => $period->teacher_id]);
            $assignment->setRelation('subject', $period->subject);
            $assignment->setRelation('teacher', $period->teacher);
            $rows->put($period->subject_id, $assignment);
        }
        return $rows->each(fn ($row) => $row->setRelation('schoolClass', $class))
            ->sortBy(fn ($row) => $row->subject->name)->values();
    }

    public function forTeacher(int $teacherId): Collection
    {
        $ids = ClassSubject::where('teacher_id', $teacherId)->pluck('school_class_id')
            ->merge(Timetable::where('teacher_id', $teacherId)->pluck('school_class_id'))->unique();
        return SchoolClass::whereIn('id', $ids)->with('program', 'subjectAssignments.subject', 'subjectAssignments.teacher')->get()
            ->flatMap(fn ($class) => $this->forClass($class))
            ->filter(fn ($row) => (int) $row->teacher_id === $teacherId)->values();
    }

    public function syncCourses(?int $teacherId = null): Collection
    {
        if ($teacherId !== null) $assignments = $this->forTeacher($teacherId);
        else {
            $ids = ClassSubject::pluck('school_class_id')->merge(Timetable::pluck('school_class_id'))->unique();
            $assignments = SchoolClass::whereIn('id', $ids)->with('program', 'subjectAssignments.subject', 'subjectAssignments.teacher')
                ->get()->flatMap(fn ($class) => $this->forClass($class));
        }
        return $assignments->filter(fn ($row) => $row->teacher_id)
            ->map(fn ($row) => Course::syncForAssignment($row))->unique('id')->values();
    }
}
