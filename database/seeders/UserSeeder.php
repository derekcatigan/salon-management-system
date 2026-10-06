<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'admin@email.com',
            'role' => RoleEnum::Admin->value,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'email' => 'cashier@email.com',
            'role' => RoleEnum::Cashier->value,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        User::create([
            'email' => 'staff@email.com',
            'role' => RoleEnum::Staff->value,
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
    }
}
