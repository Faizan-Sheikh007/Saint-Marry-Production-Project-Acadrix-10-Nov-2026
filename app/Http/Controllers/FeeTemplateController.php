<?php
namespace App\Http\Controllers;
use App\Models\FeeTemplate;
use App\Models\FeeHead;
use App\Models\SchoolClass;
use App\Models\Program;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeTemplateController extends Controller
{
    public function index()
    {
        $items = FeeTemplate::with('schoolClass', 'items')->latest()->paginate(20);
        return view('fee-templates.index', compact('items'));
    }

    public function create()
    {
        $classes  = SchoolClass::orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();
        return view('fee-templates.form', compact('classes', 'programs', 'feeHeads'));
    }

    protected function baseRules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'school_class_id'      => 'nullable|exists:school_classes,id',
            'billing_cycle'        => 'required|in:one_time,monthly,semester,annual',
            'parts_per_cycle'      => 'nullable|integer|min:1|max:12',
            'total_cycles'         => 'nullable|integer|min:1|max:20',
            'is_active'            => 'nullable|boolean',
            'description'          => 'nullable|string',
            'fee_head_id'          => 'required|array|min:1',
            'fee_head_id.*'        => 'exists:fee_heads,id',
            'amount'               => 'nullable|array',
            // Amount columns are DECIMAL(12,2). Eloquent returns existing whole-rupee
            // values as strings such as "65000.00", so integer validation wrongly
            // rejects an unchanged template on edit. Numeric accepts both 65000 and
            // 65000.00 without changing any stored records.
            'amount.*'             => 'nullable|numeric|min:0|max:9999999999.99',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->baseRules());
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($validated) {
            $template = FeeTemplate::create([
                'name'            => $validated['name'],
                'school_class_id' => $validated['school_class_id'] ?? null,
                'billing_cycle'   => $validated['billing_cycle'],
                'parts_per_cycle' => $validated['parts_per_cycle'] ?? 1,
                'total_cycles'    => $validated['total_cycles'] ?? null,
                'is_active'       => $validated['is_active'],
                'description'     => $validated['description'] ?? null,
            ]);

            foreach ($validated['fee_head_id'] as $i => $headId) {
                $amount = $validated['amount'][$i] ?? null;
                $template->items()->create([
                    'fee_head_id' => $headId,
                    'amount'      => $amount === '' ? null : $amount,
                ]);
            }

            AuditLog::record('created', 'FeeTemplate', $template->id, "Created fee template {$template->name}");
        });

        return redirect()->route('fee-templates.index')->with('success', 'Fee template created!');
    }

    public function show(FeeTemplate $feeTemplate)
    {
        $feeTemplate->load('items.feeHead', 'schoolClass');
        return view('fee-templates.show', compact('feeTemplate'));
    }

    public function edit(FeeTemplate $feeTemplate)
    {
        $feeTemplate->load('items', 'schoolClass');
        $classes  = SchoolClass::orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();
        return view('fee-templates.form', compact('feeTemplate', 'classes', 'programs', 'feeHeads'));
    }

    public function update(Request $request, FeeTemplate $feeTemplate)
    {
        $validated = $request->validate($this->baseRules());
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($validated, $feeTemplate) {
            $feeTemplate->update([
                'name'            => $validated['name'],
                'school_class_id' => $validated['school_class_id'] ?? null,
                'billing_cycle'   => $validated['billing_cycle'],
                'parts_per_cycle' => $validated['parts_per_cycle'] ?? 1,
                'total_cycles'    => $validated['total_cycles'] ?? null,
                'is_active'       => $validated['is_active'],
                'description'     => $validated['description'] ?? null,
            ]);

            $feeTemplate->items()->delete();
            foreach ($validated['fee_head_id'] as $i => $headId) {
                $amount = $validated['amount'][$i] ?? null;
                $feeTemplate->items()->create([
                    'fee_head_id' => $headId,
                    'amount'      => $amount === '' ? null : $amount,
                ]);
            }

            AuditLog::record('updated', 'FeeTemplate', $feeTemplate->id, "Updated fee template {$feeTemplate->name}");
        });

        return redirect()->route('fee-templates.index')->with('success', 'Fee template updated!');
    }

    public function destroy(FeeTemplate $feeTemplate)
    {
        $name = $feeTemplate->name;
        $feeTemplate->delete();
        AuditLog::record('deleted', 'FeeTemplate', null, "Deleted fee template {$name}");
        return redirect()->route('fee-templates.index')->with('success', 'Fee template deleted!');
    }
}