<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the Transport, Hostel, Payroll (incl. salary fines/deliveries),
 * Teacher Attendance, and Teacher Chat/Communication modules entirely, per
 * product decision to drop these features from the system.
 *
 * Tables are dropped in child -> parent order to respect foreign keys.
 * This migration is intentionally one-way: reversing it would mean
 * recreating a large, feature-specific schema that no longer has any
 * application code using it, so down() is a no-op.
 */
return new class extends Migration {
    public function up()
    {
        // ── Transport ───────────────────────────────────────────
        Schema::dropIfExists('transport_challans');
        Schema::dropIfExists('transport_complaints');
        Schema::dropIfExists('transport_requests');
        Schema::dropIfExists('transport_buses');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_drivers');
        Schema::dropIfExists('transports');

        // ── Hostel ──────────────────────────────────────────────
        Schema::dropIfExists('hostel_challans');
        Schema::dropIfExists('hostel_complaints');
        Schema::dropIfExists('hostel_attendances');
        Schema::dropIfExists('hostel_bookings');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');

        // ── Payroll / Salary ────────────────────────────────────
        Schema::dropIfExists('salary_deliveries');
        Schema::dropIfExists('salary_fines');
        Schema::dropIfExists('payrolls');

        // ── Teacher Attendance ──────────────────────────────────
        Schema::dropIfExists('teacher_attendances');

        // ── Teacher Chat / Communication ────────────────────────
        Schema::dropIfExists('teacher_messages');
    }

    public function down()
    {
        // Intentionally left blank — see class docblock. These modules and
        // their models/controllers/views have been removed from the
        // codebase, so there is nothing left to restore this data into.
    }
};
