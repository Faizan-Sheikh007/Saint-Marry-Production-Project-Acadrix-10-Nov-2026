<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        $now = now();

        DB::table('grading_scales')->insert([
            ['grade' => 'A+', 'min_percentage' => 90, 'max_percentage' => null, 'remarks' => 'Excellent', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['grade' => 'A',  'min_percentage' => 80, 'max_percentage' => 89.99, 'remarks' => 'Very Good', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['grade' => 'B',  'min_percentage' => 70, 'max_percentage' => 79.99, 'remarks' => 'Good', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['grade' => 'C',  'min_percentage' => 60, 'max_percentage' => 69.99, 'remarks' => 'Satisfactory', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['grade' => 'D',  'min_percentage' => 50, 'max_percentage' => 59.99, 'remarks' => 'Pass', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['grade' => 'F',  'min_percentage' => 0,  'max_percentage' => 49.99, 'remarks' => 'Fail', 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        DB::table('grading_scales')->whereIn('grade', ['A+', 'A', 'B', 'C', 'D', 'F'])->delete();
    }
};
