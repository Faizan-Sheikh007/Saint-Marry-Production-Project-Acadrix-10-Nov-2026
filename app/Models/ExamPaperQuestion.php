<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPaperQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['exam_paper_id', 'type', 'question', 'options', 'correct_answer', 'image_path', 'marks', 'order_no'];
    protected $casts = ['options' => 'array'];

    public const TYPES = [
        'short'      => 'Short Question',
        'long'       => 'Long Question',
        'mcq'        => 'Multiple Choice (MCQ)',
        'fill_blank' => 'Fill in the Blank',
        'true_false' => 'True / False',
    ];

    public function examPaper() { return $this->belongsTo(ExamPaper::class); }
}
