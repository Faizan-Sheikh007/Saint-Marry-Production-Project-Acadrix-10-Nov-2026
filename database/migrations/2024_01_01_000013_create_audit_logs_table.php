<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, paid, etc.
            $table->string('subject_type'); // Student, Voucher, FeeHead...
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('audit_logs'); }
};
