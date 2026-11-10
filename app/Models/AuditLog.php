<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model {
    use HasFactory;
    protected $fillable = ['user_id','action','subject_type','subject_id','description'];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, string $subjectType, ?int $subjectId, string $description): void
    {
        static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => $description,
        ]);
    }
}
