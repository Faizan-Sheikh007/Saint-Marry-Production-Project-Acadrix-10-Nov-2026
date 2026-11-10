<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('class_subject', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('subject_id')
                  ->constrained('teachers')->nullOnDelete();
        });
    }
    public function down() {
        Schema::table('class_subject', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
