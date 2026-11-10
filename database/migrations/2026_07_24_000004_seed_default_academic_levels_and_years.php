<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        $now = now();

        DB::table('academic_levels')->insert([
            [
                'name' => 'College', 'slug' => 'college',
                'requires_institution' => false, 'sort_order' => 1,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'University', 'slug' => 'university',
                'requires_institution' => true, 'sort_order' => 2,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $currentLabel = now()->year . '-' . (now()->year + 1);
        $nextLabel    = (now()->year + 1) . '-' . (now()->year + 2);

        DB::table('academic_years')->insert([
            [
                'name' => $currentLabel, 'is_default' => true, 'sort_order' => 1,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => $nextLabel, 'is_default' => false, 'sort_order' => 2,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        DB::table('academic_levels')->whereIn('slug', ['college', 'university'])->delete();
        $currentLabel = now()->year . '-' . (now()->year + 1);
        $nextLabel    = (now()->year + 1) . '-' . (now()->year + 2);
        DB::table('academic_years')->whereIn('name', [$currentLabel, $nextLabel])->delete();
    }
};
