<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('books', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('rack')->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('program_id')->constrained('school_classes')->nullOnDelete();
            $table->string('industry')->nullable()->after('school_class_id');
        });
    }
    public function down() {
        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropColumn('industry');
        });
    }
};
