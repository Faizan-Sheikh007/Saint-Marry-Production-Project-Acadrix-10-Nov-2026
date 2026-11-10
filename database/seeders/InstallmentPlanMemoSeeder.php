<?php

namespace Database\Seeders;

use App\Models\TeacherAnnouncement;
use App\Models\User;
use App\Services\NotifierService;
use Illuminate\Database\Seeder;

/**
 * Run on demand whenever admin needs to push this memo out:
 *   php artisan db:seed --class=InstallmentPlanMemoSeeder
 *
 * Posts to the same teacher-announcement board used by admin ▸ Announcements
 * (visible on every teacher's dashboard), and pings each teacher's
 * notification bell. Edit the $title/$content below before running if the
 * wording or dates need to change for this term.
 */
class InstallmentPlanMemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $title = 'Update: Installment Plan Change & Upcoming Fee Due Dates';

        $content = "Please note the following changes to the fee installment plan for students in your program, "
            . "effective this term:\n\n"
            . "• The installment schedule has been revised — please check each student's updated fee plan before "
            . "collecting or discussing dues with parents.\n"
            . "• The next installment/upcoming fee is due shortly. Kindly remind your students in class and flag "
            . "any hardship cases to the office.\n"
            . "• For exact per-student amounts and due dates, use Student ▸ Fee Summary from your dashboard.\n\n"
            . "Contact the accounts office for any questions about a specific student's plan.";

        $announcement = TeacherAnnouncement::updateOrCreate(
            ['title' => $title, 'audience' => 'all'],
            [
                'content'    => $content,
                'created_by' => $admin?->id,
                'teacher_id' => null,
                'pinned'     => true,
            ]
        );

        User::where('role', 'teacher')->pluck('id')->each(
            fn ($id) => NotifierService::notify($id, 'New announcement: ' . $announcement->title, 'fa-bullhorn', 'yellow')
        );

        $this->command?->info('Installment plan memo posted to all teachers.');
    }
}
