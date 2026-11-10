<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TeacherController extends Controller
{
    use \App\Traits\PlanLimitTrait;
    public function index(Request $request)
    {
        $query = Teacher::with('subject');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',         'like', "%{$request->search}%")
                  ->orWhere('employee_id', 'like', "%{$request->search}%")
                  ->orWhere('email',       'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $items = $query->latest()->paginate(20);

        return view('teachers.index', compact('items'));
    }

    public function create()
    {
        $classes  = SchoolClass::all();
        $subjects = Subject::all();
        return view('teachers.form', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTeacher($request);

        // Generate employee ID based on next available ID
        $validated['employee_id'] = 'EMP-' . str_pad(Teacher::max('id') + 1, 4, '0', STR_PAD_LEFT);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers/photos', 'public');
        }

        if ($signaturePath = $this->storeSignature($request)) {
            $validated['signature'] = $signaturePath;
        }

        // Auto-generate a password for the teacher's login account
        $password = $this->generatePassword();

        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name'     => $validated['name'],
                'password' => Hash::make($password),
                'role'     => 'teacher',
            ]
        );

        $validated['user_id']           = $user->id;
        $validated['login_password']    = $password; // encrypted via model cast
        $validated['password_reset_at'] = now();

        $teacher = Teacher::create($validated);

        return redirect()->route('admin.teachers.index')
            ->with('success', "Teacher \"{$teacher->name}\" was added successfully.")
            ->with('credentials', [
                'heading'     => 'Teacher account created',
                'employee_id' => $teacher->employee_id,
                'email'       => $validated['email'],
                'password'    => $password,
                'name'        => $validated['name'],
                'profile_url' => route('admin.teachers.show', $teacher),
                'card_url'    => route('admin.teachers.idcard', $teacher),
            ]);
    }

    /**
     * Shared validation rules for store() and update().
     */
    protected function validateTeacher(Request $request, ?Teacher $teacher = null): array
    {
        $emailRule = $teacher
            ? 'required|email|unique:teachers,email,' . $teacher->id
            : 'required|email|unique:teachers';

        return $request->validate([
            'name'                 => 'required|string|max:255',
            'father_name'          => 'nullable|string|max:255',
            'email'                => $emailRule,
            'phone'                => 'nullable|string|max:20',
            'emergency_contact'    => 'nullable|string|max:20',
            'gender'               => 'required|in:male,female,other',
            'date_of_birth'        => 'nullable|date',
            'blood_group'          => 'nullable|string|max:5',
            'marital_status'       => 'nullable|in:single,married,other',
            'qualification'        => 'nullable|string|max:255',
            'experience'           => 'nullable|integer',
            'salary'               => 'nullable|numeric',
            'bank_name'            => 'nullable|string|max:255',
            'bank_account_title'   => 'nullable|string|max:255',
            'bank_account_number'  => 'nullable|string|max:50',
            'subject_id'           => 'nullable|exists:subjects,id',
            'designation'          => 'nullable|string|max:255',
            'department'           => 'nullable|string|max:255',
            'employment_type'      => 'nullable|in:permanent,contract,visiting,probation',
            'joining_date'         => 'nullable|date',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:255',
            'cnic'                 => 'nullable|string|max:20',
            'status'               => 'required|in:active,inactive',
            'photo'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'signature'            => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'signature_data'       => 'nullable|string',
            'remove_signature'     => 'nullable|boolean',
        ]);
    }

    /**
     * Persist a teacher's signature, either drawn on the on-screen pad
     * (submitted as a base64 PNG data URL in `signature_data`) or uploaded
     * as a regular image file (`signature`). Returns the stored path, or
     * null if nothing new was submitted.
     */
    protected function storeSignature(Request $request): ?string
    {
        if ($request->hasFile('signature')) {
            return $request->file('signature')->store('teachers/signatures', 'public');
        }

        if ($request->filled('signature_data')) {
            $data = $request->input('signature_data');
            if (preg_match('/^data:image\/(png|jpe?g);base64,/', $data, $m)) {
                $ext     = $m[1] === 'jpg' ? 'jpeg' : $m[1];
                $decoded = base64_decode(substr($data, strpos($data, ',') + 1));
                if ($decoded !== false) {
                    $path = 'teachers/signatures/' . uniqid('sig_') . '.' . $ext;
                    Storage::disk('public')->put($path, $decoded);
                    return $path;
                }
            }
        }

        return null;
    }

    /** Generates a readable-but-strong random password for a new staff login. */
    protected function generatePassword(): string
    {
        $words = ['Panda','Falcon','Cedar','Nova','Comet','River','Ember','Orbit','Pixel','Maple'];
        return $words[array_rand($words)] . rand(100, 999) . '@' . rand(10, 99);
    }

    public function show(Teacher $teacher)
    {
        $timetable = Timetable::with('subject', 'schoolClass.program')
            ->where('teacher_id', $teacher->id)
            ->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
            ->orderBy('start_time')
            ->get();

        $unreadFromTeacher = \App\Models\TeacherMessage::where('teacher_id', $teacher->id)
            ->where('is_read', false)
            ->whereHas('sender', fn ($q) => $q->where('role', 'teacher'))
            ->count();

        $pendingActivities =
            \App\Models\Tour::where('teacher_id', $teacher->id)->where('status', 'assigned')->count()
            + \App\Models\Project::where('teacher_id', $teacher->id)->where('status', 'assigned')->count()
            + \App\Models\Practical::where('teacher_id', $teacher->id)->whereNull('paragraph')->count()
            + \App\Models\Survey::where('teacher_id', $teacher->id)->where('status', 'assigned')->count()
            + \App\Models\Assignment::adminRequests()->where('teacher_id', $teacher->id)->where('request_status', 'pending')->count()
            + \App\Models\Quiz::adminRequests()->where('teacher_id', $teacher->id)->where('request_status', 'pending')->count();

        return view('teachers.show', compact('teacher', 'timetable', 'unreadFromTeacher', 'pendingActivities'));
    }

    public function edit(Teacher $teacher)
    {
        $classes  = SchoolClass::all();
        $subjects = Subject::all();
        return view('teachers.form', compact('teacher', 'classes', 'subjects'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $this->validateTeacher($request, $teacher);

        if ($request->hasFile('photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $validated['photo'] = $request->file('photo')->store('teachers/photos', 'public');
        }

        if ($request->boolean('remove_signature')) {
            if ($teacher->signature) {
                Storage::disk('public')->delete($teacher->signature);
            }
            $validated['signature'] = null;
        } elseif ($signaturePath = $this->storeSignature($request)) {
            if ($teacher->signature) {
                Storage::disk('public')->delete($teacher->signature);
            }
            $validated['signature'] = $signaturePath;
        }

        $teacher->update($validated);

        // Keep the linked user account in sync
        if ($teacher->user_id) {
            User::where('id', $teacher->user_id)->update([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully!');
    }

    /**
     * Regenerate this teacher's login password (admin-triggered from the
     * profile page) and show it once more in the credentials modal.
     */
    public function resetPassword(Teacher $teacher)
    {
        $password = $this->generatePassword();

        $teacher->update([
            'login_password'    => $password,
            'password_reset_at' => now(),
        ]);

        if ($teacher->user_id) {
            User::where('id', $teacher->user_id)->update(['password' => Hash::make($password)]);
        }

        return redirect()->route('admin.teachers.show', $teacher)
            ->with('success', "Password reset for {$teacher->name}.")
            ->with('credentials', [
                'heading'     => 'Password reset',
                'employee_id' => $teacher->employee_id,
                'email'       => $teacher->email,
                'password'    => $password,
                'name'        => $teacher->name,
                'profile_url' => route('admin.teachers.show', $teacher),
                'card_url'    => route('admin.teachers.idcard', $teacher),
            ]);
    }

    /** Printable staff ID card. */
    public function idCard(Teacher $teacher)
    {
        return view('teachers.idcard', compact('teacher'));
    }

    public function destroy(Teacher $teacher)
    {
        if ($teacher->photo) {
            Storage::disk('public')->delete($teacher->photo);
        }

        // Remove the linked user login account
        if ($teacher->user_id) {
            User::where('id', $teacher->user_id)->delete();
        }

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully!');
    }
}
