<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeTemplateItem extends Model {
    use HasFactory;
    protected $fillable = ['fee_template_id','fee_head_id','amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function feeTemplate() { return $this->belongsTo(FeeTemplate::class); }
    public function feeHead()     { return $this->belongsTo(FeeHead::class); }
}
