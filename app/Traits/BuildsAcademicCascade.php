<?php

namespace App\Traits;

use App\Models\AcademicYear;

/**
 * Shared Session -> Program -> Semester/Part -> Subject -> Teacher hierarchy
 * used by admin filters. The Academic Session <-> SchoolClass pivot is the
 * single source of truth, so duplicate-looking labels such as "Part 1" never
 * leak in from another program/session.
 */
trait BuildsAcademicCascade
{
    protected function academicCascadeYears(bool $withCurriculum = false)
    {
        $relations = ['schoolClasses.program'];

        if ($withCurriculum) {
            $relations[] = 'schoolClasses.subjectAssignments.subject';
            $relations[] = 'schoolClasses.subjectAssignments.teacher';
            $relations[] = 'schoolClasses.timetableEntries.subject';
            $relations[] = 'schoolClasses.timetableEntries.teacher';
        }

        return AcademicYear::with($relations)->ordered()->get();
    }

    protected function buildAcademicCascade($academicYears, bool $withCurriculum = false): array
    {
        $structure = [];

        foreach ($academicYears as $year) {
            $classes = $year->schoolClasses
                ->filter(fn ($class) => $class->program)
                ->sortBy(fn ($class) => [
                    $class->program->name,
                    $class->term_number ?? 999,
                    $class->section ?? '',
                    $class->name,
                ])
                ->values();

            $programs = $classes->groupBy('program_id')->map(function ($programClasses) use ($withCurriculum) {
                $program = $programClasses->first()->program;

                $terms = $programClasses->map(function ($class) use ($withCurriculum) {
                    $term = [
                        'id'      => $class->id,
                        'name'    => $class->section_label,
                        'term_no' => $class->term_number,
                    ];

                    if ($withCurriculum) {
                        $subjects = app(\App\Services\SemesterCurriculum::class)->forClass($class)
                            ->filter(fn ($assignment) => $assignment->subject)
                            ->groupBy('subject_id')
                            ->map(function ($assignments) {
                                $subject = $assignments->first()->subject;
                                $teachers = $assignments
                                    ->pluck('teacher')
                                    ->filter()
                                    ->unique('id')
                                    ->sortBy('name')
                                    ->values()
                                    ->map(fn ($teacher) => [
                                        'id'   => $teacher->id,
                                        'name' => $teacher->name,
                                    ])->all();

                                return [
                                    'id'       => $subject->id,
                                    'name'     => $subject->name,
                                    'teachers' => $teachers,
                                ];
                            })
                            ->sortBy('name')
                            ->values()
                            ->all();

                        $term['subjects'] = $subjects;
                    }

                    return $term;
                })->values()->all();

                return [
                    'id'    => $program->id,
                    'name'  => $program->name,
                    'terms' => $terms,
                ];
            })->sortBy('name')->values()->all();

            $structure[(string) $year->id] = [
                'id'         => $year->id,
                'name'       => $year->name,
                'is_default' => (bool) $year->is_default,
                'programs'   => $programs,
            ];
        }

        return $structure;
    }
}
