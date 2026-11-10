<?php
/**
 * Execute the real receipt-grouping service without requiring vendor/ or a DB.
 * Run: php tests/Regression/student_received_payment_history_fixture.php
 */
declare(strict_types=1);

namespace Illuminate\Support {
    class Collection implements \IteratorAggregate
    {
        public function __construct(public array $items = []) {}
        public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
        public function filter(?callable $fn = null): self { return new self(array_filter($this->items, $fn ?? fn ($v) => (bool) $v)); }
        public function groupBy(callable|string $key): self {
            $groups=[];
            foreach ($this->items as $row) $groups[is_callable($key) ? $key($row) : $row->{$key}][]=$row;
            return new self(array_map(fn ($items) => new self($items), $groups));
        }
        public function map(callable $fn): self { $items=[];foreach($this->items as $k=>$v)$items[$k]=$fn($v,$k);return new self($items); }
        public function sortBy(callable|string $key): self {
            $items=$this->items;uasort($items,fn($a,$b)=> (is_callable($key)?$key($a):$a->{$key}) <=>(is_callable($key)?$key($b):$b->{$key}));return new self($items);
        }
        public function sortByDesc(callable|string $key): self {
            $items=$this->items;uasort($items,fn($a,$b)=> (is_callable($key)?$key($b):$b->{$key}) <=>(is_callable($key)?$key($a):$a->{$key}));return new self($items);
        }
        public function first(): mixed { foreach ($this->items as $row) return $row;return null; }
        public function pluck(string $key): self {
            return new self(array_map(function($row)use($key){foreach(explode('.',$key) as $field){$row=$row->{$field}??null;if($row===null)break;}return $row;},$this->items));
        }
        public function unique(): self { return new self(array_unique($this->items)); }
        public function values(): self { return new self(array_values($this->items)); }
        public function sum(callable|string $field): float {return (float) array_sum(array_map(fn ($row)=>is_callable($field)?$field($row):$row->{$field},$this->items));}
        public function concat(iterable $other): self {return new self(array_merge(array_values($this->items),array_values(iterator_to_array($other))));}
        public function count(): int {return count($this->items);}
    }
}
namespace App\Models {
    class Voucher {public function __construct(public string $voucher_no) {}}
    class Payment {
        public ?string $proof_path=null;
        public ?string $reference_no='FR-01';
        public ?string $received_by='Accounts Officer';
        public ?string $notes=null;
        public ?\DateTimeImmutable $paid_at=null;
        public ?\DateTimeImmutable $created_at=null;
        public function __construct(public int $id,public Voucher $voucher,public float $amount,public string $method='cash',public ?string $receipt_batch_id=null,public ?string $status='verified') {
            $this->paid_at=new \DateTimeImmutable('2026-11-03');$this->created_at=$this->paid_at;
        }
        public function isVerified(): bool {return ($this->status ?? 'verified')==='verified';}
    }
    class StudentBalanceTransaction {
        public string $type='receipt';
        public string $method='cash';
        public ?string $reference_no='extra';
        public ?string $recorded_by='Accounts Officer';
        public ?string $notes=null;
        public ?\DateTimeImmutable $paid_at=null;
        public ?\DateTimeImmutable $created_at=null;
        public function __construct(public int $id, public float $amount) {
            $this->paid_at=new \DateTimeImmutable('2026-11-02');$this->created_at=$this->paid_at;
        }
    }
}
namespace {
    use App\Models\{Payment, Voucher, StudentBalanceTransaction};
    use App\Services\StudentReceivedPaymentHistory;
    use Illuminate\Support\Collection;
    function collect(mixed $value=[]): Collection {return new Collection(is_array($value)?$value:[$value]);}
    require dirname(__DIR__,2).'/app/Services/StudentReceivedPaymentHistory.php';
    function check(bool $valid,string $message): void {if(!$valid) throw new \RuntimeException($message);}

    $a=new Voucher('VCH-1'); $b=new Voucher('VCH-2');
    $payments=collect([
        new Payment(101,$a,19600,'cash','batch-22k'),
        new Payment(102,$b,2400,'cash','batch-22k'),
        new Payment(103,$b,3000,'bank_transfer','batch-3k'),
        new Payment(104,$b,200,'cash','batch-200'),
        new Payment(105,$a,1500,'balance_credit'),
        new Payment(106,$a,999,'online',null,'pending'),
        new Payment(107,$a,333,'online',null,'rejected'),
    ]);
    $history=StudentReceivedPaymentHistory::forStudent($payments,collect());
    check($history->count()===3,'one split collection must appear as one receipt');
    check($history->sum('amount')===25200.0,'22k + 3k + 200 must equal 25,200');
    $byNumber=[];foreach($history as $r)$byNumber[$r->receipt_number]=$r;
    check($byNumber['PR-00000101']->amount===22000.0,'split fee rows must render Rs 22,000');
    check($byNumber['PR-00000101']->voucher_numbers->count()===2,'show both allocated vouchers');
    check($byNumber['PR-00000103']->amount===3000.0,'the second collection is separate');
    check($byNumber['PR-00000104']->amount===200.0,'the third collection is separate');
    check(!isset($byNumber['PR-00000105']),'credit allocation must not count as new money');

    $history=StudentReceivedPaymentHistory::forStudent($payments,collect([new StudentBalanceTransaction(1,500)]));
    check($history->count()===4,'extra cash receipt adds one collection');
    check($history->sum('amount')===25700.0,'extra receipts count once and credit allocations never count again');

    $history=StudentReceivedPaymentHistory::forStudent(collect([
        new Payment(202,$a,19500,'cash'), new Payment(203,$b,2500,'cash'),
    ]),collect());
    check($history->count()===2,'historic unbatched voucher allocations must NOT be guessed into one collection');
    check($history->sum('amount')===22000.0,'historic allocated amounts still reconcile');
    echo "PASS: individual receipts, split grouping, 25,200 total, extra credit, legacy, pending/rejected exclusions\n";
}
