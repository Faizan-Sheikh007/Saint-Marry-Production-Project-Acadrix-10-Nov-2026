<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $table = 'course_enrollments';
    protected $fillable = ['course_id', 'student_id', 'enrolled_at', 'progress_percent', 'status'];
    protected $casts = ['enrolled_at' => 'datetime'];

    public function course()  { return $this->belongsTo(Course::class); }
    public function student() { return $this->belongsTo(Student::class); }
}
