<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'level', 'duration_terms', 'description', 'is_active',
        'institution_id', 'department_disclaimer', 'system_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** The affiliated university/facility (only relevant when level = university). */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * The disclaimer actually shown for this program/department: the
     * program's own override if set, otherwise the parent Institution's
     * default disclaimer, otherwise null.
     */
    public function getEffectiveDisclaimerAttribute(): ?string
    {
        return $this->department_disclaimer ?: ($this->institution->disclaimer ?? null);
    }

    /**
     * Valid term/session labels for this program, driven by system_type:
     * annual programs are split into "Part 1" / "Part 2"; semester programs
     * are split into "Semester 1".."Semester N" where N is duration_terms
     * (expected to be 8 or 10).
     */
    public function getTermLabelsAttribute(): array
    {
        if ($this->system_type === 'annual') {
            return ['Part 1', 'Part 2'];
        }

        $count = $this->duration_terms ?: 8;
        return collect(range(1, $count))->map(fn ($n) => "Semester {$n}")->all();
    }

    /** All Sections / Semesters that belong to this Program. */
    public function sections()
    {
        return $this->hasMany(SchoolClass::class, 'program_id');
    }

    /** Limit Programs to those actually offered in a particular session. */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->whereHas('sections.academicYears', function ($q) use ($academicYearId) {
            $q->where('academic_years.id', $academicYearId);
        });
    }

    /**
     * The real number of semesters/years this program has, read straight
     * from its actual Parts/Semesters records (the "Parts/Semesters" module
     * an admin manages under this Program) rather than the duration_terms
     * column — which is only used to *generate* those records in the first
     * place and can drift out of sync if semesters are added/removed by
     * hand afterwards. Returns null if the program has no sections yet.
     */
    public function actualTermCount(): ?int
    {
        $max = 0;
        foreach ($this->sections as $section) {
            if (preg_match('/(\d+)/', $section->name, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $max ?: null;
    }

    /** All students admitted into this Program (through any of its sections). */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
