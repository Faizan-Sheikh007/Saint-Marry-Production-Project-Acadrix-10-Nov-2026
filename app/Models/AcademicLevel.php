<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed classification shown on the admission form (replaces the
 * old hardcoded College/University radio). When `requires_institution` is
 * true, selecting this level on the admission form reveals the Institution
 * dropdown and filters Programs down to that Institution.
 */
class AcademicLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'requires_institution', 'sort_order', 'is_active'];

    protected $casts = [
        'requires_institution' => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
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
