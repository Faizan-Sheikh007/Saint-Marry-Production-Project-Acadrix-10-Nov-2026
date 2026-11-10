<?php

namespace App\Http\Controllers;

use App\Models\FeeHead;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Voucher;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class StudentIdCardController extends Controller
{
    public function show(Student $student)
    {
        $student->loadMissing(['program', 'schoolClass', 'academicYear']);
        $qrUrl = $this->qrUrlFor($student);
        return view('id-cards.show', compact('student', 'qrUrl'));
    }

    /**
     * Public, unauthenticated record lookup — this is what the QR code on the
     * printed card points to. The URL is cryptographically signed (and
     * time-limited to the card's validity), so it can't be forged or altered,
     * but no login is required, since a gate guard scanning the card won't
     * have (or need) a staff account.
     */
    public function verify(Request $request, Student $student)
    {
        $student->loadMissing(['program', 'schoolClass', 'academicYear']);
        abort_unless($request->hasValidSignature(), 403, 'This QR code is invalid or has expired.');

        return view('id-cards.verify', compact('student'));
    }

    /**
     * Build the signed verification URL encoded in a student's QR code.
     * Expires at the same time the card itself becomes invalid.
     */
    public function qrUrlFor(Student $student): string
    {
        $student->loadMissing('academicYear');
        $expiresAt = $student->idCardExpiryDate();
        $expiresAt = $expiresAt
            ? $expiresAt->copy()->endOfDay()
            : ($student->admission_date ? $student->admission_date->copy()->addYears(4) : now()->addYears(4));

        return URL::temporarySignedRoute(
            'students.id-card.verify',
            $expiresAt,
            ['student' => $student->id]
        );
    }

    public function issue(Student $student)
    {
        $student->update([
            'id_card_number'    => 'ID-' . $student->student_id,
            'id_card_issued_at' => now(),
        ]);

        AuditLog::record('issued', 'Student', $student->id, "ID card issued for {$student->name}");

        return redirect()->route('students.id-card', $student)->with('success', 'ID card issued.');
    }

    /**
     * Reissue a lost/damaged ID card. Charges the configured "ID Card Fee" head
     * by generating a voucher for the student.
     */
    public function reissue(Student $student)
    {
        $student->increment('id_card_reissue_count');
        $student->update(['id_card_issued_at' => now()]);

        $feeHead = FeeHead::where('category', 'id_card')->where('is_active', true)->first();
        $amount  = $feeHead->default_amount ?? (float) Setting::get('id_card_reissue_fee', 0);

        if ($amount > 0) {
            Voucher::create([
                'voucher_no'  => Voucher::generateVoucherNo(),
                'student_id'  => $student->id,
                'fee_head_id' => $feeHead->id ?? null,
                'title'       => 'ID Card Reissue Fee',
                'amount'      => $amount,
                'due_date'    => now()->addDays(7),
                'issued_at'   => now(),
                'status'      => 'unpaid',
            ]);
        }

        AuditLog::record('reissued', 'Student', $student->id, "ID card reissued for {$student->name}" . ($amount > 0 ? " (fee: {$amount})" : ''));

        return redirect()->route('students.id-card', $student)->with('success', 'ID card reissued' . ($amount > 0 ? ' and a reissue-fee voucher was generated.' : '.'));
    }

    public function bulk(SchoolClass $schoolClass)
    {
        $students = $schoolClass->students()->where('status', 'active')
            ->with(['program', 'schoolClass', 'academicYear'])
            ->orderBy('name')->get();
        $qrUrls   = $students->mapWithKeys(fn ($s) => [$s->id => $this->qrUrlFor($s)]);
        return view('id-cards.bulk', compact('schoolClass', 'students', 'qrUrls'));
    }
}
