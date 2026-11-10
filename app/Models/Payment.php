<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    use HasFactory;
    protected $fillable = [
        'voucher_id','amount','method','paid_at','reference_no','received_by','notes',
        'status','proof_path','submitted_by','verified_by','verified_at','rejection_reason',
        'receipt_batch_id',
    ];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'verified_at' => 'datetime'];

    public function voucher() { return $this->belongsTo(Voucher::class); }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isVerified(): bool { return ($this->status ?? 'verified') === 'verified'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
