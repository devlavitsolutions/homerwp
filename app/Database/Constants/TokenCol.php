<?php

namespace App\Database\Constants;

class TokenCol
{
    public const FREE_TOKENS = 'free_tokens_used';
    public const ID = 'id';
    public const LAST_USED = 'last_used';
    public const LICENSE_KEY = 'license_key';
    public const PAID_TOKENS = 'paid_tokens';
    public const USER_ID = 'user_id';

    private function __construct() {}
}
