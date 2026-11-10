<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Subject extends Model {
    use HasFactory;
    protected $fillable = ['name','code','description','credit_hours'];

    /** Raw pivot rows: one per (class, teacher) this subject is taught in. */
    public function classAssignments()
    {
        return $this->hasMany(ClassSubject::class);
    }

    /** Classes this subject is taught in, with the assigned teacher available via ->pivot->teacher_id. */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject')
                     ->withPivot('teacher_id', 'id')
                     ->withTimestamps();
    }
}
