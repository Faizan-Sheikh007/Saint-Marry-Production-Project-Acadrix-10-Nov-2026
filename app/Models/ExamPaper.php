<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    use HasFactory;

    protected $fillable = ['examination_id', 'teacher_id', 'status', 'instructions', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function examination() { return $this->belongsTo(Examination::class); }
    public function teacher()     { return $this->belongsTo(Teacher::class); }

    public function questions()
    {
        return $this->hasMany(ExamPaperQuestion::class)->orderBy('type')->orderBy('order_no');
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'due'      => 'Due',
            'pending'  => 'Pending',
            'uploaded' => 'Uploaded',
            default    => ucfirst($this->status),
        };
    }

    /** Maps to the existing badge CSS classes used across the app (badge success/warning/danger). */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'uploaded' => 'success',
            'pending'  => 'warning',
            'due'      => 'danger',
            default    => 'muted',
        };
    }

    public function getShortQuestionsCountAttribute(): int
    {
        return $this->questions->where('type', 'short')->count();
    }

    public function getLongQuestionsCountAttribute(): int
    {
        return $this->questions->where('type', 'long')->count();
    }

    public function getTotalMarksAllocatedAttribute(): int
    {
        return (int) $this->questions->sum('marks');
    }
}
