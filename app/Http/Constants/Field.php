<?php

namespace App\Http\Constants;

class Field
{
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const WEBSITES = 'websites';
    const PAID_TOKENS = 'paidTokens';
    const FREE_TOKENS = 'freeTokensRemainingThisMonth';
    const IS_DISABLED = 'isDisabled';
    const IS_ADMIN = 'isAdmin';
    const MESSAGE = 'message';

    private function __construct()
    {
    }
}
