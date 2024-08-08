<?php

namespace App\Database\Constants;

class UserCol
{
    const ID = 'id';
    const CID = 'cid';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const LICENSE_KEY = 'license_key';
    const IS_ADMIN = 'is_admin';
    const IS_DISABLED = 'is_disabled';
    const IS_PREMIUM = 'is_premium';
    const REMEMBER_TOKEN = 'remember_token';

    private function __construct()
    {
    }
}
