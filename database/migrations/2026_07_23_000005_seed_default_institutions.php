<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        $now = now();

        DB::table('institutions')->insert([
            [
                'name'       => 'Punjab Medical Facility',
                'disclaimer' => 'Admission to Punjab Medical Facility affiliated programs is subject to verification of academic '
                    . 'records and merit criteria set by the institution. Fees once paid are non-refundable except as provided '
                    . 'by policy. The institution reserves the right to update this disclaimer at any time.',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'Government College University Faisalabad',
                'disclaimer' => 'Admission to Government College University Faisalabad affiliated programs is provisional until '
                    . 'documents are verified by the university. Students are bound by GCUF\'s code of conduct and examination '
                    . 'rules. The institution reserves the right to update this disclaimer at any time.',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        DB::table('institutions')->whereIn('name', [
            'Punjab Medical Facility',
            'Government College University Faisalabad',
        ])->delete();
    }
};
