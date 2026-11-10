<?php
namespace App\Http\Controllers;
use App\Models\ComputerLabInventory;
use Illuminate\Http\Request;

class ComputerLabInventoryController extends Controller
{
    public function index()
    {
        $items = ComputerLabInventory::latest()->paginate(20);
        return view('computer-lab-inventory.index', compact('items'));
    }

    public function create() { return view('computer-lab-inventory.form'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'category'          => 'nullable|string|max:100',
            'item_type'         => 'nullable|in:workstation,server,peripheral,networking,other',
            'quantity'          => 'required|integer|min:0',
            'unit'              => 'nullable|string|max:20',
            'price'             => 'nullable|numeric',
            'min_quantity'      => 'nullable|integer|min:0',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',
            'server_role'       => 'nullable|string|max:255',
            'operating_system'  => 'nullable|string|max:255',
            'ip_address'        => 'nullable|string|max:100',
        ]);
        ComputerLabInventory::create($validated);
        return redirect()->route('admin.computer-lab-inventory.index')->with('success', 'Item added!');
    }

    public function show(ComputerLabInventory $computer_lab_inventory) { return view('computer-lab-inventory.show', ['item' => $computer_lab_inventory]); }
    public function edit(ComputerLabInventory $computer_lab_inventory) { return view('computer-lab-inventory.form', ['item' => $computer_lab_inventory]); }

    public function update(Request $request, ComputerLabInventory $computer_lab_inventory)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'category'          => 'nullable|string|max:100',
            'item_type'         => 'nullable|in:workstation,server,peripheral,networking,other',
            'quantity'          => 'required|integer|min:0',
            'unit'              => 'nullable|string|max:20',
            'price'             => 'nullable|numeric',
            'min_quantity'      => 'nullable|integer|min:0',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',
            'server_role'       => 'nullable|string|max:255',
            'operating_system'  => 'nullable|string|max:255',
            'ip_address'        => 'nullable|string|max:100',
        ]);
        $computer_lab_inventory->update($validated);
        return redirect()->route('admin.computer-lab-inventory.index')->with('success', 'Inventory updated!');
    }

    public function destroy(ComputerLabInventory $computer_lab_inventory)
    {
        $computer_lab_inventory->delete();
        return redirect()->route('admin.computer-lab-inventory.index')->with('success', 'Item deleted!');
    }
}
