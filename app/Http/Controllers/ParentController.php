<?php
namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $items = ParentModel::with('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('cnic', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('parents.index', compact('items'));
    }

    public function create()
    {
        $students = Student::where('status', 'active')
                           ->with('schoolClass')
                           ->get();
        $linkedStudentIds = [];
        return view('parents.form', compact('students', 'linkedStudentIds'));
    }
public function store(Request $request)
{
    $validated = $request->validate([
        'name'          => 'required|string|max:255',
        'phone'         => 'required|string|max:20',
        'email'         => 'nullable|email|unique:parents,email',
        'cnic'          => 'nullable|string|max:20',
        'occupation'    => 'nullable|string|max:255',
        'address'       => 'nullable|string',
        'student_ids'   => 'nullable|array',
        'student_ids.*' => 'exists:students,id',
    ]);

    // The LMS/portal login is always randomly generated (e.g.
    // prnt-2121-santmarry@cyberorion.pk / prnt@122@2109), never the
    // parent's own personal email — that stays on the record purely as an
    // optional contact address.
    $loginEmail = \App\Services\CredentialGenerator::parentEmail();
    $password   = \App\Services\CredentialGenerator::parentPassword();

    $user = User::create([
        'name'     => $validated['name'],
        'email'    => $loginEmail,
        'password' => Hash::make($password),
        'role'     => 'parent',
    ]);

    $parent = ParentModel::create([
        'user_id'    => $user->id,
        'name'       => $validated['name'],
        'phone'      => $validated['phone'],
        'email'      => $validated['email'] ?? null,
        'cnic'       => $validated['cnic']        ?? null,
        'occupation' => $validated['occupation']  ?? null,
        'address'    => $validated['address']     ?? null,
    ]);

    if (!empty($validated['student_ids'])) {
        Student::whereIn('id', $validated['student_ids'])
               ->update(['parent_id' => $parent->id]);
    }

    return redirect()->route('admin.parents.index')
                     ->with('success', "Parent added! Portal login — Email: {$loginEmail} | Password: {$password}")
                     ->with('credentials', ['email' => $loginEmail, 'password' => $password, 'name' => $validated['name']]);
}

public function update(Request $request, ParentModel $parent)
{
    $validated = $request->validate([
        'name'          => 'required|string|max:255',
        'phone'         => 'required|string|max:20',
        'email'         => 'nullable|email|unique:parents,email,' . $parent->id,
        'cnic'          => 'nullable|string|max:20',
        'occupation'    => 'nullable|string|max:255',
        'address'       => 'nullable|string',
        'student_ids'   => 'nullable|array',
        'student_ids.*' => 'exists:students,id',
    ]);

    $parent->update([
        'name'       => $validated['name'],
        'phone'      => $validated['phone'],
        'email'      => $validated['email']     ?? null,
        'cnic'       => $validated['cnic']       ?? null,
        'occupation' => $validated['occupation'] ?? null,
        'address'    => $validated['address']    ?? null,
    ]);

    // Keep the User's display name in sync. The login email/password are
    // never changed here — use "Generate Login" / "Reset Password" for that.
    if ($parent->user_id) {
        User::where('id', $parent->user_id)->update(['name' => $validated['name']]);
    } else {
        // Legacy parent record with no portal login yet — issue one now.
        $loginEmail = \App\Services\CredentialGenerator::parentEmail();
        $password   = \App\Services\CredentialGenerator::parentPassword();
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $loginEmail,
            'password' => Hash::make($password),
            'role'     => 'parent',
        ]);
        $parent->update(['user_id' => $user->id]);
    }

    // Re-link students
    Student::where('parent_id', $parent->id)->update(['parent_id' => null]);
    if (!empty($validated['student_ids'])) {
        Student::whereIn('id', $validated['student_ids'])
               ->update(['parent_id' => $parent->id]);
    }

    return redirect()->route('admin.parents.index')
                     ->with('success', 'Parent updated!');
}

/**
 * Regenerate this parent's portal login password (admin-triggered from the
 * profile page) and show it once more in the credentials modal. Passwords
 * are hashed in the database, so an old one can never be "shown" again —
 * the honest option is to issue a brand new one. If the parent has no
 * portal login yet, one is issued now instead of failing.
 */
public function resetPassword(ParentModel $parent)
{
    $password = \App\Services\CredentialGenerator::parentPassword();

    if ($parent->user_id) {
        User::where('id', $parent->user_id)->update(['password' => Hash::make($password)]);
        $email = $parent->user->email;
    } else {
        $loginEmail = \App\Services\CredentialGenerator::parentEmail();
        $user = User::create([
            'name'     => $parent->name,
            'email'    => $loginEmail,
            'password' => Hash::make($password),
            'role'     => 'parent',
        ]);
        $parent->update(['user_id' => $user->id]);
        $email = $loginEmail;
    }

    return redirect()->route('admin.parents.show', $parent)
        ->with('success', "Password reset for {$parent->name}.")
        ->with('credentials', [
            'heading'  => 'Password reset',
            'email'    => $email,
            'password' => $password,
            'name'     => $parent->name,
        ]);
}

    public function show(ParentModel $parent)
    {
        $parent->load('students.schoolClass');
        return view('parents.show', compact('parent'));
    }

    public function edit(ParentModel $parent)
    {
        $students         = Student::where('status', 'active')->with('schoolClass')->get();
        $linkedStudentIds = $parent->students->pluck('id')->toArray();
        return view('parents.form', compact('parent', 'students', 'linkedStudentIds'));
    }

    public function destroy(ParentModel $parent)
    {
        Student::where('parent_id', $parent->id)->update(['parent_id' => null]);
        if ($parent->user_id) {
            User::where('id', $parent->user_id)->delete();
        }
        $parent->delete();
        return redirect()->route('admin.parents.index')
                         ->with('success', 'Parent deleted.');
    }
}
