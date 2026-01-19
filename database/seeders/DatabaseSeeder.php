<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * By default, this runs the DemoDataSeeder which creates a full demo budget.
     *
     * Available seeders:
     * - php artisan db:seed                           (runs DemoDataSeeder)
     * - php artisan db:seed --class=AdminUserSeeder   (creates only admin user)
     * - php artisan db:seed --class=DemoDataSeeder    (creates full demo data)
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
