<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $table = 'assignment_submissions';
    protected $fillable = [
        'assignment_id', 'student_id', 'content', 'file_path',
        'submitted_at', 'marks_obtained', 'feedback', 'status',
    ];
    protected $casts = ['submitted_at' => 'datetime'];

    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function student()    { return $this->belongsTo(Student::class); }
}
