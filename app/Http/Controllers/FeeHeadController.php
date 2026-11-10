<?php
namespace App\Http\Controllers;
use App\Models\FeeHead;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class FeeHeadController extends Controller
{
    public function index()
    {
        $items = FeeHead::latest()->paginate(20);
        return view('fee-heads.index', compact('items'));
    }

    public function create() { return view('fee-heads.form'); }

    protected function rules($id = null): array
    {
        return [
            'name'           => 'required|string|max:255',
            'category'       => 'required|in:' . implode(',', array_keys(FeeHead::categories())),
            'type'           => 'required|in:one_time,recurring,per_cycle',
            'default_amount' => 'required|integer|min:0',
            'is_active'      => 'nullable|boolean',
            'description'    => 'nullable|string',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);
        $head = FeeHead::create($validated);
        AuditLog::record('created', 'FeeHead', $head->id, "Created fee head {$head->name}");
        return redirect()->route('fee-heads.index')->with('success', 'Fee head created!');
    }

    public function show(FeeHead $feeHead)
    {
        $feeHead->load('vouchers');
        return view('fee-heads.show', compact('feeHead'));
    }

    public function edit(FeeHead $feeHead) { return view('fee-heads.form', compact('feeHead')); }

    public function update(Request $request, FeeHead $feeHead)
    {
        $validated = $request->validate($this->rules($feeHead->id));
        $validated['is_active'] = $request->boolean('is_active', true);
        $feeHead->update($validated);
        AuditLog::record('updated', 'FeeHead', $feeHead->id, "Updated fee head {$feeHead->name}");
        return redirect()->route('fee-heads.index')->with('success', 'Fee head updated!');
    }

    public function destroy(FeeHead $feeHead)
    {
        $name = $feeHead->name;
        $feeHead->delete();
        AuditLog::record('deleted', 'FeeHead', null, "Deleted fee head {$name}");
        return redirect()->route('fee-heads.index')->with('success', 'Fee head deleted!');
    }
}
