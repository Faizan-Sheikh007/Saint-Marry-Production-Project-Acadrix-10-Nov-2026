<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    protected $fillable = [
        'course_id', 'teacher_id', 'title', 'description',
        'type', 'file_path', 'file_type', 'url', 'order',
    ];

    public function course()  { return $this->belongsTo(Course::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }
}
