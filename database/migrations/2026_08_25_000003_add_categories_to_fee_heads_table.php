<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * fee_heads.category is a MySQL ENUM, so new options need a raw
     * MODIFY COLUMN. Existing rows/values are untouched — this only
     * widens the allowed list. 'library' itself keeps its DB value;
     * only its display label changes (Library Fine -> Library Fee),
     * which is handled in App\Models\FeeHead::categories().
     */
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE fee_heads MODIFY COLUMN category ENUM(
            'tuition','admission','exam','library','lab','transport','hostel',
            'thesis','late_registration','degree_transcript','id_card',
            'sports_fund','internship_fee','mid_absent_restoration_fee',
            'supply_fee','board_verification','program_change_fee','misc'
        ) NOT NULL DEFAULT 'misc'");
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE fee_heads MODIFY COLUMN category ENUM(
            'tuition','admission','exam','library','lab','transport','hostel',
            'thesis','late_registration','degree_transcript','id_card','misc'
        ) NOT NULL DEFAULT 'misc'");
    }
};
