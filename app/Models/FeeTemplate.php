<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeTemplate extends Model {
    use HasFactory;
    protected $fillable = ['name','school_class_id','billing_cycle','parts_per_cycle','total_cycles','is_active','description'];
    protected $casts = ['is_active' => 'boolean'];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function items()       { return $this->hasMany(FeeTemplateItem::class); }
    public function vouchers()    { return $this->hasMany(Voucher::class); }

    public function totalAmount()
    {
        return $this->items->sum('amount');
    }

    /** "Semester" / "Year" / "Month" — the word used to label one cycle of this template. */
    public function cycleLabel(): string
    {
        return match ($this->billing_cycle) {
            'semester' => 'Semester',
            'annual'   => 'Year',
            'monthly'  => 'Month',
            default    => 'Term',
        };
    }
}
