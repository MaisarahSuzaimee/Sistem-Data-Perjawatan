<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'name' => 'Superadmin',
            'email' => 'superadmin@gmail.com',
            'ptj_id' => 13,
            'nokp' => '020822090278',
            'phone_number' => '01161404157',
            'password' => Hash::make('superadmin'),
            'status' => 1,
            'role' => 1 //superadmin

        ]);

        User::create([
            'name' => 'User',
            'email' => 'usern@gmail.com',
            'ptj_id' => 4,
            'nokp' => '020822090279',
            'phone_number' => '01161404159',
            'password' => Hash::make('user'),
            'status' => 1,
            'role' => 3 //user

        ]);
    }
}
