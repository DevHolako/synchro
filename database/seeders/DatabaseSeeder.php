<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@synchro.isga.ma'],
            [
                'name' => 'Admin Synchro',
                'password' => 'password',
                'role' => UserRole::Administrator,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'coordinator@synchro.isga.ma'],
            [
                'name' => 'Coordinator Synchro',
                'password' => 'password',
                'role' => UserRole::Coordinator,
                'email_verified_at' => now(),
            ]
        );

        $this->call(ReferentialsSeeder::class);
        $this->call(AcademicStructureSeeder::class);
        $this->call(ModuleSeeder::class);
        $this->call(UserProfileSeeder::class);
    }
}
