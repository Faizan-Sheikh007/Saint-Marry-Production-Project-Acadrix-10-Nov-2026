<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transport_complaints', function (Blueprint $table) {
            $table->id();
            // Polymorphic: the student or teacher who filed the complaint.
            $table->string('complainant_type');
            $table->unsignedBigInteger('complainant_id');
            $table->foreignId('transport_bus_id')->nullable()->constrained('transport_buses')->nullOnDelete();
            $table->foreignId('transport_driver_id')->nullable()->constrained('transport_drivers')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'in_review', 'resolved', 'dismissed'])->default('open');
            $table->text('admin_response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['complainant_type', 'complainant_id']);
        });
    }
    public function down() { Schema::dropIfExists('transport_complaints'); }
};
