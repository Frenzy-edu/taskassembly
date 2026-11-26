<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Task relies on model events (static::creating/saving) to generate its slug and position,
    // so WithoutModelEvents must not be used here or seeded tasks fail a NOT NULL constraint.
    public function run(): void
    {
        $this->call(TaskSeeder::class);
    }
}
