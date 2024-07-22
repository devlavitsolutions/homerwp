<?php

namespace Database\Seeders;

use App\Constants\Persist;
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
        DB::table(Persist::USERS)->insert([
            Persist::EMAIL => Persist::SEED_EMAIL,
            Persist::PASSWORD => Generators::encryptPassword(Persist::SEED_PASSWORD),
            Persist::IS_ADMIN => true,
            Persist::LICENSE_KEY => Generators::generateLicenseKey(),
        ]);
    }
}
