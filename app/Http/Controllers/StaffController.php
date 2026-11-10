<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('employee_id', 'like', "%{$request->search}%")
                  ->orWhere('designation', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->department) {
            $query->where('department', $request->department);
        }

        $items = $query->latest()->paginate(20)->withQueryString();

        $departments = Staff::whereNotNull('department')->distinct()->pluck('department');

        return view('staff.index', compact('items', 'departments'));
    }

    public function create()
    {
        return view('staff.form');
    }

    public function store(Request $request)
    {
        $validated = $this->validateStaff($request);

        $validated['employee_id'] = 'STF-' . str_pad((int) (Staff::max('id')) + 1, 4, '0', STR_PAD_LEFT);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        $staff = Staff::create($validated);

        AuditLog::record('created', 'Staff', $staff->id, "Added staff member \"{$staff->name}\" ({$staff->designation})");

        return redirect()->route('admin.staff.index')
            ->with('success', "Staff member \"{$staff->name}\" was added successfully.");
    }

    public function show(Staff $staff)
    {
        return view('staff.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        return view('staff.form', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $validated = $this->validateStaff($request, $staff);

        if ($request->hasFile('photo')) {
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $validated['photo'] = $request->file('photo')->store('staff/photos', 'public');
        }

        $staff->update($validated);

        AuditLog::record('updated', 'Staff', $staff->id, "Updated staff member \"{$staff->name}\"");

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated!');
    }

    public function destroy(Staff $staff)
    {
        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }

        $name = $staff->name;
        $staff->delete();

        AuditLog::record('deleted', 'Staff', null, "Deleted staff member \"{$name}\"");

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted.');
    }

    protected function validateStaff(Request $request, ?Staff $staff = null): array
    {
        return $request->validate([
            'name'                => 'required|string|max:150',
            'father_name'         => 'nullable|string|max:150',
            'email'               => ['required', 'email', Rule::unique('staff', 'email')->ignore($staff?->id)],
            'phone'               => 'nullable|string|max:30',
            'emergency_contact'   => 'nullable|string|max:30',
            'gender'              => 'nullable|in:male,female,other',
            'date_of_birth'       => 'nullable|date',
            'blood_group'         => 'nullable|string|max:5',
            'marital_status'      => 'nullable|string|max:20',
            'address'             => 'nullable|string',
            'city'                => 'nullable|string|max:100',
            'cnic'                => 'nullable|string|max:20',
            'qualification'       => 'nullable|string|max:150',
            'experience'          => 'nullable|integer|min:0|max:60',
            'designation'         => 'required|string|max:100',
            'department'          => 'nullable|string|max:100',
            'employment_type'     => 'required|in:full_time,part_time,contract,daily_wage',
            'salary'              => 'required|numeric|min:0',
            'bank_name'           => 'nullable|string|max:100',
            'bank_account_title'  => 'nullable|string|max:150',
            'bank_account_number' => 'nullable|string|max:50',
            'joining_date'        => 'nullable|date',
            'photo'               => 'nullable|image|max:2048',
            'status'              => 'required|in:active,inactive,terminated',
        ]);
    }
}
