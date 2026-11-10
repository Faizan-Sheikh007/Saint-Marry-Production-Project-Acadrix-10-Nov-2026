<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShortenExistingPortalLogins extends Command
{
    protected $signature = 'portal:shorten-logins {--apply : Actually update the database (otherwise preview only)}';

    protected $description = 'Shorten previously issued student and parent portal login emails without changing passwords or contact emails.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $changes = [];
        $reserved = [];

        // Reserve every existing address, including unrelated accounts.
        User::query()->select(['id', 'email'])->orderBy('id')->chunkById(500, function ($users) use (&$reserved) {
            foreach ($users as $user) {
                $reserved[strtolower($user->email)] = (int) $user->id;
            }
        });

        User::query()->whereIn('role', [User::ROLE_STUDENT, User::ROLE_PARENT])
            ->select(['id', 'email', 'role'])->orderBy('id')
            ->chunkById(500, function ($users) use (&$changes, &$reserved) {
                foreach ($users as $user) {
                    $old = (string) $user->email;
                    $prefix = $user->role === User::ROLE_STUDENT ? 'stu' : 'prnt';
                    // Only touch generated logins, never personal addresses.
                    if (!preg_match('/^' . $prefix . '-([0-9]{4})(?:-[a-z0-9-]+)?@[^@]+$/i', $old, $match)) {
                        continue;
                    }
                    $new = $prefix . '-' . $match[1] . '@marians.edu.pk';
                    if (strcasecmp($old, $new) === 0) {
                        continue;
                    }
                    // If another account has the desired ID, assign a free four-digit ID.
                    if (isset($reserved[strtolower($new)]) && $reserved[strtolower($new)] !== (int) $user->id) {
                        $found = false;
                        for ($n = 1000; $n <= 9999; $n++) {
                            $candidate = $prefix . '-' . $n . '@marians.edu.pk';
                            if (!isset($reserved[strtolower($candidate)])) {
                                $new = $candidate;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $this->error("No available {$prefix} login for user #{$user->id}; aborted.");
                            throw new \RuntimeException('Exhausted available four-digit portal login IDs.');
                        }
                    }
                    unset($reserved[strtolower($old)]);
                    $reserved[strtolower($new)] = (int) $user->id;
                    $changes[] = ['id' => (int) $user->id, 'old' => $old, 'new' => $new];
                }
            });

        $this->info(count($changes) . ' generated login(s) eligible for update.');
        foreach (array_slice($changes, 0, 20) as $change) {
            $this->line("#{$change['id']}: {$change['old']} -> {$change['new']}");
        }
        if (count($changes) > 20) {
            $this->line('Preview shows first 20 changes only.');
        }
        if (!$apply) {
            $this->warn('Preview only. Run php artisan portal:shorten-logins --apply to update.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $change) {
                User::query()->whereKey($change['id'])->update(['email' => $change['new']]);
            }
        });

        $this->info('Updated ' . count($changes) . ' portal login(s). Passwords were not changed.');
        return self::SUCCESS;
    }
}
