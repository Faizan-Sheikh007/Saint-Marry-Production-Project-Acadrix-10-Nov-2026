<?php
namespace App\Http\Controllers\LMS;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /** Teacher-only: upload a file (PDF/Doc/Presentation/etc.) or share an external link. */
    public function store(Request $request, Course $course)
    {
        $teacher = auth()->user()->teacher;

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:file,link',
            'url'         => 'required_if:type,link|nullable|url',
            'file'        => 'required_if:type,file|nullable|file|max:51200|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png',
        ]);

        $payload = [
            'course_id'   => $course->id,
            'teacher_id'  => $teacher?->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'type'        => $data['type'],
            'order'       => $course->materials()->max('order') + 1,
        ];

        if ($data['type'] === 'link') {
            $payload['url'] = $data['url'];
        } else {
            $file = $request->file('file');
            $path = $file->store('lms/materials/' . $course->id, 'public');
            $payload['file_path'] = $path;
            $payload['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        CourseMaterial::create($payload);

        return back()->with('success', 'Material added!');
    }

    /** Teacher-only: remove a material (deletes the stored file, if any). */
    public function destroy(Course $course, CourseMaterial $material)
    {
        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }
        $material->delete();

        return back()->with('success', 'Material removed!');
    }
}
