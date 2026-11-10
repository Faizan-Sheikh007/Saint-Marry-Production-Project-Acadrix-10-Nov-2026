<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('computer_lab_inventories', function (Blueprint $table) {
            // Item type — lets computer lab entries be marked as Server hardware
            // (vs. Workstation/Peripheral/Networking), each with its own free-text
            // specification block (CPU/RAM/storage for workstations, OS/role/IP for servers).
            $table->string('item_type')->default('workstation')->after('category'); // workstation, server, peripheral, networking, other
            $table->text('specification')->nullable()->after('description');

            // Server-specific detail, only meaningful when item_type = server.
            $table->string('server_role')->nullable()->after('specification');   // e.g. File Server, Domain Controller, Web Server
            $table->string('operating_system')->nullable()->after('server_role');
            $table->string('ip_address')->nullable()->after('operating_system');
        });
    }

    public function down()
    {
        Schema::table('computer_lab_inventories', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'specification', 'server_role', 'operating_system', 'ip_address']);
        });
    }
};
