<?php
namespace App\Http\Controllers;

use App\Models\FeeAssignment;
use App\Models\Student;
use App\Models\Voucher;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Additional relief granted after the installment plan was created. */
class PostAdmissionAidController extends Controller
{
    public function store(Request $request, Student $student, FeeAssignment $feeAssignment)
    {
        abort_unless($feeAssignment->student_id === $student->id, 404);
        $data = $request->validate([
            'type' => 'required|in:scholarship,financial_aid',
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);
        DB::transaction(function () use ($student, $feeAssignment, $data) {
            $plan = FeeAssignment::whereKey($feeAssignment->id)->lockForUpdate()->firstOrFail();
            // Grant only against real, uncollected charges. Past payments are never modified.
            $vouchers = Voucher::where('fee_assignment_id', $plan->id)
                ->where('status', '!=', 'cancelled')->orderBy('due_date')->orderBy('id')
                ->lockForUpdate()->get();
            $remaining = (float) $data['amount'];
            $available = round($vouchers->sum(fn ($v) => $v->balance()), 2);
            if ($remaining > $available) {
                throw ValidationException::withMessages(['amount' => 'Aid cannot exceed the unpaid amount on this installment plan (Rs. '.number_format($available, 0).').']);
            }
            foreach ($vouchers as $voucher) {
                if ($remaining <= 0) break;
                $relief = min($remaining, $voucher->balance());
                if ($relief <= 0) continue;
                $voucher->discount_amount = round((float) $voucher->discount_amount + $relief, 2);
                $voucher->save();
                VoucherController::recalcStatus($voucher);
                $remaining = round($remaining - $relief, 2);
            }
            $column = $data['type'] === 'scholarship' ? 'scholarship_amount' : 'financial_aid_amount';
            $plan->{$column} = round((float) $plan->{$column} + $data['amount'], 2);
            $plan->total_amount = max(0, round((float) $plan->total_amount - $data['amount'], 2));
            $plan->save();
            // Snapshot mirrors the actual outstanding balance after a grant.
            $student->adminFill(['fee_status' => $student->outstandingBalance() > 0 ? 'partial' : 'paid']);
            $student->save();
            AuditLog::record('updated', 'FeeAssignment', $plan->id,
                'Post-admission '.$data['type'].' Rs.'.$data['amount'].' for student '.$student->student_id
                .(!empty($data['notes']) ? ' — '.$data['notes'] : ''));
        });
        return back()->with('success', ucfirst(str_replace('_',' ', $data['type'])).' applied to unpaid installments and voucher balances.');
    }
}
