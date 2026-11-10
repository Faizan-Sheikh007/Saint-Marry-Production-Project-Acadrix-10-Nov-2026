<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model {
    use HasFactory;
    protected $fillable = [
        'bank_name', 'account_title', 'account_number', 'branch_name',
        'iban', 'is_default', 'is_active', 'sort_order',
    ];
    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function feeAssignments() { return $this->hasMany(FeeAssignment::class); }
    public function vouchers()       { return $this->hasMany(Voucher::class); }

    public static function active()
    {
        return static::where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->orderBy('bank_name')->get();
    }
}
