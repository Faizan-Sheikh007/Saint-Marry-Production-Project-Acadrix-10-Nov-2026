<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ExamResult extends Model {
    use HasFactory;
    protected $table = 'exam_results';
    protected $fillable = ['examination_id','student_id','graded_by','marks_obtained','percentage','grade','remarks'];
    public function examination() { return $this->belongsTo(Examination::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function gradedBy() { return $this->belongsTo(Teacher::class, 'graded_by'); }
}
