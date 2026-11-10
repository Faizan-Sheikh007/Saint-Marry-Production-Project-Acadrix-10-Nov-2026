<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An affiliated university/facility (e.g. "Punjab Medical Facility",
 * "Government College University Faisalabad"). When an admin sets a
 * Program's level to "university" and picks one of these, its name and
 * disclaimer surface on the admission form and installment plan slip for
 * that program/department. Admins manage this list and its disclaimer text
 * from Admin > Institutions.
 */
class Institution extends Model
{
    protected $fillable = ['name', 'disclaimer', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
