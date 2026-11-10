<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradingScale;
use Illuminate\Http\Request;

class GradingScaleController extends Controller
{
    /** Manage the custom grading formula (grade -> percentage band) used across all marksheets. */
    public function index()
    {
        $bands = GradingScale::orderByDesc('min_percentage')->get();
        return view('admin.grading-scale.index', compact('bands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade'          => 'required|string|max:10',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'remarks'        => 'nullable|string|max:100',
        ]);

        $validated['sort_order'] = (GradingScale::max('sort_order') ?? 0) + 1;
        GradingScale::create($validated);

        return back()->with('success', 'Grade band added.');
    }

    public function update(Request $request, GradingScale $gradingScale)
    {
        $validated = $request->validate([
            'grade'          => 'required|string|max:10',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'remarks'        => 'nullable|string|max:100',
        ]);

        $gradingScale->update($validated);

        return back()->with('success', 'Grade band updated. This is now reflected on every marksheet.');
    }

    public function destroy(GradingScale $gradingScale)
    {
        $gradingScale->delete();
        return back()->with('success', 'Grade band removed.');
    }
}
