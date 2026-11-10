<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Attendance extends Model {
    use HasFactory;
    protected $fillable = ['student_id','school_class_id','subject_id','date','status','remarks'];
    protected $casts = ['date' => 'date'];
    public function student() { return $this->belongsTo(Student::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
