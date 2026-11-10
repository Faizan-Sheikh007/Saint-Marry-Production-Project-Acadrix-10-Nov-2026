<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SchoolClass extends Model {
    use HasFactory;
    protected $table = 'school_classes';
    protected $fillable = ['program_id','name','section','level','capacity','description'];

    /** The Program (Class) this Section / Semester belongs to. */
    public function program() { return $this->belongsTo(Program::class); }

    /** Academic Sessions in which this Part/Semester is offered. */
    public function academicYears()
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_year_school_class')
            ->withTimestamps();
    }

    public function timetableEntries() { return $this->hasMany(Timetable::class); }

    public function students() { return $this->hasMany(Student::class); }
    public function feeTemplates() { return $this->hasMany(FeeTemplate::class); }

    /** Raw pivot rows: one per (subject, teacher) taught in this class. */
    public function subjectAssignments()
    {
        return $this->hasMany(ClassSubject::class)->with(['subject', 'teacher']);
    }

    /** Subjects taught in this class, with the assigned teacher available via ->pivot->teacher_id. */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject')
                     ->withPivot('teacher_id', 'id')
                     ->withTimestamps();
    }

    /** e.g. "BSCS › Semester 3 (A)" -- handy label for dropdowns/reports. */
    public function getDisplayNameAttribute(): string
    {
        $label = trim($this->name . ($this->section ? " ({$this->section})" : ''));
        return $this->program ? "{$this->program->name} › {$label}" : $label;
    }

    /** Just the "Semester 3 (A)" part, without the program prefix -- for use in structured/badge-based UI. */
    public function getSectionLabelAttribute(): string
    {
        return trim($this->name . ($this->section ? " ({$this->section})" : ''));
    }

    /**
     * Numeric cycle represented by names such as "Semester 7", "Part 2",
     * "Year 1". Prefers the digits that actually follow a recognised
     * cycle keyword (Semester/Sem/Part/Term/Year) so a class name that
     * also happens to contain an unrelated number first — e.g. a batch
     * or intake year like "BSCS 2024 - Semester 1" — doesn't get
     * mis-detected as term 2024 instead of term 1. Falls back to the
     * first digit group in the name for names that don't use one of
     * those keywords at all.
     */
    public function getTermNumberAttribute(): ?int
    {
        if (preg_match('/(?:semester|sem|part|term|year)\.?\s*[-#]?\s*(\d+)/i', $this->name, $m)) {
            return (int) $m[1];
        }

        return preg_match('/(\d+)/', $this->name, $m) ? (int) $m[1] : null;
    }
}
