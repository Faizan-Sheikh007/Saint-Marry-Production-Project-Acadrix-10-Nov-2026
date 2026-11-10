<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings     = Setting::pluck('value', 'key')->toArray();
        $bankAccounts = BankAccount::orderByDesc('is_default')->orderBy('sort_order')->orderBy('bank_name')->get();
        return view('settings.index', compact('settings', 'bankAccounts'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'institution_name'   => 'nullable|string|max:255',
            'institution_tagline' => 'nullable|string|max:255',
            'campus_name'        => 'nullable|string|max:255',
            'institution_domain' => 'nullable|string|max:255',
            'academic_session'   => 'nullable|string|max:50',
            'currency'           => 'nullable|string|max:10',
            'installment_max'    => 'nullable|integer|min:1|max:12',
            'attendance_hold_enabled' => 'nullable|boolean',
            'attendance_threshold'    => 'nullable|numeric|min:0|max:100',
            'id_card_reissue_fee'     => 'nullable|numeric|min:0',
            'tax_percent'        => 'nullable|numeric|min:0',
            'fee_summary_note'         => 'nullable|string|max:2000',
            'fee_summary_declaration'  => 'nullable|string|max:2000',
            'bank_name'          => 'nullable|string|max:255',
            'bank_account_title' => 'nullable|string|max:255',
            'bank_account_no'    => 'nullable|string|max:100',
            'institution_logo'   => 'nullable|image|max:5120',
            'authorized_signatory_name'      => 'nullable|string|max:255',
            'authorized_signatory_title'     => 'nullable|string|max:255',
            'authorized_signatory_signature' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'authorized_signatory_signature_data' => 'nullable|string',
            'remove_authorized_signature'    => 'nullable|boolean',
        ]);

        $validated['attendance_hold_enabled'] = $request->boolean('attendance_hold_enabled') ? '1' : '0';

        if ($request->hasFile('institution_logo')) {
            $oldLogo = Setting::get('institution_logo');
            if ($oldLogo) Storage::disk('public')->delete($oldLogo);
            $validated['institution_logo'] = $request->file('institution_logo')->store('branding', 'public');
        } else {
            unset($validated['institution_logo']);
        }

        // ── Authorized Signatory signature (drawn on-screen or uploaded) ──
        unset($validated['authorized_signatory_signature_data']); // never persisted as a raw setting
        if ($request->boolean('remove_authorized_signature')) {
            $oldSig = Setting::get('authorized_signatory_signature');
            if ($oldSig) Storage::disk('public')->delete($oldSig);
            $validated['authorized_signatory_signature'] = '';
        } elseif ($sigPath = $this->storeAuthorizedSignature($request)) {
            $oldSig = Setting::get('authorized_signatory_signature');
            if ($oldSig) Storage::disk('public')->delete($oldSig);
            $validated['authorized_signatory_signature'] = $sigPath;
        } else {
            unset($validated['authorized_signatory_signature']);
        }
        unset($validated['remove_authorized_signature']);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully!');
    }

    /**
     * Persist the institution's authorized-signatory signature, either
     * drawn on the on-screen pad (submitted as a base64 PNG data URL in
     * `authorized_signatory_signature_data`) or uploaded as an image file
     * (`authorized_signatory_signature`). Returns the stored path, or null
     * if nothing new was submitted.
     */
    protected function storeAuthorizedSignature(Request $request): ?string
    {
        if ($request->hasFile('authorized_signatory_signature')) {
            return $request->file('authorized_signatory_signature')->store('branding/signatures', 'public');
        }

        if ($request->filled('authorized_signatory_signature_data')) {
            $data = $request->input('authorized_signatory_signature_data');
            if (preg_match('/^data:image\/(png|jpe?g);base64,/', $data, $m)) {
                $ext     = $m[1] === 'jpg' ? 'jpeg' : $m[1];
                $decoded = base64_decode(substr($data, strpos($data, ',') + 1));
                if ($decoded !== false) {
                    $path = 'branding/signatures/' . uniqid('sig_') . '.' . $ext;
                    Storage::disk('public')->put($path, $decoded);
                    return $path;
                }
            }
        }

        return null;
    }

    public function reset()
    {
        Setting::query()->delete();
        return redirect()->route('settings.index')->with('success', 'Settings reset to defaults.');
    }
}
