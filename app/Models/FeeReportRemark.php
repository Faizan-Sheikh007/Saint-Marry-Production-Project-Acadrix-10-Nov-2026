<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReportRemark extends Model
{
    protected $fillable = ['report_type', 'fee_assignment_id', 'remarks', 'updated_by'];

    public function feeAssignment() { return $this->belongsTo(FeeAssignment::class); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
}
