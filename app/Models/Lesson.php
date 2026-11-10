<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id', 'title', 'description', 'content',
        'video_url', 'file_attachment', 'type', 'order', 'duration_minutes', 'is_free',
    ];
    protected $casts = ['is_free' => 'boolean'];

    public function course()    { return $this->belongsTo(Course::class); }
    public function progress()  { return $this->hasMany(LessonProgress::class); }

    public function isCompletedBy(int $studentId): bool
    {
        return $this->progress()
            ->where('student_id', $studentId)
            ->where('completed', true)
            ->exists();
    }
}
