<?php
namespace App\Http\Controllers\LMS;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function create(Course $course)
    {
        return view("lms.lessons.form", compact("course"));
    }

    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            "title"            => "required|string|max:255",
            "description"      => "nullable|string",
            "content"          => "nullable|string",
            "video_url"        => "nullable|url",
            "type"             => "required|in:text,video,file,mixed",
            "order"            => "nullable|integer",
            "duration_minutes" => "nullable|integer",
            "is_free"          => "nullable|boolean",
            "attachment"       => "nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,mp4",
        ]);
        if ($request->hasFile("attachment")) {
            $data["file_attachment"] = $request->file("attachment")->store("lms/lessons/" . $course->id, "public");
        }
        unset($data["attachment"]);
        $data["course_id"] = $course->id;
        $data["order"]     = $data["order"] ?? ($course->lessons()->max("order") + 1);
        $data["is_free"]   = $request->boolean("is_free");
        Lesson::create($data);
        return redirect()->route("lms.courses.show", $course)->with("success", "Lesson added!");
    }

    public function show(Course $course, Lesson $lesson)
    {
        $student  = auth()->user()->isStudent() ? auth()->user()->student : null;
        $progress = $student
            ? LessonProgress::where(["lesson_id" => $lesson->id, "student_id" => $student->id])->first()
            : null;
        $prev = $course->lessons()->where("order", "<", $lesson->order)->orderByDesc("order")->first();
        $next = $course->lessons()->where("order", ">", $lesson->order)->orderBy("order")->first();
        return view("lms.lessons.show", compact("course", "lesson", "student", "progress", "prev", "next"));
    }

    public function edit(Course $course, Lesson $lesson)
    {
        return view("lms.lessons.form", compact("course", "lesson"));
    }

    public function update(Request $request, Course $course, Lesson $lesson)
    {
        $data = $request->validate([
            "title"            => "required|string|max:255",
            "description"      => "nullable|string",
            "content"          => "nullable|string",
            "video_url"        => "nullable|url",
            "type"             => "required|in:text,video,file,mixed",
            "order"            => "nullable|integer",
            "duration_minutes" => "nullable|integer",
            "attachment"       => "nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,mp4",
        ]);
        if ($request->hasFile("attachment")) {
            if ($lesson->file_attachment) {
                Storage::disk("public")->delete($lesson->file_attachment);
            }
            $data["file_attachment"] = $request->file("attachment")->store("lms/lessons/" . $course->id, "public");
        }
        unset($data["attachment"]);
        $lesson->update($data);
        return redirect()->route("lms.courses.show", $course)->with("success", "Lesson updated!");
    }

    public function destroy(Course $course, Lesson $lesson)
    {
        $lesson->delete();
        return redirect()->route("lms.courses.show", $course)->with("success", "Lesson deleted!");
    }

    public function markComplete(Course $course, Lesson $lesson)
    {
        $student = auth()->user()->student;
        LessonProgress::updateOrCreate(
            ["lesson_id" => $lesson->id, "student_id" => $student->id],
            ["completed" => true, "completed_at" => now()]
        );
        $total     = $course->lessons()->count();
        $completed = LessonProgress::where("student_id", $student->id)
            ->whereIn("lesson_id", $course->lessons()->pluck("id"))
            ->where("completed", true)->count();
        CourseEnrollment::where(["course_id" => $course->id, "student_id" => $student->id])
            ->update(["progress_percent" => $total > 0 ? round($completed / $total * 100) : 0]);
        return back()->with("success", "Lesson marked complete!");
    }
}
