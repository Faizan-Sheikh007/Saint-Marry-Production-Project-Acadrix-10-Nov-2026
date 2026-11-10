<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed replacement for the old free-text Session input on the
 * admission form (e.g. "2025-2026").
 */
class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_default', 'sort_order', 'is_active'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Resolve the date the session ends for ID-card validity.
     *
     * An explicitly configured end_date always wins. For legacy sessions that
     * only have a label such as "2023-2027", keep the day/month from the
     * session start (or the student's admission date) and use the ending year
     * from the session label. Example: 05-10-2023 -> 05-10-2027.
     */
    public function effectiveEndDate($fallbackDate = null)
    {
        if ($this->end_date) {
            return $this->end_date->copy();
        }

        preg_match_all('/\b(?:19|20)\d{2}\b/', (string) $this->name, $matches);
        if (empty($matches[0])) {
            return null;
        }

        $endYear = (int) end($matches[0]);
        $anchor = $this->start_date ?: $fallbackDate;
        if (!$anchor) {
            return null;
        }

        $date = $anchor instanceof \Carbon\CarbonInterface
            ? $anchor->copy()
            : \Carbon\Carbon::parse($anchor);

        // Clamp leap-day / month-end dates safely when moving to another year.
        $month = $date->month;
        $day = min($date->day, \Carbon\Carbon::create($endYear, $month, 1)->daysInMonth);

        return \Carbon\Carbon::create($endYear, $month, $day)->startOfDay();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Exact Parts/Semesters offered in this academic session.
     * Each SchoolClass belongs to a Program, giving us the complete
     * Session -> Program -> Part/Semester structure without duplicating the
     * program id in another pivot table.
     */
    public function schoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'academic_year_school_class')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
