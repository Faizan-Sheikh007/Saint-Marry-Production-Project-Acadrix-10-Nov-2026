<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherMessage;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class TeacherMessageController extends Controller
{
    /** List every teacher, with unread-from-teacher counts, so admin can pick who to message. */
    public function index()
    {
        $teachers = Teacher::query()
            ->where('status', 'active')
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_read', false)->whereHas('sender', fn ($s) => $s->where('role', 'teacher'));
            }])
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('unread_count')
            ->orderBy('name')
            ->get();

        return view('admin.messages.index', compact('teachers'));
    }

    /** Full thread with one teacher. */
    public function show(Teacher $teacher)
    {
        $thread = TeacherMessage::where('teacher_id', $teacher->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        // Mark everything the teacher sent as read once admin opens the thread.
        TeacherMessage::where('teacher_id', $teacher->id)
            ->where('is_read', false)
            ->whereHas('sender', fn ($q) => $q->where('role', 'teacher'))
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('admin.messages.show', compact('teacher', 'thread'));
    }

    public function store(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        $msg = TeacherMessage::create([
            'teacher_id'   => $teacher->id,
            'sender_id'    => auth()->id(),
            'title'        => 'Message from Admin',
            'subject'      => 'Message from Admin',
            'message'      => $data['message'],
            'message_type' => 'chat',
            'type'         => 'chat',
            'priority'     => $data['priority'] ?? 'normal',
            'audience'     => 'individual',
            'status'       => 'sent',
            'sent_at'      => now(),
            'is_read'      => false,
        ]);

        if ($teacher->user_id) {
            NotifierService::notify(
                $teacher->user_id,
                'New message from Admin: ' . \Illuminate\Support\Str::limit($data['message'], 60),
                'fa-comment-dots',
                'blue'
            );
        }

        return back()->with('success', 'Message sent to ' . $teacher->name . '.');
    }
}
