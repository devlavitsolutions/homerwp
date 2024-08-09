<?php

namespace App\Http\Constants;

class Field
{
    public const EMAIL = 'email';
    public const FREE_TOKENS = 'freeTokensRemainingThisMonth';
    public const IS_ADMIN = 'isAdmin';
    public const IS_DISABLED = 'isDisabled';
    public const MESSAGE = 'message';
    public const PAID_TOKENS = 'paidTokens';
    public const PASSWORD = 'password';
    public const WEBSITES = 'websites';

    private function __construct() {}
}
