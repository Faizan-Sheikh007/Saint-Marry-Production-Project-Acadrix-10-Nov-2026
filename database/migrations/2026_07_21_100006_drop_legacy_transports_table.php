<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // Superseded by transport_drivers / transport_routes / transport_buses /
        // transport_requests / transport_challans / transport_complaints.
        Schema::dropIfExists('transports');
    }
    public function down() {
        Schema::create('transports', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('route')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->integer('capacity')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
};
