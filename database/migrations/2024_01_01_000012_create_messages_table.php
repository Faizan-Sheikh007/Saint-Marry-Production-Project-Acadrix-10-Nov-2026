<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('message');
            $table->enum('type', ['announcement','notice','sms','email'])->default('announcement');
            $table->enum('audience', ['all','students','teachers','parents'])->default('all');
            $table->enum('status', ['draft','sent'])->default('draft');
            $table->boolean('read')->default(false);
            $table->timestamp('sent_at')->nullable(); $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('messages'); }
};
