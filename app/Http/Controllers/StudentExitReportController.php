<?php
namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
class StudentExitReportController extends Controller {
    public function index(Request $request) {
        $data = $request->validate([
            'status'=>'nullable|in:dropped,left', 'from'=>'nullable|date',
            'to'=>'nullable|date|after_or_equal:from', 'search'=>'nullable|string|max:100',
            'program_id'=>'nullable|integer|exists:programs,id',
        ]);
        $query = Student::with(['program','schoolClass','academicYear'])
            ->whereIn('status', ['dropped','left']);
        if (!empty($data['status'])) $query->where('status', $data['status']);
        if (!empty($data['from'])) $query->whereDate('updated_at','>=',$data['from']);
        if (!empty($data['to'])) $query->whereDate('updated_at','<=',$data['to']);
        if (!empty($data['program_id'])) $query->where('program_id',$data['program_id']);
        if (!empty($data['search'])) {
            $search=$data['search'];
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('student_id','like',"%{$search}%"));
        }
        if ($request->query('export') === 'csv') {
            $rows = $query->orderByDesc('updated_at')->get();
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Student ID', 'Name', 'Program', 'Semester', 'Session', 'Student Status', 'Last Updated']);
                foreach ($rows as $student) {
                    fputcsv($out, [$student->student_id, $student->name, $student->program?->name,
                        $student->schoolClass?->name, $student->session, $student->statusLabel(),
                        $student->updated_at?->format('Y-m-d')]);
                }
                fclose($out);
            }, 'drop-left-students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $students=$query->orderByDesc('updated_at')->paginate(40)->withQueryString();
        $programs=\App\Models\Program::orderBy('name')->get();
        return view('students.exit-report',compact('students','programs'));
    }
}
