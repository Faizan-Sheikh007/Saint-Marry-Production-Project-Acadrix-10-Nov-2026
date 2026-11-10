<?php
namespace App\Http\Controllers;
use App\Models\SchoolClass;
use App\Models\Program;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        // Programs (grouped) drive the main view — every Part/Semester is
        // shown nested under the Program it belongs to, rather than as one
        // long flat table. When a specific program is selected in the
        // filter, only that program group is returned (still nested).
        $groupQuery = Program::with('institution')->orderBy('name');
        if ($request->program) {
            $groupQuery->where('id', $request->program);
        } else {
            $groupQuery->whereHas('sections');
        }

        $groupedPrograms = $groupQuery->get()->map(function ($program) {
            $sections = $program->sections()->withCount('students')->get()
                ->sortBy(function ($section) {
                    // Natural sort ("Semester 2" before "Semester 10") using
                    // the first number found in the name, section coming next.
                    preg_match('/(\d+)/', $section->name, $m);
                    return [isset($m[1]) ? (int) $m[1] : 0, $section->section, $section->name];
                })->values();

            $program->setRelation('sections', $sections);
            $program->seats_total    = (int) $sections->sum('capacity');
            $program->students_total = (int) $sections->sum('students_count');

            return $program;
        })->filter(fn ($program) => $program->sections->isNotEmpty() || $request->program);

        // Plain list, used only to populate the filter dropdown.
        $programs = Program::orderBy('name')->get();

        $stats = [
            'total'    => SchoolClass::count(),
            'capacity' => (int) SchoolClass::sum('capacity'),
            'students' => (int) SchoolClass::withCount('students')->get()->sum('students_count'),
            'programs' => Program::whereHas('sections')->count(),
        ];

        return view('classes.index', compact('groupedPrograms', 'programs', 'stats'));
    }

    public function create(Request $request)
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $selectedProgramId = $request->program_id;
        return view('classes.form', compact('programs', 'selectedProgramId'));
    }

    protected function rules(): array
    {
        return [
            'program_id'  => 'required|exists:programs,id',
            'name'        => 'required|string|max:255',
            'section'     => 'nullable|string|max:10',
            'level'       => 'required|in:college,university',
            'capacity'    => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $schoolClass = SchoolClass::create($validated);
        AuditLog::record('created', 'SchoolClass', $schoolClass->id, "Created section/semester {$schoolClass->name} under {$schoolClass->program->name}");
        return redirect()->route('classes.index')->with('success', 'Section / Semester created!');
    }

    public function show(SchoolClass $schoolClass)
    {
        $schoolClass->load(['feeTemplates', 'program', 'students']);
        return view('classes.show', compact('schoolClass'));
    }

    public function edit(SchoolClass $schoolClass)
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('classes.form', compact('schoolClass', 'programs'));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $validated = $request->validate($this->rules());
        $schoolClass->update($validated);
        AuditLog::record('updated', 'SchoolClass', $schoolClass->id, "Updated section/semester {$schoolClass->name}");
        return redirect()->route('classes.index')->with('success', 'Section / Semester updated!');
    }

    public function destroy(SchoolClass $schoolClass)
    {
        if ($schoolClass->students()->exists()) {
            return redirect()->route('classes.index')
                ->with('error', 'Cannot delete a section that still has students enrolled in it.');
        }

        $name = $schoolClass->name;
        $schoolClass->delete();
        AuditLog::record('deleted', 'SchoolClass', null, "Deleted section/semester {$name}");
        return redirect()->route('classes.index')->with('success', 'Section / Semester deleted!');
    }
}
