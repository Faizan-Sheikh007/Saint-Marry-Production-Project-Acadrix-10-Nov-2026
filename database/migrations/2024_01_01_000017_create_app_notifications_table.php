<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id(); $table->text('message'); $table->string('icon')->default('fas fa-bell');
            $table->string('color')->default('primary'); $table->boolean('read')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('app_notifications'); }
};
