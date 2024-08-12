<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Generators
{
    public static function checkPassword(string $password, string $encryptedPassword)
    {
        return Hash::check($password, $encryptedPassword);
    }

    public static function encryptPassword(string $password)
    {
        return bcrypt($password);
    }

    public static function generateLicenseKey()
    {
        return Str::uuid()->toString();
    }
}
