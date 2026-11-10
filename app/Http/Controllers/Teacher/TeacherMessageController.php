<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherMessage;
use App\Models\User;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class TeacherMessageController extends Controller
{
    /** Single running thread between this teacher and Admin. */
    public function index()
    {
        $teacher = auth()->user()->teacher;

        $thread = $teacher
            ? TeacherMessage::where('teacher_id', $teacher->id)->with('sender')->orderBy('created_at')->get()
            : collect();

        if ($teacher) {
            TeacherMessage::where('teacher_id', $teacher->id)
                ->where('is_read', false)
                ->whereHas('sender', fn ($q) => $q->where('role', 'admin'))
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return view('teacher.messages.index', compact('thread', 'teacher'));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403, 'No teacher profile linked to this account.');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        TeacherMessage::create([
            'teacher_id'   => $teacher->id,
            'sender_id'    => auth()->id(),
            'title'        => 'Reply from ' . $teacher->name,
            'subject'      => 'Reply from ' . $teacher->name,
            'message'      => $data['message'],
            'message_type' => 'chat',
            'type'         => 'chat',
            'priority'     => 'normal',
            'audience'     => 'individual',
            'status'       => 'sent',
            'sent_at'      => now(),
            'is_read'      => false,
        ]);

        NotifierService::notifyAdmins(
            $teacher->name . ' sent a message: ' . \Illuminate\Support\Str::limit($data['message'], 60),
            'fa-comment-dots',
            'blue'
        );

        return back()->with('success', 'Message sent.');
    }
}
