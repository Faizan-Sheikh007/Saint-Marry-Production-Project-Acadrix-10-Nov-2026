<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentStatusObserver
{
    public function updated(Student $student): void
    {
        if (! $student->wasChanged('status') || $student->isActiveEnrollment() || ! $student->user_id) return;
        $user = $student->user()->first();
        if (! $user || ! $user->isStudent()) return;

        // Revoke remembered logins; passwords and the account itself are retained.
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        // File/Redis sessions are rejected on their very next request by middleware.
    }
}
