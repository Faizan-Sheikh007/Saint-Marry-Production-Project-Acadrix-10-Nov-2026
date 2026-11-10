<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id', 'student_id', 'answers', 'score',
        'total_marks', 'percentage', 'started_at', 'submitted_at', 'status',
    ];
    protected $casts = [
        'answers'      => 'array',
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function student() { return $this->belongsTo(Student::class); }
}
