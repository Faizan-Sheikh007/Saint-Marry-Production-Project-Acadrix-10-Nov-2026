<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Fee extends Model {
    use HasFactory;
    protected $fillable = ['student_id','amount','fee_type','due_date','paid_at','status','month','description','receipt_no'];
    protected $casts = ['due_date' => 'date', 'paid_at' => 'datetime'];
    public function student() { return $this->belongsTo(Student::class); }
}
