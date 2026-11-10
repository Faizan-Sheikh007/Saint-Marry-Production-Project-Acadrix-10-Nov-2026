<?php
/**
 * Read-only regression fixture for voucher payment grouping.
 * Uses lightweight Collection/model stubs but executes the actual service.
 * No vendor/, Laravel bootstrap, DB, or real student information required.
 * Run: php tests/Regression/voucher_payment_history_fixture.php
 */
declare(strict_types=1);

namespace Illuminate\Support {
    final class Collection implements \IteratorAggregate
    {
        public function __construct(public array $items = []) {}
        public function getIterator(): \Traversable { return new \ArrayIterator($this->items); }
        public function has(mixed $key): bool { return array_key_exists($key, $this->items); }
        public function get(mixed $key): mixed { return $this->items[$key] ?? null; }
        public function filter(?callable $fn = null): self {
            return new self(array_filter($this->items, $fn ?? fn ($v) => (bool) $v));
        }
        public function reject(callable $fn): self { return $this->filter(fn ($v) => !$fn($v)); }
        public function groupBy(callable|string $key): self {
            $result=[];
            foreach ($this->items as $item) {
                $group=is_string($key) ? $item->{$key} : $key($item);
                $result[$group][]=$item;
            }
            return new self(array_map(fn ($group) => new self($group), $result));
        }
        public function map(callable $fn): self {
            $items=[];
            foreach ($this->items as $key=>$value) $items[$key]=$fn($value,$key);
            return new self($items);
        }
        public function sortBy(string|callable $key): self {
            $items=$this->items;
            uasort($items, fn($a,$b) => (is_callable($key)?$key($a):$a->{$key}) <=> (is_callable($key)?$key($b):$b->{$key}));
            return new self($items);
        }
        public function sortByDesc(string|callable $key): self {
            $items=$this->items;
            uasort($items, fn($a,$b) => (is_callable($key)?$key($b):$b->{$key}) <=> (is_callable($key)?$key($a):$a->{$key}));
            return new self($items);
        }
        public function first(): mixed { return reset($this->items) ?: null; }
        public function pluck(string $key): self { return new self(array_map(fn($x)=>$x->{$key} ?? null, $this->items)); }
        public function unique(): self { return new self(array_unique($this->items)); }
        public function values(): self { return new self(array_values($this->items)); }
        public function implode(string $delimiter): string { return implode($delimiter,$this->items); }
        public function sum(string|callable $key): float {
            return (float) array_sum(array_map(fn($x)=>is_callable($key)?$key($x):$x->{$key},$this->items));
        }
        public function isNotEmpty(): bool { return count($this->items) > 0; }
        public function isEmpty(): bool { return count($this->items) === 0; }
        public function count(): int { return count($this->items); }
    }
}
namespace App\Models {
    use Illuminate\Support\Collection;
    class Voucher {
        public function __construct(public int $id,public string $voucher_no,private float $fee) {}
        public array $payments=[];
        public function total(): float { return $this->fee; }
        public function combinedPayments(): Collection {
            return new Collection($this->payments);
        }
    }
    class Payment {
        public ?string $proof_path=null;
        public ?string $submitted_by=null;
        public ?string $received_by='Accounts Officer';
        public ?\DateTimeImmutable $created_at=null;
        public ?\DateTimeImmutable $paid_at=null;
        public function __construct(public Voucher $voucher,public float $amount,public string $method='balance_credit',public ?string $status='verified',string $date='2026-10-30') {
            $this->paid_at=new \DateTimeImmutable($date);
            $this->created_at=$this->paid_at;
            $voucher->payments[]=$this;
        }
        public function isVerified(): bool { return ($this->status ?? 'verified')==='verified'; }
        public function isPending(): bool { return $this->status==='pending'; }
    }
}
namespace {
    use Illuminate\Support\Collection;
    use App\Models\{Payment,Voucher};
    use App\Services\VoucherPaymentHistory;
    require dirname(__DIR__,2).'/app/Services/VoucherPaymentHistory.php';
    $v1a=new Voucher(101,'VCH-261028-968E5',8000);
    $v1b=new Voucher(102,'VCH-261028-968E5',7000);
    $v2a=new Voucher(201,'VCH-261028-96148',10000);
    $v2b=new Voucher(202,'VCH-261028-96148',7000);
    $v3=new Voucher(301,'VCH-261028-95A3B',20000);
    $v4=new Voucher(401,'VCH-261024-280DA',60000);
    $vouchers=new Collection([$v1a,$v1b,$v2a,$v2b,$v3,$v4]);
    $payments=new Collection([
        new Payment($v1a,2000,'balance_credit','verified','2026-10-31'),
        new Payment($v1b,13000,'balance_credit','verified','2026-10-30'),
        new Payment($v2a,5000,'balance_credit'),
        new Payment($v2a,7000,'balance_credit'),
        new Payment($v2b,5000,'balance_credit'),
        new Payment($v3,20000,'balance_credit'),
        new Payment($v4,60000,'cash','verified','2026-10-24'),
    ]);
    function requireTrue(bool $condition, string $what): void {
        if (!$condition) throw new \RuntimeException('FAIL: '.$what);
    }
    $history=VoucherPaymentHistory::forStudent($vouchers,$payments);
    requireTrue($history->count()===4,'seven allocations produce four voucher rows');
    $byNo=[]; foreach ($history as $row) $byNo[$row->voucher_no]=$row;
    requireTrue($byNo['VCH-261028-968E5']->received_amount===15000.0,'sibling fee-head rows combine to Rs 15,000');
    requireTrue($byNo['VCH-261028-96148']->received_amount===17000.0,'three partial allocations combine to Rs 17,000');
    requireTrue($byNo['VCH-261028-95A3B']->received_amount===20000.0,'single allocation preserved');
    requireTrue($byNo['VCH-261024-280DA']->received_amount===60000.0,'cash receipt preserved');
    requireTrue($history->sum('received_amount')===112000.0,'total paid against vouchers = Rs 112,000');
    requireTrue($history->sum('direct_amount')===60000.0,'new cash is NOT inflated by existing credit allocations');
    requireTrue($history->sum('credit_amount')===52000.0,'previously received balance credit separated');
    requireTrue(VoucherPaymentHistory::verifiedAmountForVoucher($v1a)===2000.0,'single fee-head allocation is partial');
    // The fixture must emulate Voucher::combinedPayments across sibling fee-head rows.
    $v1a->payments=array_merge($v1a->payments,$v1b->payments);
    requireTrue(VoucherPaymentHistory::verifiedAmountForVoucher($v1a)===15000.0,'receipt total across sibling rows matches grouped history');
    $payments->items[]=new Payment($v1a,999,'online','pending','2026-11-02');
    $payments->items[]=new Payment($v2a,555,'online','rejected','2026-11-02');
    $rechecked=VoucherPaymentHistory::forStudent($vouchers,$payments);
    $recheckedByNo=[]; foreach ($rechecked as $row) $recheckedByNo[$row->voucher_no]=$row;
    requireTrue($rechecked->sum('received_amount')===112000.0,'pending and rejected amounts excluded from paid totals');
    requireTrue($recheckedByNo['VCH-261028-968E5']->status==='Verified + Pending','mixed verification disclosed');
    requireTrue($recheckedByNo['VCH-261028-96148']->status==='Verified','rejected claim does not undo verified payment');
    echo "PASS: 12 read-only payment allocation/grouping fixtures. No DB or live records used.\n";
}
