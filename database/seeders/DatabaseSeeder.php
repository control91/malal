<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Production-safe base content. Example data: php artisan app:seed-demo */
    public function run(): void
    {
        $this->call(SiteContentSeeder::class);
    }
}
