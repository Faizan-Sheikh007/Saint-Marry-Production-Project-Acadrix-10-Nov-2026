<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherAnnouncement;
use App\Models\User;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class TeacherAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = TeacherAnnouncement::with(['author', 'teacher'])->latest()->paginate(15);
        $teachers = Teacher::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.announcements.index', compact('announcements', 'teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:190'],
            'content'    => ['required', 'string', 'max:5000'],
            'audience'   => ['required', 'in:all,specific'],
            'teacher_id' => ['nullable', 'required_if:audience,specific', 'exists:teachers,id'],
            'pinned'     => ['nullable', 'boolean'],
        ]);

        $announcement = TeacherAnnouncement::create([
            'title'      => $data['title'],
            'content'    => $data['content'],
            'created_by' => auth()->id(),
            'audience'   => $data['audience'],
            'teacher_id' => $data['audience'] === 'specific' ? $data['teacher_id'] : null,
            'pinned'     => $request->boolean('pinned'),
        ]);

        // Notify whoever it's for.
        if ($announcement->audience === 'specific') {
            $teacher = Teacher::find($announcement->teacher_id);
            if ($teacher?->user_id) {
                NotifierService::notify($teacher->user_id, 'New announcement: ' . $announcement->title, 'fa-bullhorn', 'yellow');
            }
        } else {
            User::where('role', 'teacher')->pluck('id')->each(
                fn ($id) => NotifierService::notify($id, 'New announcement: ' . $announcement->title, 'fa-bullhorn', 'yellow')
            );
        }

        return back()->with('success', 'Announcement posted.');
    }

    public function destroy(TeacherAnnouncement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement removed.');
    }
}
