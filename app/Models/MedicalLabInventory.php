<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MedicalLabInventory extends Model {
    use HasFactory;
    protected $fillable = ['name','category','quantity','unit','price','description','min_quantity','expiry_date','remarks'];
    protected $casts = ['expiry_date' => 'date'];

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && ! $this->isExpired() && $this->expiry_date->diffInDays(now()) <= $days;
    }
}
