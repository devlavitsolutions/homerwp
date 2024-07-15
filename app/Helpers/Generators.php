<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Generators {
    static function generateLicenseKey() {
        return Str::uuid()->toString();
    }

    static function encryptPassword(string $password) {
        return bcrypt($password);
    }

    static function checkPassword(string $password, string $encryptedPassword) {
        return Hash::check($password, $encryptedPassword);
    }
}