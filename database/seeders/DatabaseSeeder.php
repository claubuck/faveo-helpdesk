<?php

namespace Database\Seeders;

use Database\Seeders\v_2_0_0\DatabaseSeeder as V200DatabaseSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Call the version-specific seeder
        $this->call(V200DatabaseSeeder::class);
    }
} 