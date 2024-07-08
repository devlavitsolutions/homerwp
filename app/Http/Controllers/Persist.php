<?php

namespace App\Constants;

class Persist {
    const ID = 'id';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const LICENSE_KEY = 'license_key';
    const TOKENS_COUNT = 'tokens_count';
    const IS_ADMIN = 'is_admin';
    const IS_DISABLED = 'is_disabled';
    const REMEMBER_TOKEN = 'remember_token';

    const VALIDATE_REQUIRED = 'required';
    const VALIDATE_ID = 'required|numeric|exists:users,id';
    const VALIDATE_EMAIL = 'required|email:rfc,dns|unique:users,email';
    const VALIDATE_PASSWORD = 'required|string|min:8';
}
