<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentBalanceService;
use Illuminate\Console\Command;

class ApplyStudentCredit extends Command
{
    protected $signature = 'accounts:apply-student-credit {--student= : Student database ID or admission ID} {--apply : Commit allocations; without this option the command is read-only}';
    protected $description = 'Preview/apply existing student credit to authoritative unpaid vouchers (safe to repeat).';

    public function handle(StudentBalanceService $service): int
    {
        $query = Student::where('credit_balance', '>', 0);
        if ($id = $this->option('student')) {
            $query->where(function ($q) use ($id) {
                $q->where('student_id', $id);
                if (ctype_digit((string) $id)) $q->orWhere('id', (int) $id);
            });
        }
        $count = 0;
        $query->orderBy('id')->chunkById(100, function ($students) use ($service, &$count) {
            foreach ($students as $student) {
                if (! $student->isActiveEnrollment()) {
                    $this->warn($student->student_id.': skipped — Student status: '.$student->statusLabel().'; credit preserved. Reactivate before allocating.');
                    continue;
                }
                $count++;
                if ($this->option('apply')) {
                    $result = $service->applyAvailable($student, 'Credit reconciliation command');
                    $this->line($student->student_id.': applied '.number_format($result['applied'] / 100, 2)
                        .'; remaining credit '.number_format($result['remaining'] / 100, 2));
                } else {
                    $outstanding = $student->authoritativeVouchers()->reject(fn ($v) => $v->status === 'cancelled')->sum(fn ($v) => $v->balance());
                    $this->line($student->student_id.': credit '.$student->credit_balance.'; outstanding '
                        .number_format($outstanding, 2).'; would apply '.number_format(min((float) $student->credit_balance, $outstanding), 2));
                }
            }
        });
        $this->info(($this->option('apply') ? 'Applied' : 'Preview only — no changes made').'; '.$count.' student(s).');
        return self::SUCCESS;
    }
}
