<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'image_path', 'fee_amount', 'due_date',
        'target_type', 'target_program_ids', 'target_student_ids',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'fee_amount'          => 'decimal:2',
        'due_date'            => 'date',
        'is_active'           => 'boolean',
        'target_program_ids'  => 'array',
        'target_student_ids'  => 'array',
    ];

    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }
    public function applications(){ return $this->hasMany(InternshipApplication::class); }

    /** Whether the given student is inside this posting's chosen audience. */
    public function isVisibleTo(Student $student): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->target_type) {
            'programs' => in_array($student->program_id, $this->target_program_ids ?? []),
            'students' => in_array($student->id, $this->target_student_ids ?? []),
            default    => true, // 'all'
        };
    }

    public function applicantsCount(): int
    {
        return $this->applications()->count();
    }

    public function pendingCount(): int
    {
        return $this->applications()->where('status', 'pending')->count();
    }

    public function paidCount(): int
    {
        return $this->applications()->where('fee_status', 'paid')->count();
    }
}
