<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Admin management of affiliated universities/facilities (e.g. "Punjab
 * Medical Facility", "Government College University Faisalabad"). Each has
 * its own name and disclaimer text; when a Program's level is set to
 * "university" and one of these is selected, its name + disclaimer surface
 * on the admission form and installment plan slip for that department.
 */
class InstitutionController extends Controller
{
    public function index()
    {
        $items = Institution::withCount('programs')->orderBy('name')->paginate(20);
        return view('institutions.index', compact('items'));
    }

    public function create()
    {
        return view('institutions.form');
    }

    protected function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'disclaimer' => 'nullable|string',
            'is_active'  => 'sometimes|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        $institution = Institution::create($validated);
        AuditLog::record('created', 'Institution', $institution->id, "Added institution {$institution->name}");

        return redirect()->route('institutions.index')->with('success', 'Institution added!');
    }

    public function edit(Institution $institution)
    {
        return view('institutions.form', compact('institution'));
    }

    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        $institution->update($validated);
        AuditLog::record('updated', 'Institution', $institution->id, "Updated institution {$institution->name}");

        return redirect()->route('institutions.index')->with('success', 'Institution updated! Programs using its default disclaimer will pick up the change automatically.');
    }

    /**
     * Active Programs affiliated with this Institution, for the admission
     * form's "pick Institution first, then Program" cascade.
     */
    public function programs(Institution $institution)
    {
        $programs = $institution->programs()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($programs);
    }

    public function destroy(Institution $institution)
    {
        if ($institution->programs()->exists()) {
            return redirect()->route('institutions.index')
                ->with('error', 'Cannot delete an institution that is still linked to one or more Programs.');
        }

        $name = $institution->name;
        $institution->delete();
        AuditLog::record('deleted', 'Institution', null, "Deleted institution {$name}");

        return redirect()->route('institutions.index')->with('success', 'Institution deleted!');
    }
}
