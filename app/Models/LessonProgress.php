<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';
    protected $fillable = ['lesson_id', 'student_id', 'completed', 'completed_at', 'time_spent'];
    protected $casts = ['completed' => 'boolean', 'completed_at' => 'datetime'];

    public function lesson()  { return $this->belongsTo(Lesson::class); }
    public function student() { return $this->belongsTo(Student::class); }
}
