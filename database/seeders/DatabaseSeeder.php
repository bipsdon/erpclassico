<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Order matters — CapacityConfigSeeder and OrderSeeder depend on users existing.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CapacityConfigSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
