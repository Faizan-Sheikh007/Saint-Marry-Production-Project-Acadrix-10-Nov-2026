<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question', 'type', 'options', 'correct_answer', 'marks', 'order',
    ];
    protected $casts = ['options' => 'array'];

    public function quiz() { return $this->belongsTo(Quiz::class); }
}
