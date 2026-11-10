<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Download-only receipt renderer using the application's existing PDF writer.
 * Original payment values only; never reads or writes student credit.
 * Core PDF fonts cannot faithfully render every script. Non-ASCII receipts use
 * the existing Unicode HTML print view instead of silently corrupting names.
 */
final class ExtraPaymentSlipPdf
{
    public static function supports(array $receipt, array $profile, string $institution): bool
    {
        $values = [$institution, ...array_values($profile), ...array_values($receipt)];
        foreach ($values as $value) {
            if (is_string($value) && preg_match('/[^\x09\x0A\x0D\x20-\x7E]/', $value)) return false;
        }
        return true;
    }

    public function render(array $receipt, array $profile, string $institution, string $generatedAt): string
    {
        if (!self::supports($receipt, $profile, $institution)) {
            throw new \InvalidArgumentException('Use the Unicode print view for this receipt.');
        }
        $pdf = new SimplePdfDocument('portrait');
        foreach (['Student Copy', 'Bank Copy', 'Office Copy'] as $index => $copy) {
            if ($index > 0) $pdf->pageBreak();
            $this->write($pdf, $institution, 12, true);
            $pdf->text('ACCOUNTS DEPARTMENT', 8)->blank(6)->title('Payment Receiving Receipt');
            $this->write($pdf, $copy.' | '.$receipt['number'], 10, true);
            $pdf->blank(10);
            foreach ([
                'Student name' => $receipt['student_name'], 'Student ID' => $receipt['student_id'],
                'Program (current record)' => $profile['program'],
                'Session (current record)' => $profile['session'],
                'Semester / Part (current record)' => $profile['semester'],
                'Payment date' => $receipt['paid_at'], 'Payment method' => $receipt['method'],
                'Reference / Transaction No.' => $receipt['reference_no'],
                'Recorded by' => $receipt['received_by'],
            ] as $label => $value) {
                $this->write($pdf, $label.': '.(string) $value, 9);
                $pdf->blank(2);
            }
            $pdf->blank(8)->section('Extra / Advance Payment - No Voucher Required');
            $this->write($pdf, 'AMOUNT RECEIVED: Rs. '.number_format((float) $receipt['amount'], 2), 15, true);
            $this->write($pdf, 'In words: '.$receipt['words'], 9, true);
            $pdf->blank(10);
            $this->write($pdf, 'This receipt acknowledges the recorded payment only. Applying it to vouchers is not another payment. It does not certify that all student dues are cleared.', 8);
            if ($receipt['legacy']) {
                $pdf->blank(6);
                $this->write($pdf, 'HISTORICAL RECEIPT: Reproduced from the original payment audit entry. The previous system did not retain the payment method or selected payment date. Reprinting does not record a new collection.', 8);
            }
            if (!empty($receipt['notes'])) {
                $pdf->blank(6);
                $this->write($pdf, 'Notes: '.$receipt['notes'], 8);
            }
            $pdf->blank(12);
            $this->write($pdf, 'Source: '.$receipt['source'].' | Entry recorded: '.$receipt['recorded_at'], 7);
            $this->write($pdf, 'Print Date: '.$generatedAt.'. Student profile details reflect the current record.', 7);
            $this->write($pdf, 'Computer-generated receipt; no signature required. Reprints retain the same receipt number.', 7);
        }
        return $pdf->output();
    }

    /** Conservative width bound for the core fonts; long names/notes wrap, never truncate. */
    private function write(SimplePdfDocument $pdf, string $text, int $size, bool $bold = false): void
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        $maxChars = max(15, (int) floor(523 / ($size * 1.1)));
        foreach (explode("\n", wordwrap($text, $maxChars, "\n", true)) as $line) {
            $pdf->text($line, $size, $bold);
        }
    }
}
