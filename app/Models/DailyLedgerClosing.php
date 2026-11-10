<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLedgerClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date', 'denominations', 'counted_total', 'expected_total', 'difference', 'closed_by',
    ];

    protected $casts = [
        'entry_date'     => 'date',
        'denominations'  => 'array',
        'counted_total'  => 'decimal:2',
        'expected_total' => 'decimal:2',
        'difference'     => 'decimal:2',
    ];

    public function closedBy() { return $this->belongsTo(User::class, 'closed_by'); }

    /** Standard PKR/most-currency note & coin denominations offered in the calculator. */
    public static function denominationUnits(): array
    {
        return [5000, 1000, 500, 100, 50, 20, 10, 5, 2, 1];
    }
}
