<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('daily_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');                    // the ledger day this entry belongs to
            $table->enum('type', ['in', 'out', 'expense']); // In / Out / Expense
            $table->string('particulars');                  // description of the entry
            $table->string('category')->nullable();         // e.g. "Stationery", "Utility Bill" (mainly for expense/out)
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // accountant who made the entry
            // Entries are permanent — never edited or deleted once saved.
            // If an entry turns out to be wrong, the accountant annotates it with a remark instead.
            $table->text('remarks')->nullable();
            $table->foreignId('remarks_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('remarks_at')->nullable();
            // Snapshot of when this day's ledger was locked (12 hours after the day ended).
            // Kept here too (in addition to being computed on the fly) so a locked day's
            // exact lock time is preserved even if server time zone/config changes later.
            $table->dateTime('locked_at')->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'type']);
        });
    }
    public function down() { Schema::dropIfExists('daily_ledger_entries'); }
};
