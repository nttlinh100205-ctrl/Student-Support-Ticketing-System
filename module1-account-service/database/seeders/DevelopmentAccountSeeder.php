<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Development accounts can only be seeded in local/testing.');
        }

        $password = config('development.account_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set DEV_ACCOUNT_PASSWORD in .env to at least 12 characters.');
        }

        $department = config('development.department_id');
        if ($department !== null && $department !== '' && filter_var($department, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new \RuntimeException('DEV_DEPARTMENT_ID must be a positive department ID from module 2.');
        }

        $accounts = [
            ['email' => 'admin@support.test', 'name' => 'Local Admin', 'role' => 'ADMIN', 'department_id' => null],
            ['email' => 'student@support.test', 'name' => 'Local Student', 'role' => 'STUDENT', 'department_id' => null],
        ];
        if ($department) {
            $accounts[] = ['email' => 'staff@support.test', 'name' => 'Local Staff', 'role' => 'STAFF', 'department_id' => (int) $department];
            $accounts[] = ['email' => 'head@support.test', 'name' => 'Local Department Head', 'role' => 'DEPARTMENT_HEAD', 'department_id' => (int) $department];
        }

        DB::transaction(function () use ($accounts, $password) {
            foreach ($accounts as $account) {
                $user = User::firstOrCreate(['email' => $account['email']], array_merge($account, [
                    'password' => $password,
                    'status' => 'ACTIVE',
                ]));
                $this->command?->info($account['email'].($user->wasRecentlyCreated ? ' created.' : ' already exists; unchanged.'));
            }
        });

        if (! $department) {
            $this->command?->warn('Staff/head skipped. Set DEV_DEPARTMENT_ID from module 2 and seed again when ready.');
        }
    }
}
