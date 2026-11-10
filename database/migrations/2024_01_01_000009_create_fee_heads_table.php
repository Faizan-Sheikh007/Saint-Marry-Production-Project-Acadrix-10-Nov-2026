<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Tuition Fee, Admission Fee, Exam Fee...
            $table->enum('category', [
                'tuition','admission','exam','library','lab','transport','hostel',
                'thesis','late_registration','degree_transcript','id_card','misc',
            ])->default('misc');
            $table->enum('type', ['one_time','recurring'])->default('recurring');
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('fee_heads'); }
};
