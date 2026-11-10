<?php
/**
 * No database/Composer needed. Executes the actual StudentFeeLedger service
 * against small fake voucher/fee-head/plan rows as of 2026-11-02.
 * Run: php tests/Regression/student_fee_ledger_fixture.php
 */
declare(strict_types=1);

namespace App\Models {
    final class FakeFeeHead {
        public function __construct(public string $name) {}
    }
    final class FakePlan {
        public function termLabelFor(int $cycle): string {return "Semester {$cycle} of 8";}
        public function cycleRangeLabel(): string {return 'Semester 1';}
    }
    final class Voucher {
        public object $feeHead;
        public ?object $feeAssignment;
        public ?\DateTimeImmutable $due_date;
        public float $amount;
        public float $discount_amount;
        public float $paid_amount;
        public ?int $cycle_no = 1;
        public ?int $installment_no = 1;
        public ?int $part_no = null;
        public string $title;

        public function __construct(
            public int $id, public string $voucher_no, public string $status,
            float $gross, float $discount, float $paid, ?string $due,
            string $feeName, bool $standalone = false
        ) {
            $this->amount = $gross;
            $this->discount_amount = $discount;
            $this->paid_amount = $paid;
            $this->feeHead = new FakeFeeHead($feeName);
            $this->feeAssignment = $standalone ? null : new FakePlan();
            $this->due_date = $due ? new \DateTimeImmutable($due) : null;
            $this->title = $feeName;
        }
        public function total(): float {return round($this->amount - $this->discount_amount,2);}
        public function balance(): float {return max(0, round($this->total() - $this->paid_amount,2));}
    }
}

namespace {
    use App\Models\Voucher;
    use App\Services\StudentFeeLedger;
    require dirname(__DIR__, 2) . '/app/Services/StudentFeeLedger.php';

    function verify(bool $condition, string $label): void {
        if (!$condition) throw new \RuntimeException('FAILED: '.$label);
    }
    $current = [
        new Voucher(1,'VCH-PAID','paid',20000,0,20000,'2026-09-24','Registration Fee'),
        new Voucher(2,'VCH-PAID','paid',12000,2000,10000,'2026-09-24','Tuition Fee'),
        new Voucher(3,'VCH-PARTIAL','partial',17000,0,5000,'2026-11-01','Tuition Fee'),
        new Voucher(4,'VCH-PARTIAL','unpaid',9200,0,0,'2026-11-01','Exam Fee'),
        new Voucher(5,'VCH-FUTURE','unpaid',26200,0,0,'2026-11-10','Tuition Fee'),
        new Voucher(6,'VCH-SOON','unpaid',5000,0,0,'2026-11-05','Lab Fee',true),
        new Voucher(7,'VCH-CANCEL','cancelled',1500,0,0,'2026-10-15','Old Fee'),
    ];
    $s = StudentFeeLedger::summarize($current,new \DateTimeImmutable('2026-11-02'));
    verify(count($s['rows'])===5,'7 fee-head records become 5 voucher numbers');
    $byNo=[];foreach ($s['rows'] as $row) $byNo[$row->voucher_no]=$row;
    verify($byNo['VCH-PAID']->amount===30000.0,'sibling fees group amount net of discount');
    verify($byNo['VCH-PAID']->paid===30000.0,'sibling fee-head payment allocations combine');
    verify(count($byNo['VCH-PAID']->lines)===2,'fee details preserve both heads');
    verify($byNo['VCH-PAID']->payment_state==='Paid','paid voucher recognized');
    verify($byNo['VCH-PARTIAL']->payment_state==='Partial','partial voucher recognized from payment');
    verify($byNo['VCH-PARTIAL']->schedule==='Overdue','due date passed');
    verify($byNo['VCH-FUTURE']->schedule==='Upcoming','future installment');
    verify($byNo['VCH-SOON']->schedule==='Due Soon','within seven days');
    verify($byNo['VCH-CANCEL']->payment_state==='Cancelled','cancelled separately shown');
    verify($s['totals']['billed']===87400.0,'current total excludes cancelled');
    verify($s['totals']['paid']===35000.0,'paid correctly summed');
    verify($s['totals']['pending']===52400.0,'remaining balance correct');
    verify($s['totals']['overdue']===21200.0,'overdue excludes paid portions');
    verify($s['totals']['due_soon']===5000.0,'due soon separate from overdue');
    verify($s['totals']['upcoming']===26200.0,'later amounts are upcoming');
    verify(abs($s['totals']['pending'] - $s['totals']['overdue'] - $s['totals']['due_soon'] - $s['totals']['upcoming'])<0.001,'remaining = overdue + soon + future');
    verify($s['counts']['paid']===1 && $s['counts']['partial']===1 && $s['counts']['unpaid']===2,'payment state counts');
    verify($s['counts']['overdue']===1 && $s['counts']['upcoming']===2,'date state filters');
    verify(str_contains($byNo['VCH-SOON']->filters, 'upcoming'),'upcoming filter includes future due-soon');
    verify(!str_contains($byNo['VCH-CANCEL']->filters,'unpaid'),'cancelled not shown as overdue/unpaid');
    verify($byNo['VCH-SOON']->term==='Other fee · Installment 1','standalone charge labelled correctly');
    $zero = StudentFeeLedger::summarize([
        new Voucher(91,'0','unpaid',750,0,0,'2026-12-01','Misc Fee',true),
        new Voucher(92,'0','unpaid',250,0,0,'2026-12-01','Exam Fee',true),
    ],new \DateTimeImmutable('2026-11-02'));
    verify(count($zero['rows']) === 1 && $zero['rows'][0]->voucher_no === '0', 'legacy voucher number 0 groups correctly');
    verify($zero['rows'][0]->amount === 1000.0, 'legacy zero voucher aggregates sibling amounts');
    // Accounts example (Aneesa): one installment has TWO separately numbered
    // paid vouchers.  Both must be displayed and the student must not be
    // marked overdue because the remaining installments are future dated.
    $aneesa = [
        new Voucher(7446,'VCH-261030-B1DBB','paid',22000,0,22000,'2026-10-15','Tuition Fee'),
        new Voucher(7449,'VCH-261030-E8246','paid',8000,0,8000,'2026-10-15','Tuition Fee'),
        new Voucher(7447,'VCH-261030-B26E6','partial',15000,0,7000,'2026-12-15','Tuition Fee'),
        new Voucher(7448,'VCH-261030-B2EB6','unpaid',15000,0,0,'2027-01-15','Tuition Fee'),
    ];
    $aneesa[0]->part_no = 1;
    $aneesa[1]->part_no = 1;
    $aneesa[2]->part_no = 2;
    $aneesa[3]->part_no = 3;
    $position = StudentFeeLedger::summarize($aneesa, new \DateTimeImmutable('2026-11-02'));
    verify(count($position['rows']) === 4, 'two paid vouchers in one installment stay distinct');
    verify($position['totals']['billed'] === 60000.0, 'Aneesa total payable 60,000');
    verify($position['totals']['paid'] === 37000.0, 'Aneesa total paid 37,000');
    verify($position['totals']['pending'] === 23000.0, 'Aneesa future pending 23,000');
    verify($position['totals']['overdue'] === 0.0, 'Aneesa no overdue on 02 Nov 2026');
    verify($position['totals']['upcoming'] === 23000.0, 'Aneesa all balance upcoming');
    verify($position['counts']['paid'] === 2, 'two separate paid vouchers');
    verify($position['counts']['partial'] === 1, 'one partially paid voucher');
    verify($position['counts']['unpaid'] === 1, 'one unpaid future voucher');
    $lookup = []; foreach ($position['rows'] as $row) $lookup[$row->voucher_no]=$row;
    verify(str_contains($lookup['VCH-261030-B2EB6']->filters, 'unpaid_only'), 'unpaid-only filter matches zero-paid voucher');
    verify(!str_contains($lookup['VCH-261030-B26E6']->filters, 'unpaid_only'), 'partial does not appear as wholly unpaid');
    verify($lookup['VCH-261030-B26E6']->payment_state === 'Partial'
        && $lookup['VCH-261030-B26E6']->schedule === 'Upcoming', 'partially paid can be upcoming');
    verify($lookup['VCH-261030-B2EB6']->schedule === 'Upcoming', 'unpaid voucher is upcoming');
    verify(array_sum(array_map(fn($v) => $v->part_no === 1 ? $v->total() : 0, $aneesa)) === 30000.0,
        'two first-installment paid vouchers combine to Rs.30,000 without hiding either voucher');
    // v5: Fee History must include the second installment's received portion
    // (Rs.7,000) even though Rs.8,000 remains scheduled for payment.
    $receivedHistory = array_values(array_filter($position['rows'], static fn ($row) =>
        $row->payment_state !== 'Cancelled' && $row->paid > 0.005
    ));
    verify(count($receivedHistory) === 3, 'Fee History contains two fully paid and one partially paid voucher');
    verify(array_sum(array_map(static fn ($row) => $row->amount, $receivedHistory)) === 45000.0,
        'fee history voucher amounts include 15,000 partial voucher');
    verify(array_sum(array_map(static fn ($row) => $row->paid, $receivedHistory)) === 37000.0,
        'fee history shows all 37,000 paid, including 7,000 on partial');
    verify(array_sum(array_map(static fn ($row) => $row->balance, $receivedHistory)) === 8000.0,
        'fee history shows 8,000 remaining on partial; unpaid 15,000 not double counted');
    verify(count(array_filter($receivedHistory, static fn ($row) => $row->payment_state === 'Partial')) === 1,
        'one partial voucher is explicitly included with payment history');
    echo "PASS: 42 Student Fee Ledger fixture assertions.\n";
}
