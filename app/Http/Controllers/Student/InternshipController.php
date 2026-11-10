<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Services\NotifierService;
use Illuminate\Http\Request;

class InternshipController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student;

        if (! $student) {
            return view('student.internships.index', [
                'internships'    => collect(),
                'myApplications' => collect(),
                'student'        => null,
            ]);
        }

        $internships = Internship::where('is_active', true)
            ->get()
            ->filter(fn (Internship $i) => $i->isVisibleTo($student))
            ->values();

        $myApplications = InternshipApplication::with(['internship', 'voucher'])
            ->where('student_id', $student->id)
            ->latest()
            ->get()
            ->keyBy('internship_id');

        return view('student.internships.index', compact('internships', 'myApplications', 'student'));
    }

    public function apply(Request $request, Internship $internship)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403, 'No student profile linked to this account.');
        abort_unless($internship->isVisibleTo($student), 403, 'This internship is not open to you.');

        if (InternshipApplication::where('internship_id', $internship->id)->where('student_id', $student->id)->exists()) {
            return back()->with('error', 'You have already applied for this internship.');
        }

        $validated = $request->validate([
            'roll_number' => 'required|string|max:50',
        ]);

        $application = InternshipApplication::create([
            'internship_id' => $internship->id,
            'student_id'    => $student->id,
            'roll_number'   => $validated['roll_number'],
            'status'        => 'pending',
            'fee_status'    => 'not_applicable',
        ]);

        NotifierService::notifyAdmins(
            "{$student->name} applied for the internship \"{$internship->title}\".",
            'fa-file-signature',
            'info'
        );

        AuditLog::record('created', 'InternshipApplication', $application->id, "{$student->name} applied for internship \"{$internship->title}\"");

        return back()->with('success', 'Application submitted! You will be notified once admin reviews it.');
    }
}
