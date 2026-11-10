<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A chat message between Admin and a specific Teacher.
 * `sender_id` is the users.id of whoever sent it (admin or the teacher's own user account).
 * `parent_id` threads replies so a teacher <-> admin conversation reads like a chat.
 */
class TeacherMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'sender_id', 'parent_id', 'title', 'subject', 'message',
        'message_type', 'type', 'priority', 'audience', 'status', 'sent_at',
        'scheduled_at', 'is_read', 'read_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'scheduled_at' => 'datetime',
        'read_at'      => 'datetime',
        'is_read'      => 'boolean',
    ];

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function sender()  { return $this->belongsTo(User::class, 'sender_id'); }
    public function parent()  { return $this->belongsTo(TeacherMessage::class, 'parent_id'); }
    public function replies() { return $this->hasMany(TeacherMessage::class, 'parent_id'); }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /** Was this particular message sent by the admin side (vs. the teacher replying)? */
    public function isFromAdmin(): bool
    {
        return optional($this->sender)->role === 'admin';
    }
}
