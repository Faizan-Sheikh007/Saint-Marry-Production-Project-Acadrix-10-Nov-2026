<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active','inactive','suspended','graduated','dropped','transferred','terminated','left') NOT NULL DEFAULT 'active'");
    }
    public function down(): void {
        DB::table('students')->where('status', 'left')->update(['status'=>'inactive']);
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active','inactive','suspended','graduated','dropped','transferred','terminated') NOT NULL DEFAULT 'active'");
    }
};
