<?php

namespace App\Constants;

class Persist
{
    const ACTIVATIONS = 'activations';
    const ID = 'id';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const LICENSE_KEY = 'license_key';
    const IS_ADMIN = 'is_admin';
    const IS_DISABLED = 'is_disabled';
    const IS_PREMIUM = 'is_premium';
    const CID = 'cid';
    const REMEMBER_TOKEN = 'remember_token';
    const USERS = 'users';
    const USER_ID = 'user_id';
    const TOKENS = 'tokens';
    const FREE_TOKENS = 'free_tokens_used';
    const PAID_TOKENS = 'paid_tokens';
    const LAST_USED = 'last_used';
    const KEYWORDS = 'keywords';
    const WEBSITE = 'website';
    const WEBSITES = 'websites';
    const RESPONSE = 'response';
    const UPDATED_AT = 'updated_at';
    const DATA = 'data';

    const VALIDATE_REQUIRED = 'required';
    const VALIDATE_ID = 'required|numeric|exists:users,id';
    const VALIDATE_EMAIL = 'required|email:rfc,dns|unique:users,email';
    const VALIDATE_PASSWORD = 'required|string|min:8';
    const VALIDATE_PAID_TOKENS = 'required|numeric|gte:0';
    const VALIDATE_KEYWORDS = 'required|string';
    const VALIDATE_LICENSE_KEY = 'required|string';
    const VALIDATE_WEBSITE = 'required|url|unique:activations,website';
    const VALIDATE_WEBSITE_EXISTS = 'required|url|exists:activations,website';
    const VALIDATE_EXISTING_EMAIL = 'required|email:rfc,dns|exists:users,email';
    const VALIDATE_EXISTING_LICENSE_KEY = 'required|string|exists:users,license_key';

    const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';

    const ASC = 'asc';
    const DESC = 'desc';
}
