<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsAnnouncement extends Model
{
    protected $table = 'lms_announcements';
    protected $fillable = ['course_id', 'teacher_id', 'title', 'content', 'pinned'];
    protected $casts = ['pinned' => 'boolean'];

    public function course()  { return $this->belongsTo(Course::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
}
