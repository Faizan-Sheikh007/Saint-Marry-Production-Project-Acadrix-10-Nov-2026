<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentBalanceTransaction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'balance_after' => 'decimal:2', 'paid_at' => 'datetime'];

    public function student() { return $this->belongsTo(Student::class); }
}
