<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active','inactive','suspended','graduated','dropped','transferred','terminated') NOT NULL DEFAULT 'active'");
    }

    public function down()
    {
        // Any 'terminated' rows fall back to 'dropped' so the column can be narrowed again.
        DB::statement("UPDATE students SET status = 'dropped' WHERE status = 'terminated'");
        DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM('active','inactive','suspended','graduated','dropped','transferred') NOT NULL DEFAULT 'active'");
    }
};
