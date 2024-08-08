<?php

namespace Database\Seeders;

use App\Constants\Defaults;
use App\Database\Constants\Table;
use App\Database\Constants\UserCol;
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
        DB::table(Table::USERS)->insert([
            UserCol::EMAIL => Defaults::SEED_EMAIL,
            UserCol::PASSWORD => Generators::encryptPassword(Defaults::SEED_PASSWORD),
            UserCol::IS_ADMIN => true,
            UserCol::LICENSE_KEY => Generators::generateLicenseKey(),
        ]);
    }
}
