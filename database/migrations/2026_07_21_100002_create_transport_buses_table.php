<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('transport_buses', function (Blueprint $table) {
            $table->id();
            $table->string('bus_number');
            $table->string('vehicle_number');
            $table->string('model')->nullable();
            $table->integer('capacity')->default(40);
            $table->foreignId('transport_driver_id')->nullable()->constrained('transport_drivers')->nullOnDelete();
            $table->foreignId('transport_route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('transport_buses'); }
};
