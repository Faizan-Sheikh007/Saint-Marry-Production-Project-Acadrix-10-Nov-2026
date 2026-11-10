<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Message extends Model {
    use HasFactory;
    protected $fillable = [
        'title', 'message', 'type', 'audience', 'status', 'sent_at',
        'student_id', 'sent_by', 'recipient_student_email', 'recipient_parent_email',
    ];
    protected $casts = ['sent_at' => 'datetime'];

    public function student() { return $this->belongsTo(Student::class); }
    public function sender()  { return $this->belongsTo(User::class, 'sent_by'); }
}
