<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\Voucher;
use App\Services\NotifierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InternshipController extends Controller
{
    public function index()
    {
        $internships = Internship::withCount('applications')
            ->with('applications')
            ->latest()
            ->get();

        return view('admin.internships.index', compact('internships'));
    }

    public function create()
    {
        $programs = Program::orderBy('name')->get();
        $students = Student::orderBy('name')->get(['id', 'name', 'student_id', 'program_id']);

        return view('admin.internships.form', [
            'internship' => new Internship(),
            'programs'   => $programs,
            'students'   => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('internships', 'public');
        }

        $validated['created_by'] = auth()->id();

        $internship = Internship::create($validated);

        AuditLog::record('created', 'Internship', $internship->id, "Created internship posting \"{$internship->title}\"");

        return redirect()->route('admin.internships.index')->with('success', 'Internship posting created!');
    }

    public function edit(Internship $internship)
    {
        $programs = Program::orderBy('name')->get();
        $students = Student::orderBy('name')->get(['id', 'name', 'student_id', 'program_id']);

        return view('admin.internships.form', compact('internship', 'programs', 'students'));
    }

    public function update(Request $request, Internship $internship)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($internship->image_path) {
                Storage::disk('public')->delete($internship->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('internships', 'public');
        }

        $internship->update($validated);

        AuditLog::record('updated', 'Internship', $internship->id, "Updated internship posting \"{$internship->title}\"");

        return redirect()->route('admin.internships.index')->with('success', 'Internship posting updated!');
    }

    public function destroy(Internship $internship)
    {
        $title = $internship->title;

        if ($internship->image_path) {
            Storage::disk('public')->delete($internship->image_path);
        }

        $internship->delete();

        AuditLog::record('deleted', 'Internship', null, "Deleted internship posting \"{$title}\"");

        return redirect()->route('admin.internships.index')->with('success', 'Internship posting deleted.');
    }

    /** Full applicant roster for one posting — this is admin's single source of truth for applied students, fee status, etc. */
    public function show(Internship $internship)
    {
        $applications = $internship->applications()
            ->with(['student', 'voucher'])
            ->latest()
            ->get();

        return view('admin.internships.show', compact('internship', 'applications'));
    }

    /**
     * Admin approves a student's application. This immediately generates
     * the challan (a Voucher, exactly like every other fee in the system)
     * and hands it to Accounts to collect — admin never touches payment
     * collection itself, only approval.
     */
    public function approve(Request $request, InternshipApplication $application)
    {
        abort_unless($application->status === 'pending', 400, 'This application has already been reviewed.');

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'due_date'    => 'nullable|date',
        ]);

        $student     = $application->student;
        $internship  = $application->internship;

        $voucher = Voucher::create([
            'voucher_no' => Voucher::generateVoucherNo(),
            'student_id' => $student->id,
            'title'      => 'Internship Fee — ' . $internship->title,
            'amount'     => $internship->fee_amount,
            'due_date'   => $validated['due_date'] ?? ($internship->due_date ?? now()->addDays(7)),
            'issued_at'  => now(),
            'status'     => 'unpaid',
            'notes'      => 'Auto-generated on approval of internship application #' . $application->id,
        ]);

        $application->update([
            'status'      => 'admin_approved',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'voucher_id'  => $voucher->id,
            'fee_status'  => 'unpaid',
        ]);

        if ($student->user_id) {
            NotifierService::notify(
                $student->user_id,
                "Your application for \"{$internship->title}\" was approved! A challan of Rs." . number_format($internship->fee_amount, 2) . ' has been issued — please pay to confirm your seat.',
                'fa-circle-check',
                'success'
            );
        }

        AuditLog::record('approved', 'InternshipApplication', $application->id, "Approved {$student->name}'s application for \"{$internship->title}\" and issued voucher {$voucher->voucher_no}");

        return back()->with('success', "Approved! Challan {$voucher->voucher_no} generated and sent to Accounts.");
    }

    public function reject(Request $request, InternshipApplication $application)
    {
        abort_unless($application->status === 'pending', 400, 'This application has already been reviewed.');

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status'      => 'admin_rejected',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $student    = $application->student;
        $internship = $application->internship;

        if ($student->user_id) {
            NotifierService::notify(
                $student->user_id,
                "Your application for \"{$internship->title}\" was not approved." . ($validated['admin_notes'] ?? '' ? ' Reason: ' . $validated['admin_notes'] : ''),
                'fa-circle-xmark',
                'danger'
            );
        }

        AuditLog::record('rejected', 'InternshipApplication', $application->id, "Rejected {$student->name}'s application for \"{$internship->title}\"");

        return back()->with('success', 'Application rejected.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'image'                => 'nullable|image|max:4096',
            'fee_amount'           => 'required|numeric|min:0',
            'due_date'             => 'nullable|date',
            'target_type'          => 'required|in:all,programs,students',
            'target_program_ids'   => 'nullable|array',
            'target_program_ids.*' => 'exists:programs,id',
            'target_student_ids'   => 'nullable|array',
            'target_student_ids.*' => 'exists:students,id',
            'is_active'            => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
