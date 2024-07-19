<?php

namespace App\Constants;

class Persist
{
    const ID = 'id';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const LICENSE_KEY = 'license_key';
    const IS_ADMIN = 'is_admin';
    const IS_DISABLED = 'is_disabled';
    const CID = 'cid';
    const REMEMBER_TOKEN = 'remember_token';
    const USERS = 'users';
    const USER_ID = 'user_id';
    const FREE_TOKENS = 'free_tokens_used';
    const PAID_TOKENS = 'paid_tokens';
    const LAST_USED = 'last_used';
    const KEYWORDS = 'keywords';
    const WEBSITE = 'website';
    const RESPONSE = 'response';

    const VALIDATE_REQUIRED = 'required';
    const VALIDATE_ID = 'required|numeric|exists:users,id';
    const VALIDATE_EMAIL = 'required|email:rfc,dns|unique:users,email';
    const VALIDATE_PASSWORD = 'required|string|min:8';
    const VALIDATE_PAID_TOKENS = 'required|numeric|gte:0';

    const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';
}
