<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DailyLedgerEntry extends Model
{
    use HasFactory;

    /** How many hours after a ledger day ends before it locks for good. */
    const LOCK_HOURS_AFTER_DAY_END = 12;

    protected $fillable = [
        'entry_date', 'type', 'particulars', 'category', 'amount',
        'created_by', 'remarks', 'remarks_by', 'remarks_at', 'locked_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
        'remarks_at' => 'datetime',
        'locked_at'  => 'datetime',
    ];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function remarksBy() { return $this->belongsTo(User::class, 'remarks_by'); }

    /**
     * The exact moment a given ledger day becomes permanently locked:
     * midnight at the end of that day, plus the grace window.
     */
    public static function lockMomentFor($date): Carbon
    {
        return Carbon::parse($date)->endOfDay()->addHours(self::LOCK_HOURS_AFTER_DAY_END);
    }

    public static function isDateLocked($date): bool
    {
        return now()->greaterThanOrEqualTo(self::lockMomentFor($date));
    }

    /** Whether new entries can still be added to this specific ledger day. */
    public static function isDateOpenForEntries($date): bool
    {
        // Entries can only be added for today or the recent past, while that
        // day's ledger hasn't locked yet — never for a future date.
        $date = Carbon::parse($date)->startOfDay();
        return !$date->isFuture() && !self::isDateLocked($date);
    }

    public function isLocked(): bool
    {
        return self::isDateLocked($this->entry_date);
    }

    /**
     * Entry details may be corrected only while the entry is still on the
     * current calendar date. From the next date onward the original financial
     * details stay fixed and only a remark may be added.
     */
    public function canEditDetails(): bool
    {
        return $this->entry_date?->isToday() ?? false;
    }

    /**
     * Deletion remains permanently disabled. Corrections after the entry date
     * are documented with remarks instead of removing the audit trail.
     */
    public function delete()
    {
        throw new \RuntimeException('Daily ledger entries cannot be deleted. Edit them on the same date or add a remark afterward.');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('entry_date', $date);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }
}
