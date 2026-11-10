<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model {
    use HasFactory;
    protected $fillable = ['key','value','type','group'];

    public static function get(string $key, $default = null)
    {
        $value = Cache::rememberForever("setting:{$key}", fn () => static::where('key', $key)->value('value'));
        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function flag(string $key, bool $default = false): bool
    {
        $value = static::get($key, $default ? '1' : '0');
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    public static function set(string $key, $value, string $type = 'text', string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type, 'group' => $group]);
        Cache::forget("setting:{$key}");
    }

    protected static function booted()
    {
        static::saved(fn ($setting) => Cache::forget("setting:{$setting->key}"));
        static::deleted(fn ($setting) => Cache::forget("setting:{$setting->key}"));
    }
}
