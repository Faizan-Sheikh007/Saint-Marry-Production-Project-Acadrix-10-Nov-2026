<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LmsAnnouncement;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'description', 'subject_id', 'teacher_id',
        'school_class_id', 'thumbnail', 'status', 'order',
    ];

    public function subject()     { return $this->belongsTo(Subject::class); }
    public function teacher()     { return $this->belongsTo(Teacher::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function lessons()     { return $this->hasMany(Lesson::class)->orderBy('order'); }
    public function assignments() { return $this->hasMany(Assignment::class); }
    public function quizzes()     { return $this->hasMany(Quiz::class); }
    public function enrollments() { return $this->hasMany(CourseEnrollment::class); }
    public function materials()   { return $this->hasMany(CourseMaterial::class)->orderBy('order'); }
    public function questions()   { return $this->hasMany(LmsQuestion::class); }

    public function isEnrolledBy(Student $student): bool
    {
        return $this->enrollments()->where('student_id', $student->id)->exists();
    }

    /**
     * Keep the LMS Course in sync with a real ClassSubject assignment
     * (Admin > Subjects > assign subject-to-class-and-teacher).
     *
     * One Course per (subject, class) is treated as the "official" course for
     * that subject — if one already exists (e.g. a teacher built it manually)
     * its content is preserved and only the teacher/status are updated; if
     * none exists, a fresh one is created with the subject's own name as the
     * title so students see their real subjects, not placeholder titles.
     *
     * Publishing it here also triggers auto-enrollment for every active
     * student currently in that class, so newly-assigned subjects show up
     * under "My Courses" immediately — no manual course creation needed.
     */
    public static function syncForAssignment(ClassSubject $assignment): self
    {
        $assignment->loadMissing('subject');

        $course = static::updateOrCreate(
            [
                'subject_id'      => $assignment->subject_id,
                'school_class_id' => $assignment->school_class_id,
            ],
            [
                'title'      => $assignment->subject->name ?? 'Untitled Subject',
                'teacher_id' => $assignment->teacher_id,
                'status'     => 'published',
            ]
        );

        Student::where('school_class_id', $assignment->school_class_id)
            ->where('status', 'active')
            ->each(fn ($s) => CourseEnrollment::firstOrCreate(
                ['course_id' => $course->id, 'student_id' => $s->id],
                ['enrolled_at' => now(), 'status' => 'active']
            ));

        return $course;
    }

    /**
     * When a subject is unassigned from a class, archive (don't delete) its
     * matching course — preserves lessons/materials but hides it from
     * students' "My Courses" and browse listings.
     */
    public static function archiveForUnassignment(int $subjectId, int $schoolClassId): void
    {
        static::where('subject_id', $subjectId)
            ->where('school_class_id', $schoolClassId)
            ->update(['status' => 'archived']);
    }
    public function lmsAnnouncements()
{
    return $this->hasMany(LmsAnnouncement::class);
    // or hasOne, belongsToMany, etc. depending on your structure
}
}
