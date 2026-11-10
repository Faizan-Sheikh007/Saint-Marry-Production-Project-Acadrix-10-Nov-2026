<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content', 'created_by', 'audience', 'teacher_id', 'pinned'];
    protected $casts = ['pinned' => 'boolean'];

    public function author()  { return $this->belongsTo(User::class, 'created_by'); }
    public function teacher() { return $this->belongsTo(Teacher::class); }

    /** Announcements visible to a given teacher: broadcast to all, or targeted at them. */
    public function scopeVisibleTo($query, int $teacherId)
    {
        return $query->where(function ($q) use ($teacherId) {
            $q->where('audience', 'all')
              ->orWhere(function ($q2) use ($teacherId) {
                  $q2->where('audience', 'specific')->where('teacher_id', $teacherId);
              });
        });
    }
}
