<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsQuestion extends Model
{
    protected $table = 'lms_questions';
    protected $fillable = [
        'course_id', 'student_id', 'teacher_id',
        'question', 'answer', 'answered_at', 'status',
    ];
    protected $casts = ['answered_at' => 'datetime'];

    public function course()  { return $this->belongsTo(Course::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
}
