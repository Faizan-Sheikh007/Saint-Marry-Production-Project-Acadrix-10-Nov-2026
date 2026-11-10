<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed replacement for the old free-text "Session" input on the
 * admission form (e.g. "2025-2026"). Admin adds/retires years from the
 * sidebar; `is_default` controls which one is pre-selected on new
 * admissions.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            // e.g. "2025-2026"
            $table->string('name')->unique();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academic_years');
    }
};
