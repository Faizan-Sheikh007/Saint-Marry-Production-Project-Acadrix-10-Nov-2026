<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model {
    use HasFactory;
    protected $table = 'app_notifications';
    protected $fillable = ['message','icon','color','read','user_id'];
    protected $casts = ['read' => 'boolean'];
}
