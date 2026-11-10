<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A voucher used to get a brand-new voucher_no for every fee-head line
     * in an installment (Admission Fee, Exam Fee, Tuition Fee each got
     * their own number), so one installment printed with 3 different
     * voucher numbers on it. Going forward, every fee-head row belonging
     * to the same installment slot shares one voucher_no — one number per
     * installment, which is what actually gets written on the bank
     * challan / handed to the parent.
     *
     * Existing rows: still individual rows (payments.voucher_id keeps
     * pointing at the exact row it was recorded against — nothing there
     * is merged or deleted), but every row sharing a batch_id is backfilled
     * to use the batch's first voucher_no, so historical installments
     * collapse to one number too.
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('vouchers_voucher_no_unique');
            $table->index('voucher_no');
        });

        DB::table('vouchers')
            ->select('batch_id')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id')
            ->orderBy('batch_id')
            ->chunkById(200, function ($batches) {
                foreach ($batches as $batch) {
                    $rows = DB::table('vouchers')
                        ->where('batch_id', $batch->batch_id)
                        ->orderBy('id')
                        ->get(['id', 'voucher_no']);

                    if ($rows->count() < 2) continue;

                    $canonical = $rows->first()->voucher_no;

                    DB::table('vouchers')
                        ->where('batch_id', $batch->batch_id)
                        ->update(['voucher_no' => $canonical]);
                }
            }, 'batch_id');
    }

    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['voucher_no']);
            $table->unique('voucher_no');
        });
        // Note: rolling back does not restore the original per-line voucher
        // numbers that were overwritten by the backfill above — that data
        // is not preserved. Re-run the original seed/import if you need
        // the pre-consolidation numbers back.
    }
};
