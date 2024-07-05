<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Seed the users table.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'email' => 'stefan.jankovic@lavitsolutions.com',
            'password' => bcrypt('seedpassword'),
            'is_admin' => true,
        ]);
    }
}
