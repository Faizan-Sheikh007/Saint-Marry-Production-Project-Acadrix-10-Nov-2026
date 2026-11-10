<?php

namespace App\Models;

use App\Casts\RsaEncrypted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Non-teaching staff (accountants, guards, drivers, librarians, peons,
 * lab attendants, etc). Mirrors the Teacher model's HR fields but with
 * a free-form `designation` instead of a subject, so admin can enter any
 * role.
 */
class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id', 'employee_id', 'name', 'father_name', 'email', 'phone',
        'emergency_contact', 'gender', 'date_of_birth', 'blood_group', 'marital_status',
        'address', 'city', 'cnic', 'bank_account_number',
        'qualification', 'experience', 'designation', 'department', 'employment_type',
        'salary', 'bank_name', 'bank_account_title',
        'joining_date', 'photo', 'status',
    ];

    protected $casts = [
        'joining_date'   => 'date',
        'date_of_birth'  => 'date',
        'salary'         => 'decimal:2',
        // Sensitive fields — encrypted at rest with RSA+AES envelope
        // encryption, transparently decrypted when accessed in PHP.
        'cnic'                 => RsaEncrypted::class,
        'bank_account_number'  => RsaEncrypted::class,
    ];

    protected $hidden = ['cnic', 'bank_account_number'];

    public function user() { return $this->belongsTo(User::class); }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }
}
