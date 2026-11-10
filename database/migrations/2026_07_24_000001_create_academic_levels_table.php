<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed replacement for the old hardcoded College/University radio
 * on the admission form. Admin can add, rename, reorder, or deactivate
 * levels from the sidebar instead of it being fixed in code.
 *
 * `requires_institution` drives the admission-form flow: when the selected
 * level has this set (seeded true for "University"), the form shows the
 * Institution dropdown and filters Programs down to that Institution.
 */
return new class extends Migration {
    public function up()
    {
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Stable machine key mirrored into students.level for backward
            // compatibility with existing code that reads that string column.
            $table->string('slug')->unique();
            $table->boolean('requires_institution')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academic_levels');
    }
};
