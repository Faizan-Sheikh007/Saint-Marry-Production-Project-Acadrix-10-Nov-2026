<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('daily_ledger_closings', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->unique();  // one cash-count closing per day
            $table->json('denominations');         // {"5000":0,"1000":2,"500":10,...,"note_count":1}
            $table->decimal('counted_total', 12, 2); // total from the note/coin count
            $table->decimal('expected_total', 12, 2)->nullable(); // system-computed closing balance at save time
            $table->decimal('difference', 12, 2)->default(0);     // counted - expected
            $table->foreignId('closed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('daily_ledger_closings'); }
};
