<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GradingScale extends Model
{
    use HasFactory;

    protected $fillable = ['grade', 'min_percentage', 'max_percentage', 'remarks', 'sort_order'];

    protected $casts = [
        'min_percentage' => 'float',
        'max_percentage' => 'float',
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('grading-scale:bands'));
        static::deleted(fn () => Cache::forget('grading-scale:bands'));
    }

    /** All bands, highest percentage first — the order grading is evaluated in. */
    public static function bands()
    {
        return Cache::rememberForever('grading-scale:bands', function () {
            return static::orderByDesc('min_percentage')->get();
        });
    }

    /**
     * Resolve a percentage to a grade using the admin-defined formula.
     * Falls back to 'N/A' only if no bands have been configured at all.
     */
    public static function gradeFor(float $percentage): string
    {
        $band = static::bands()->first(fn ($b) => $percentage >= $b->min_percentage
            && ($b->max_percentage === null || $percentage <= $b->max_percentage));

        return $band->grade ?? 'N/A';
    }

    public static function remarksFor(float $percentage): ?string
    {
        $band = static::bands()->first(fn ($b) => $percentage >= $b->min_percentage
            && ($b->max_percentage === null || $percentage <= $b->max_percentage));

        return $band->remarks ?? null;
    }
}
