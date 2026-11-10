<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN      = 'admin';
    const ROLE_ACCOUNTANT = 'accountant';
    const ROLE_TEACHER    = 'teacher';
    const ROLE_STUDENT    = 'student';
    const ROLE_PARENT     = 'parent';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Role helpers ──────────────────────────────────────────
    public function isAdmin():      bool { return $this->role === self::ROLE_ADMIN; }
    public function isAccountant(): bool { return $this->role === self::ROLE_ACCOUNTANT; }
    public function isTeacher():    bool { return $this->role === self::ROLE_TEACHER; }
    public function isStudent():    bool { return $this->role === self::ROLE_STUDENT; }
    public function isParent():     bool { return $this->role === self::ROLE_PARENT; }

    /**
     * Whether this role has any reach into fee/voucher/financial routes at
     * all. Admin reaches the shared voucher/installment-generation routes
     * from their own EMS session; accountant reaches the full Accounts
     * module from their own separate Accounts session. Neither ever
     * "becomes" the other — see routes/web.php for the guard split.
     */
    public function canAccessAccounts(): bool
    {
        return $this->isAdmin() || $this->isAccountant();
    }

    /** Only admin (and teacher/student/parent, each scoped to their own area) use the EMS ('web') guard. */
    public function canAccessEms(): bool
    {
        return $this->isAdmin();
    }

    // ── Relationships ──────────────────────────────────────────
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentModel::class);
    }

    // ── Dashboard redirect helper ──────────────────────────────
    // EMS (admin/teacher/student/parent) and Accounts (accountant) are now
    // two separately-authenticated systems, each with exactly one home
    // screen for the account that just signed in — there is no "intent"
    // to steer between them anymore, since a single login no longer grants
    // both. Admin's read-only financial oversight lives inside the EMS
    // admin dashboard itself (see Admin\AdminDashboardController), so the
    // admin role never needs to resolve to the Accounts dashboard here.
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN      => 'admin.dashboard',
            self::ROLE_ACCOUNTANT => 'accounts.dashboard',
            self::ROLE_TEACHER    => 'teacher.dashboard',
            self::ROLE_STUDENT    => 'student.dashboard',
            self::ROLE_PARENT     => 'parent.dashboard',
            default               => 'admin.dashboard',
        };
    }
}
