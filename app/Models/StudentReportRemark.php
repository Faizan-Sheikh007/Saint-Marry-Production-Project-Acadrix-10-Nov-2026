<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentReportRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'teacher_name', 'term', 'remark_date', 'remarks', 'created_by',
    ];

    protected $casts = [
        'remark_date' => 'date',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
