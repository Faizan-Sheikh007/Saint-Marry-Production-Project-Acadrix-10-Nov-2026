<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transport_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('cnic')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->date('joined_date')->nullable();
            $table->enum('status', ['active', 'banned'])->default('active');
            $table->text('ban_reason')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('transport_drivers'); }
};
