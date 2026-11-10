<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    protected function rules(): array
    {
        return [
            'bank_name'      => 'required|string|max:255',
            'account_title'  => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'branch_name'    => 'nullable|string|max:255',
            'iban'           => 'nullable|string|max:50',
            'is_default'     => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active']  = $request->boolean('is_active', true);

        if ($validated['is_default']) {
            BankAccount::where('is_default', true)->update(['is_default' => false]);
        }

        $account = BankAccount::create($validated);
        AuditLog::record('created', 'BankAccount', $account->id, "Added bank account {$account->bank_name} ({$account->account_number})");

        return redirect()->route('settings.index')->with('success', 'Bank account added.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate($this->rules());
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active']  = $request->boolean('is_active', true);

        if ($validated['is_default']) {
            BankAccount::where('id', '!=', $bankAccount->id)->update(['is_default' => false]);
        }

        $bankAccount->update($validated);
        AuditLog::record('updated', 'BankAccount', $bankAccount->id, "Updated bank account {$bankAccount->bank_name}");

        return redirect()->route('settings.index')->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $name = $bankAccount->bank_name;
        $bankAccount->delete();
        AuditLog::record('deleted', 'BankAccount', null, "Removed bank account {$name}");

        return redirect()->route('settings.index')->with('success', 'Bank account removed.');
    }
}
