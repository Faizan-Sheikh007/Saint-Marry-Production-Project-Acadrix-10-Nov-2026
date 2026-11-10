<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'subject_id', 'name', 'tour_date', 'place', 'notes', 'assigned_by', 'status',
    ];
    protected $casts = ['tour_date' => 'date'];

    public function teacher()  { return $this->belongsTo(Teacher::class); }
    public function subject()  { return $this->belongsTo(Subject::class); }
    public function assigner() { return $this->belongsTo(User::class, 'assigned_by'); }
}
