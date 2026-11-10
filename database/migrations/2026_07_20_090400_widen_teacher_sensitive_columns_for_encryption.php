<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * teachers.cnic and teachers.bank_account_number were plain VARCHAR
     * columns. Now that they're encrypted with an RSA+AES envelope
     * (App\Casts\RsaEncrypted) the stored value is much longer than the
     * original number, so the columns need to be widened to TEXT.
     * A plain MODIFY (no doctrine/dbal needed) is enough on MySQL.
     */
    public function up()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `teachers` MODIFY `cnic` TEXT NULL');
            DB::statement('ALTER TABLE `teachers` MODIFY `bank_account_number` TEXT NULL');
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `teachers` MODIFY `cnic` VARCHAR(255) NULL');
            DB::statement('ALTER TABLE `teachers` MODIFY `bank_account_number` VARCHAR(255) NULL');
        }
    }
};
