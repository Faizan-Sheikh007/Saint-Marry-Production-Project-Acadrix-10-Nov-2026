<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {
    use HasFactory;
    protected $fillable = ['description','icon','color','user_id','model_type','model_id'];
}
