<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
    protected $fillable = [
        'batch_id', 'school_class_id', 'published_at', 'published_by', 'notes', 'exam_name',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /** Find (without creating) the publication row for a batch+class, if any. */
    public static function for(string $batchId, int $schoolClassId): ?self
    {
        return static::where('batch_id', $batchId)->where('school_class_id', $schoolClassId)->first();
    }
}
