<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'student_id',
        'student_name', 'student_roll_no', 'class_name', 'program_name', 'semester', 'contact_number',
        'issue_date', 'days', 'due_date', 'fine_per_day',
        'status', 'returned_date', 'fine_amount',
    ];

    protected $casts = [
        'issue_date'    => 'date',
        'due_date'      => 'date',
        'returned_date' => 'date',
        'fine_per_day'  => 'decimal:2',
        'fine_amount'   => 'decimal:2',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'issued' && $this->due_date->lt(Carbon::today());
    }

    /** Number of days currently overdue (0 if not overdue / already returned). */
    public function overdueDays(): int
    {
        if ($this->status !== 'issued' || $this->due_date->gte(Carbon::today())) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(Carbon::today());
    }

    /** Live-estimated fine if returned today, based on the per-day rate. */
    public function estimatedFine(): float
    {
        return round($this->overdueDays() * (float) $this->fine_per_day, 2);
    }
}
