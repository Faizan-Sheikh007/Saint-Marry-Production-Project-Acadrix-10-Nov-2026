<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Admin management of Academic Years (e.g. "2025-2026"). Replaces the old
 * free-text "Session" input on the admission form.
 */
class AcademicYearController extends Controller
{
    public function index()
    {
        $items = AcademicYear::withCount(['students', 'schoolClasses'])
            ->with(['schoolClasses:id,program_id'])
            ->ordered()
            ->paginate(20);
        return view('academic-years.index', compact('items'));
    }

    public function create()
    {
        $programs = $this->programsWithSections();
        return view('academic-years.form', compact('programs'));
    }

    protected function rules($id = null): array
    {
        return [
            'name'        => 'required|string|max:20|unique:academic_years,name' . ($id ? ",{$id}" : ''),
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'sometimes|boolean',
            'is_default'  => 'sometimes|boolean',
            'school_class_ids'   => 'nullable|array',
            'school_class_ids.*' => 'integer|exists:school_classes,id',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $schoolClassIds = $validated['school_class_ids'] ?? [];
        unset($validated['school_class_ids']);
        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($validated['is_default']) {
            AcademicYear::query()->update(['is_default' => false]);
        }

        $year = AcademicYear::create($validated);
        $year->schoolClasses()->sync($schoolClassIds);
        AuditLog::record('created', 'AcademicYear', $year->id, "Added academic year {$year->name}");

        return redirect()->route('academic-years.index')->with('success', 'Academic session added and linked to its Programs / Semesters.');
    }

    public function edit(AcademicYear $academicYear)
    {
        $programs = $this->programsWithSections();
        $academicYear->load('schoolClasses:id');
        $selectedSchoolClassIds = $academicYear->schoolClasses->pluck('id')->map(fn ($id) => (int) $id)->all();

        return view('academic-years.form', compact('academicYear', 'programs', 'selectedSchoolClassIds'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate($this->rules($academicYear->id));
        $schoolClassIds = $validated['school_class_ids'] ?? [];
        unset($validated['school_class_ids']);
        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($validated['is_default']) {
            AcademicYear::query()->where('id', '!=', $academicYear->id)->update(['is_default' => false]);
        }

        // Do not allow Admin to unlink a Part/Semester while students in this
        // same session are still admitted into it. That would leave the
        // student's Session -> Program -> Semester chain inconsistent.
        $selectedIds = collect($schoolClassIds)->map(fn ($id) => (int) $id)->unique();
        $occupiedUnlinked = Student::query()
            ->where('academic_year_id', $academicYear->id)
            ->when($selectedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('school_class_id', $selectedIds))
            ->when($selectedIds->isEmpty(), fn ($q) => $q)
            ->with(['program:id,name', 'schoolClass:id,name,section'])
            ->get();

        if ($occupiedUnlinked->isNotEmpty()) {
            $examples = $occupiedUnlinked->take(3)->map(function ($student) {
                $program = $student->program?->name ?? 'Unknown Program';
                $term = $student->schoolClass?->section_label ?? 'Unknown Semester';
                return "{$program} / {$term}";
            })->unique()->implode(', ');

            throw ValidationException::withMessages([
                'school_class_ids' => "Cannot unlink a Program/Semester that already has students in {$academicYear->name}. Still in use: {$examples}.",
            ]);
        }

        $academicYear->update($validated);
        $academicYear->schoolClasses()->sync($selectedIds->all());
        AuditLog::record('updated', 'AcademicYear', $academicYear->id, "Updated academic year {$academicYear->name}");

        return redirect()->route('academic-years.index')->with('success', 'Academic session and Program / Semester links updated!');
    }

    /**
     * Admission-form endpoint. Only Programs and Parts/Semesters linked to
     * the selected Academic Session are returned.
     */
    public function programs(Request $request, AcademicYear $academicYear)
    {
        $query = Program::query()
            ->with('institution')
            ->where('is_active', true)
            ->forAcademicYear($academicYear->id)
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', $request->institution_id))
            ->orderBy('name');

        $programs = $query->get()->map(function (Program $program) use ($academicYear) {
            $sections = $program->sections()
                ->whereHas('academicYears', fn ($q) => $q->where('academic_years.id', $academicYear->id))
                ->get()
                ->sortBy(fn ($section) => [$section->term_number ?? 999, $section->section, $section->name])
                ->values();

            return [
                'id'             => $program->id,
                'name'           => $program->name,
                'institution_id' => $program->institution_id,
                'institution'    => $program->institution?->name,
                'disclaimer'     => $program->effective_disclaimer,
                'sections'       => $sections->map(fn ($section) => [
                    'id'    => $section->id,
                    'label' => $section->section_label,
                ])->all(),
            ];
        });

        return response()->json($programs);
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->students()->exists()) {
            return redirect()->route('academic-years.index')
                ->with('error', 'Cannot delete an academic year that still has students admitted under it.');
        }

        $name = $academicYear->name;
        $academicYear->delete();
        AuditLog::record('deleted', 'AcademicYear', null, "Deleted academic year {$name}");

        return redirect()->route('academic-years.index')->with('success', 'Academic year deleted!');
    }

    private function programsWithSections()
    {
        return Program::with(['sections' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->each(function ($program) {
                $program->setRelation('sections', $program->sections
                    ->sortBy(fn ($section) => [$section->term_number ?? 999, $section->section, $section->name])
                    ->values());
            });
    }
}
