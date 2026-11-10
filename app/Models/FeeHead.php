<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model {
    use HasFactory;
    protected $fillable = ['name','category','type','default_amount','is_active','description'];
    protected $casts = ['is_active' => 'boolean', 'default_amount' => 'decimal:2'];

    public function templateItems() { return $this->hasMany(FeeTemplateItem::class); }
    public function vouchers()      { return $this->hasMany(Voucher::class); }

    public static function categories(): array
    {
        return [
            'tuition' => 'Tuition Fee', 'admission' => 'Admission/Enrollment Fee', 'exam' => 'Examination Fee',
            'library' => 'Library Fee', 'lab' => 'Lab Fee', 'transport' => 'Transport Fee',
            'hostel' => 'Hostel/Dormitory Fee', 'thesis' => 'Thesis/Research Fee',
            'late_registration' => 'Late Registration Fee', 'degree_transcript' => 'Degree/Transcript Issuance Fee',
            'id_card' => 'ID Card Fee',
            'sports_fund' => 'Sports Fund',
            'internship_fee' => 'Internship Fee',
            'mid_absent_restoration_fee' => 'Mid/Absent/Restoration Fee',
            'supply_fee' => 'Supply Fee',
            'board_verification' => 'Board Verification Fee',
            'program_change_fee' => 'Program Change Fee',
            'misc' => 'Miscellaneous',
        ];
    }
}
