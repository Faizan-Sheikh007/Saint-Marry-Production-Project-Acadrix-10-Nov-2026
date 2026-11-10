<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->foreignId('graded_by')->nullable()->after('student_id')
                  ->constrained('teachers')->nullOnDelete();
        });
    }
    public function down() {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
        });
    }
};
