<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_id', 'student_id', 'roll_number',
        'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
        'voucher_id', 'fee_status',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function internship() { return $this->belongsTo(Internship::class); }
    public function student()    { return $this->belongsTo(Student::class); }
    public function reviewer()   { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function voucher()    { return $this->belongsTo(Voucher::class); }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'        => 'Pending Review',
            'admin_approved' => $this->fee_status === 'paid' ? 'Confirmed' : 'Approved — Awaiting Payment',
            'admin_rejected' => 'Rejected',
            default          => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'        => 'warning',
            'admin_approved' => $this->fee_status === 'paid' ? 'success' : 'info',
            'admin_rejected' => 'danger',
            default          => 'gray',
        };
    }
}
