<?php

namespace Database\Seeders;

use App\Helpers\Generators;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Seed the users table.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'email' => 'stefan.jankovic@lavitsolutions.com',
            'password' => Generators::encryptPassword('seedpassword'),
            'is_admin' => true,
        ]);
    }
}
