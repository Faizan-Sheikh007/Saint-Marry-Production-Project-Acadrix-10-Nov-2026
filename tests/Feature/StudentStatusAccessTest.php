<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckOriByteLicense;
use App\Models\{Student, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Hash, Route, Schema};
use Tests\TestCase;

/**
 * Run only on an installed development/staging application with pdo_sqlite.
 * Uses a deliberately minimal IN-MEMORY schema, never the institution database.
 * Tests real HTTP middleware/auth/role/model bindings, not the whole fee module.
 */
class StudentStatusAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite required; no live database fallback is allowed.');
        }
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('session.driver', 'array');
        config()->set('cache.default', 'array');
        DB::purge('sqlite');
        $this->withoutMiddleware(CheckOriByteLicense::class);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->unique();
            $t->string('password'); $t->string('role'); $t->rememberToken(); $t->timestamps();
        });
        Schema::create('students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('student_id')->unique(); $t->string('name'); $t->string('status');
            $t->decimal('credit_balance', 12, 2)->default(5000);
            $t->string('fee_status')->default('paid'); $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('action');
            $t->string('subject_type'); $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('description'); $t->timestamps();
        });
        Route::middleware(['web', 'auth:web,accounts', 'role:admin,accountant'])
            ->post('/_status-test/students/{student}/change', function (Student $student) {
                $student->update(['name' => 'Changed']);
                return response()->json(['changed' => true]);
            })->name('status-test.change');
        Route::middleware(['web', 'auth:web,accounts', 'role:admin,accountant'])
            ->get('/_status-test/students/{student}', function (Student $student) {
                return response()->json(['status' => $student->status, 'name' => $student->name]);
            })->name('status-test.show');
        Route::middleware(['web', 'auth:web', 'role:student'])
            ->get('/_status-test/portal', fn () => response()->json(['ok' => true]))
            ->name('status-test.portal');
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Fixture '.$role, 'email' => $role.'@example.invalid',
            'password' => Hash::make('FixturePassword123!'), 'role' => $role]);
    }

    private function pupil(string $status, ?User $user = null): Student
    {
        return Student::forceCreate(['student_id' => 'FIXTURE-1', 'name' => 'Fixture Student',
            'status' => $status, 'user_id' => $user?->id, 'credit_balance' => 5000, 'fee_status' => 'paid']);
    }

    public function test_left_profile_is_read_only_and_direct_write_preserves_record(): void
    {
        $student = $this->pupil('left');
        $this->actingAs($this->user('admin'), 'web');
        $this->getJson(route('status-test.show', $student))->assertOk()->assertJsonPath('status', 'left');
        $this->postJson(route('status-test.change', $student))->assertStatus(423)
            ->assertJsonPath('student_status', 'left')->assertJsonPath('profile_locked', true);
        $this->assertSame('Fixture Student', $student->fresh()->name);
        $this->assertSame('5000.00', $student->fresh()->credit_balance);
        $this->assertSame('paid', $student->fresh()->fee_status);
    }

    public function test_reactivation_is_explicit_and_restores_editing(): void
    {
        $student = $this->pupil('left');
        $this->actingAs($this->user('admin'), 'web');
        $this->post(route('students.update-status', $student), ['status' => 'active'])->assertRedirect();
        $this->assertSame('active', $student->fresh()->status);
        $this->postJson(route('status-test.change', $student))->assertOk();
        $this->assertSame('Changed', $student->fresh()->name);
        $this->assertSame('5000.00', $student->fresh()->credit_balance);
    }

    public function test_accountant_cannot_reactivate(): void
    {
        $student = $this->pupil('left');
        $this->actingAs($this->user('accountant'), 'web');
        $this->postJson(route('students.update-status', $student), ['status' => 'active'])->assertForbidden();
        $this->assertSame('left', $student->fresh()->status);
    }

    public function test_existing_student_session_is_revoked_on_next_request(): void
    {
        $user = $this->user('student');
        $student = $this->pupil('active', $user);
        $this->actingAs($user, 'web');
        $this->getJson(route('status-test.portal'))->assertOk();
        $student->adminFill(['status' => 'left'])->save();
        $this->getJson(route('status-test.portal'))->assertForbidden()->assertJsonPath('student_status', 'left');
        $this->assertGuest('web');
    }

    public function test_correct_password_does_not_bypass_left_status(): void
    {
        $user = $this->user('student'); $this->pupil('left', $user);
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'FixturePassword123!', 'remember' => 1])
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_transition_revokes_remember_token_without_resetting_password(): void
    {
        $user = $this->user('student'); $user->forceFill(['remember_token' => 'old-token'])->save();
        $student = $this->pupil('active', $user); $password = $user->password;
        $this->actingAs($this->user('admin'), 'web');
        $this->post(route('students.update-status', $student), ['status' => 'left'])->assertRedirect();
        $this->assertSame('left', $student->fresh()->status);
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('5000.00', $student->fresh()->credit_balance);
        $this->assertDatabaseHas('audit_logs', ['subject_type' => 'Student', 'subject_id' => $student->id]);
    }
}
