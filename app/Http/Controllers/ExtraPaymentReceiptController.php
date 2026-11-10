<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, BankAccount, Setting, Student, StudentBalanceTransaction};
use App\Services\{LegacyExtraPaymentReceipt, RupeeWords};

/** Reprints persisted payments only. Nothing here changes money or records a receipt twice. */
class ExtraPaymentReceiptController extends Controller
{
    public function index(Student $student)
    {
        $receipts = $student->balanceTransactions()->where('type', 'receipt')->where('amount', '>', 0)
            ->latest('id')->paginate(20, ['*'], 'receipt_page')->withQueryString();
        $legacyReceipts = AuditLog::where('action', 'paid')->where('subject_type', 'Student')
            ->where('subject_id', $student->id)
            ->where('description', 'like', 'Received extra payment of %')
            ->latest('id')->paginate(20, ['*'], 'legacy_page')->withQueryString();
        // Reject anything not written in the old payment action's exact format.
        $legacyReceipts->setCollection($legacyReceipts->getCollection()->map(function (AuditLog $record) {
            $data = LegacyExtraPaymentReceipt::parse($record->description);
            return $data ? ['record' => $record, 'data' => $data] : null;
        })->filter()->values());
        return response()->view('payments.extra-receipts', compact('student', 'receipts', 'legacyReceipts'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function show(Student $student, StudentBalanceTransaction $transaction, bool $download = false, bool $asVoucher = false)
    {
        abort_unless((int) $transaction->student_id === (int) $student->id
            && $transaction->type === 'receipt' && (float) $transaction->amount > 0, 404);
        return $this->render($student, [
            'number' => 'EPR-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'source' => 'Student credit receipt #'.$transaction->id,
            'legacy' => false,
            'amount' => (string) $transaction->amount,
            'student_name' => $student->name,
            'student_id' => $student->student_id,
            'paid_at' => $transaction->paid_at?->format('d M Y') ?? 'Not recorded',
            'recorded_at' => $transaction->created_at?->format('d M Y, h:i A') ?? 'Not recorded',
            'method' => match ($transaction->method) {
                'cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque',
                'online' => 'Online', default => 'Not recorded',
            },
            'reference_no' => $transaction->reference_no ?: 'Not recorded',
            'received_by' => $transaction->recorded_by ?: 'Not recorded',
            'notes' => $transaction->notes,
            'download_url' => route('payments.extra-receipts.download', [$student, $transaction]),
            'receipt_url' => route('payments.extra-receipts.show', [$student, $transaction]),
            'voucher_url' => route('payments.extra-receipts.voucher', [$student, $transaction]),
        ], $download, $asVoucher);
    }

    public function legacy(Student $student, AuditLog $auditLog, bool $download = false, bool $asVoucher = false)
    {
        abort_unless($auditLog->action === 'paid' && $auditLog->subject_type === 'Student'
            && (int) $auditLog->subject_id === (int) $student->id, 404);
        $data = LegacyExtraPaymentReceipt::parse($auditLog->description);
        abort_unless($data !== null, 404);
        $auditLog->loadMissing('user');
        return $this->render($student, [
            'number' => 'EPR-L-'.str_pad((string) $auditLog->id, 8, '0', STR_PAD_LEFT),
            'source' => 'Historical audit entry #'.$auditLog->id,
            'legacy' => true,
            'amount' => $data['amount'],
            'student_name' => $data['student_name'],
            'student_id' => $data['student_id'],
            // Never substitute the audit creation time for the actual payment date.
            'paid_at' => 'Not recorded in legacy entry',
            'recorded_at' => $auditLog->created_at?->format('d M Y, h:i A') ?? 'Not recorded',
            'method' => 'Not recorded in legacy entry',
            'reference_no' => $data['reference_no'] ?: 'Not recorded',
            'received_by' => $auditLog->user?->name ?: 'Not recorded',
            'notes' => null,
            'download_url' => route('payments.extra-receipts.legacy-download', [$student, $auditLog]),
            'receipt_url' => route('payments.extra-receipts.legacy', [$student, $auditLog]),
            'voucher_url' => route('payments.extra-receipts.legacy-voucher', [$student, $auditLog]),
        ], $download, $asVoucher);
    }

    /** A voucher-format copy of the same receipt, never a new fee demand or cash entry. */
    public function voucher(Student $student, StudentBalanceTransaction $transaction)
    {
        // Share ownership, receipt-type and positive-amount checks with receipt printing.
        return $this->show($student, $transaction, false, true);
    }

    public function legacyVoucher(Student $student, AuditLog $auditLog)
    {
        return $this->legacy($student, $auditLog, false, true);
    }

    public function download(Student $student, StudentBalanceTransaction $transaction)
    {
        // Reuse the exact same student/type/positive-amount authorization checks as the print view.
        return $this->show($student, $transaction, true);
    }

    public function legacyDownload(Student $student, AuditLog $auditLog)
    {
        return $this->legacy($student, $auditLog, true);
    }

    private function render(Student $student, array $receipt, bool $download = false, bool $asVoucher = false)
    {
        $student->loadMissing(['program', 'schoolClass', 'academicYear', 'user']);
        $receipt['words'] = RupeeWords::forAmount($receipt['amount']);
        $institutionName = Setting::get('institution_name', 'Acadrix Institute');
        $logoPath = Setting::get('institution_logo');
        $generatedAt = now()->format('d M Y, h:i A');
        $profile = [
            'program' => $student->program?->name ?? 'Not recorded',
            'session' => $student->session ?: ($student->academicYear?->name ?? 'Not recorded'),
            'semester' => $student->schoolClass?->section_label ?: ($student->schoolClass?->name ?? 'Not recorded'),
        ];
        // The same branded template is used for printing and browser PDF saving.
        // Receipt, voucher and download links all open the same one-page student/bank/office sheet.
        $downloadNotice = $download
            ? 'Use Print All 3 Copies / Save as PDF to download the student, bank, and office copies together on one A4 landscape page in the existing voucher design.'
            : null;
        $bankAccounts = BankAccount::active();
        $declaration = Setting::get('fee_summary_declaration', '');
        // A login ID is optional. No password is exposed on either payment document.
        $studentLogin = $student->user?->email;
        return response()->view($asVoucher ? 'payments.extra-payment-voucher' : 'payments.extra-receipt', compact(
            'student', 'receipt', 'institutionName', 'logoPath', 'generatedAt', 'downloadNotice', 'profile', 'bankAccounts', 'declaration', 'studentLogin'
        ))->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
