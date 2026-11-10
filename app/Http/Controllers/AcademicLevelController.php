<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Admin management of Academic Levels (e.g. "College", "University").
 * Replaces the old hardcoded College/University radio on the admission
 * form — admin can rename, reorder, deactivate, or add new levels here.
 */
class AcademicLevelController extends Controller
{
    public function index()
    {
        $items = AcademicLevel::withCount('students')->ordered()->paginate(20);
        return view('academic-levels.index', compact('items'));
    }

    public function create()
    {
        return view('academic-levels.form');
    }

    protected function rules($id = null): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'requires_institution'  => 'sometimes|boolean',
            'sort_order'            => 'nullable|integer|min:0',
            'is_active'             => 'sometimes|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active']            = $request->boolean('is_active', true);
        $validated['requires_institution'] = $request->boolean('requires_institution');
        $validated['sort_order']           = $validated['sort_order'] ?? 0;
        $validated['slug']                 = $this->uniqueSlug($validated['name']);

        $level = AcademicLevel::create($validated);
        AuditLog::record('created', 'AcademicLevel', $level->id, "Added academic level {$level->name}");

        return redirect()->route('academic-levels.index')->with('success', 'Academic level added!');
    }

    public function edit(AcademicLevel $academicLevel)
    {
        return view('academic-levels.form', ['academicLevel' => $academicLevel]);
    }

    public function update(Request $request, AcademicLevel $academicLevel)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active']            = $request->boolean('is_active', true);
        $validated['requires_institution'] = $request->boolean('requires_institution');
        $validated['sort_order']           = $validated['sort_order'] ?? 0;

        $academicLevel->update($validated);
        AuditLog::record('updated', 'AcademicLevel', $academicLevel->id, "Updated academic level {$academicLevel->name}");

        return redirect()->route('academic-levels.index')->with('success', 'Academic level updated!');
    }

    public function destroy(AcademicLevel $academicLevel)
    {
        if ($academicLevel->students()->exists()) {
            return redirect()->route('academic-levels.index')
                ->with('error', 'Cannot delete an academic level that still has students admitted under it.');
        }

        $name = $academicLevel->name;
        $academicLevel->delete();
        AuditLog::record('deleted', 'AcademicLevel', null, "Deleted academic level {$name}");

        return redirect()->route('academic-levels.index')->with('success', 'Academic level deleted!');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;
        while (AcademicLevel::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
