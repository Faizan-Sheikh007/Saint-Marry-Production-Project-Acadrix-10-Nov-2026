<?php
namespace App\Http\Controllers\LMS;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsAnnouncement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $teacher = auth()->user()->teacher;
        $data    = $request->validate([
            "title"   => "required|string|max:255",
            "content" => "required|string",
            "pinned"  => "nullable|boolean",
        ]);
        LmsAnnouncement::create([
            "course_id"  => $course->id,
            "teacher_id" => $teacher?->id,
            "title"      => $data["title"],
            "content"    => $data["content"],
            "pinned"     => $request->boolean("pinned"),
        ]);
        return back()->with("success", "Announcement posted!");
    }

    public function destroy(Course $course, LmsAnnouncement $announcement)
    {
        $announcement->delete();
        return back()->with("success", "Announcement deleted!");
    }
}
