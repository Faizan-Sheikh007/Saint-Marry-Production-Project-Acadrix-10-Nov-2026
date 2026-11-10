<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ComputerLabInventory extends Model {
    use HasFactory;
    protected $fillable = ['name','category','item_type','quantity','unit','price','description','specification','server_role','operating_system','ip_address','min_quantity'];

    public function isServer(): bool
    {
        return $this->item_type === 'server';
    }

    public static function itemTypes(): array
    {
        return [
            'workstation' => 'Workstation',
            'server'      => 'Server',
            'peripheral'  => 'Peripheral',
            'networking'  => 'Networking Equipment',
            'other'       => 'Other',
        ];
    }
}
