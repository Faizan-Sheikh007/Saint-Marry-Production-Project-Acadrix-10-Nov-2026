<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Each collection is one receipt, even when distributed to several vouchers.
            // Historic allocations remain NULL: there is no reliable way to infer their batches.
            $table->uuid('receipt_batch_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['receipt_batch_id']);
            $table->dropColumn('receipt_batch_id');
        });
    }
};
