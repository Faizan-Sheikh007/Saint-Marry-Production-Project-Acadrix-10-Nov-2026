<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasStudentAudience;
    protected $fillable = [
        'published_at', 'target_student_ids',
        'course_id', 'title', 'description', 'time_limit',
        'total_marks', 'passing_marks', 'attempts_allowed', 'status', 'due_date',
        'teacher_id', 'is_admin_request', 'assigned_by', 'teacher_response',
        'request_status', 'admin_reviewed_at',
    ];
    protected $casts = [
        'published_at' => 'datetime',
        'target_student_ids' => 'array',
        'due_date'          => 'datetime',
        'is_admin_request'  => 'boolean',
        'admin_reviewed_at' => 'datetime',
    ];

    public function course()    { return $this->belongsTo(Course::class); }
    public function questions() { return $this->hasMany(QuizQuestion::class)->orderBy('order'); }
    public function attempts()  { return $this->hasMany(QuizAttempt::class); }
    public function teacher()   { return $this->belongsTo(Teacher::class); }
    public function assigner()  { return $this->belongsTo(User::class, 'assigned_by'); }

    /** Admin -> teacher direct requests waiting on (or holding) a written paragraph. */
    public function scopeAdminRequests($query)
    {
        return $query->where('is_admin_request', true);
    }

    public function attemptByStudent(int $studentId)
    {
        return $this->attempts()->where('student_id', $studentId)->latest()->first();
    }
}
