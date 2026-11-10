<?php
namespace App\Http\Controllers;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $items = Inventory::latest()->paginate(20);
        return view('inventory.index', compact('items'));
    }

    public function create() { return view('inventory.form'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'unit'     => 'nullable|string|max:20',
            'price'    => 'nullable|numeric',
        ]);
        Inventory::create($validated);
        return redirect()->route('admin.inventory.index')->with('success', 'Item added!');
    }

    public function show(Inventory $inventory) { return view('inventory.show', compact('inventory')); }
    public function edit(Inventory $inventory) { return view('inventory.form', compact('inventory')); }

    public function update(Request $request, Inventory $inventory)
    {
        $inventory->update($request->only('name', 'category', 'quantity', 'unit', 'price'));
        return redirect()->route('admin.inventory.index')->with('success', 'Inventory updated!');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->route('admin.inventory.index')->with('success', 'Item deleted!');
    }
}
