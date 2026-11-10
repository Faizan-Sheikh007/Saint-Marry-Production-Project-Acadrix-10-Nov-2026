<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examination extends Model {
    use HasFactory;

    protected $fillable = [
        'academic_year_id', 'batch_id', 'name', 'school_class_id', 'program_id', 'subject_id', 'teacher_id',
        'exam_date', 'start_time', 'end_time', 'room',
        'total_marks', 'passing_marks', 'type', 'status', 'locked_at',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'locked_at' => 'datetime',
    ];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }

    /** Never substitute a global default session for this subject paper's actual session. */
    public function getSessionLabelAttribute(): string
    {
        if ($this->academicYear) return $this->academicYear->name;
        $years = $this->schoolClass?->academicYears;
        return $years && $years->count() === 1 ? $years->first()->name : 'Not selected';
    }

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function program()     { return $this->belongsTo(Program::class); }
    public function subject()     { return $this->belongsTo(Subject::class); }
    public function teacher()     { return $this->belongsTo(Teacher::class); }
    public function results()     { return $this->hasMany(ExamResult::class); }
    public function examPaper()   { return $this->hasOne(ExamPaper::class); }

    /** Every examination created together in one scheduling run. */
    public function batchSiblings()
    {
        return static::where('batch_id', $this->batch_id)->where('batch_id', '!=', null);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    /** Whether the given teacher is allowed to enter/edit marks for this exam. */
    public function canBeMarkedBy(?Teacher $teacher): bool
    {
        if (!$teacher || $this->isLocked()) return false;
        return (int) $this->teacher_id === (int) $teacher->id;
    }

    public function getMarksEnteredCountAttribute(): int
    {
        return $this->results()->count();
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'midterm'   => 'Mid Term',
            'final'     => 'Final',
            'quiz'      => 'Quiz',
            'unit_test' => 'Unit Test',
            default     => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /** "10:00 AM - 11:30 AM" style label, or null if no time was set. */
    public function getTimeLabelAttribute(): ?string
    {
        if (!$this->start_time) return null;
        $start = \Carbon\Carbon::parse($this->start_time)->format('g:i A');
        $end   = $this->end_time ? \Carbon\Carbon::parse($this->end_time)->format('g:i A') : null;
        return $end ? "{$start} - {$end}" : $start;
    }
}
