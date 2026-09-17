<?php

namespace Database\Seeders;

use App\Models\SupportRequest;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SupportRequest::factory()->count(20)->create();
    }
}
