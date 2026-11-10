<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $items = Program::withCount(['sections', 'students'])->orderBy('name')->paginate(20);

        $stats = [
            'total'      => Program::count(),
            'active'     => Program::where('is_active', true)->count(),
            'sections'   => \App\Models\SchoolClass::count(),
            'students'   => \App\Models\Student::count(),
            'university' => Program::where('level', 'university')->count(),
        ];

        return view('programs.index', compact('items', 'stats'));
    }

    public function create()
    {
        $institutions = Institution::where('is_active', true)->orderBy('name')->get();
        return view('programs.form', compact('institutions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'nullable|string|max:50|unique:programs,code',
            'level'                  => 'required|in:college,university',
            'system_type'            => 'required|in:annual,semester',
            'duration_terms'         => 'nullable|integer|min:1',
            'institution_id'         => 'nullable|required_if:level,university|exists:institutions,id',
            'department_disclaimer'  => 'nullable|string',
            'description'            => 'nullable|string',
            'is_active'              => 'sometimes|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        if ($validated['level'] !== 'university') {
            $validated['institution_id'] = null;
        }
        $this->applyDurationRule($validated);

        $program = Program::create($validated);
        AuditLog::record('created', 'Program', $program->id, "Created program/class {$program->name}");

        $created = $this->syncPartsSemesters($program);
        $message = $created > 0
            ? "Program / Class created! {$created} Part/Semester record(s) were generated automatically."
            : 'Program / Class created!';

        return redirect()->route('programs.index')->with('success', $message);
    }

    public function show(Program $program)
    {
        $program->load(['sections' => fn ($q) => $q->withCount('students')]);
        return view('programs.show', compact('program'));
    }

    public function edit(Program $program)
    {
        $institutions = Institution::where('is_active', true)->orderBy('name')->get();
        return view('programs.form', compact('program', 'institutions'));
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'nullable|string|max:50|unique:programs,code,' . $program->id,
            'level'                  => 'required|in:college,university',
            'system_type'            => 'required|in:annual,semester',
            'duration_terms'         => 'nullable|integer|min:1',
            'institution_id'         => 'nullable|required_if:level,university|exists:institutions,id',
            'department_disclaimer'  => 'nullable|string',
            'description'            => 'nullable|string',
            'is_active'              => 'sometimes|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        if ($validated['level'] !== 'university') {
            $validated['institution_id'] = null;
        }
        $this->applyDurationRule($validated);

        $program->update($validated);
        AuditLog::record('updated', 'Program', $program->id, "Updated program/class {$program->name}");

        $created = $this->syncPartsSemesters($program);
        $message = $created > 0
            ? "Program / Class updated! {$created} new Part/Semester record(s) were generated automatically."
            : 'Program / Class updated!';

        return redirect()->route('programs.index')->with('success', $message);
    }

    public function destroy(Program $program)
    {
        if ($program->sections()->exists()) {
            return redirect()->route('programs.index')
                ->with('error', 'Cannot delete a program that still has Sections/Semesters. Remove those first.');
        }

        $name = $program->name;
        $program->delete();
        AuditLog::record('deleted', 'Program', null, "Deleted program/class {$name}");

        return redirect()->route('programs.index')->with('success', 'Program / Class deleted!');
    }

    /**
     * Enforce the institution's fixed program structures: Annual programs
     * are always exactly 2 parts (Part 1 / Part 2), and Semester programs
     * must run 8 to 10 semesters — anything typed outside that range is
     * clamped to the nearest bound so a fee template built against this
     * program can never be generated with more/fewer terms than the
     * program actually has.
     */
    protected function applyDurationRule(array &$validated): void
    {
        if ($validated['system_type'] === 'annual') {
            $validated['duration_terms'] = 2; // Part 1 / Part 2, fixed
            return;
        }

        $terms = (int) ($validated['duration_terms'] ?? 8);
        if ($terms < 8) $terms = 8;
        if ($terms > 10) $terms = 10;
        $validated['duration_terms'] = $terms;
    }

    /**
     * JSON list of a Program's Sections/Semesters, used to populate the
     * cascading dropdown on the Student and Section forms.
     */
    public function sections(Program $program)
    {
        return response()->json(
            $program->sections()->orderBy('name')->get(['id', 'name', 'section', 'capacity'])
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'label' => trim($s->name . ($s->section ? " ({$s->section})" : '')),
                ])
        );
    }

    /**
     * Auto-generate the Parts/Semesters (SchoolClass records) implied by a
     * Program's Academic System (Annual vs Semester) and its number of
     * Parts/Semesters (duration_terms), e.g. selecting "Semester" with 8
     * creates "Semester 1".."Semester 8"; selecting "Annual" creates
     * "Part 1" / "Part 2". Only missing labels are created — existing
     * Parts/Semesters (and any students/fee data already tied to them) are
     * left untouched, so this is safe to run again after every edit.
     *
     * @return int number of new Part/Semester records created
     */
    protected function syncPartsSemesters(Program $program): int
    {
        $existingNames = $program->sections()->pluck('name')->all();
        $created = 0;

        foreach ($program->term_labels as $label) {
            if (in_array($label, $existingNames, true)) {
                continue;
            }

            SchoolClass::create([
                'program_id'  => $program->id,
                'name'        => $label,
                'section'     => null,
                'level'       => $program->level,
                'capacity'    => 40,
                'description' => null,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * JSON info for a Program used by the Session-creation form JS: its
     * valid term/session labels (Part 1/Part 2, or Semester 1..N) and its
     * effective disclaimer (institution's, unless overridden per program).
     */
    public function info(Program $program)
    {
        return response()->json([
            'system_type' => $program->system_type,
            'term_labels' => $program->term_labels,
            'institution' => $program->institution?->name,
            'disclaimer'  => $program->effective_disclaimer,
        ]);
    }
}
