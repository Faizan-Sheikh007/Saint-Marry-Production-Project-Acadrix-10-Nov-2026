<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('hostels', function (Blueprint $table) {
            $table->string('warden_name')->nullable()->after('type');
            $table->string('warden_phone')->nullable()->after('warden_name');
            $table->time('opening_time')->nullable()->after('warden_phone');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->decimal('breakfast_charge', 10, 2)->default(0)->after('closing_time');
            $table->decimal('lunch_charge', 10, 2)->default(0)->after('breakfast_charge');
            $table->decimal('dinner_charge', 10, 2)->default(0)->after('lunch_charge');
            $table->decimal('misc_charge', 10, 2)->default(0)->after('dinner_charge');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('description');
        });

        // These are superseded by hostel_rooms (per-room capacity/occupancy) and
        // the misc/food charge columns above.
        Schema::table('hostels', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'occupied', 'fee', 'warden']);
        });
    }

    public function down() {
        Schema::table('hostels', function (Blueprint $table) {
            $table->integer('capacity')->default(10);
            $table->integer('occupied')->default(0);
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('warden')->nullable();
            $table->dropColumn([
                'warden_name', 'warden_phone', 'opening_time', 'closing_time',
                'breakfast_charge', 'lunch_charge', 'dinner_charge', 'misc_charge', 'status',
            ]);
        });
    }
};
