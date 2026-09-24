<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            SettingSeeder::class,
        ]);

        if (config('service-contract.seeder_faker')) {
            User::factory()->count(20)->create();
        }
    }
}
