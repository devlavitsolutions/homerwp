<?php

namespace Tests\Consts;

class TestApiEndpoints
{
    const BASE_URL = '/api/';
    const LOGIN = self::BASE_URL . 'login';
    const REGISTER = self::BASE_URL . 'register';
    const LOGOUT = self::BASE_URL . 'logout';
    const USERS = self::BASE_URL . 'users';
    public static function USERS_BY_PAGE(int $pageIndex)
    {
        return self::USERS . "?page={$pageIndex}";
    }
    public static function USER_BY_IDENTIFICATOR(int|string $userIdentificator)
    {
        return self::USERS . "/{$userIdentificator}";
    }
    public static function USER_EMAIL(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/email';
    }
    public static function USER_TOKENS(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/tokensCount';
    }
    public static function USER_LICENSE_KEY(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/licenseKey';
    }
    public static function USER_IS_DISABLED(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/isDisabled';
    }
    public static function USER_IS_ADMIN(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/isAdmin';
    }
    public static function USER_PASSWORD(int|string $userIdentificator)
    {
        return self::USER_BY_IDENTIFICATOR($userIdentificator) . '/password';
    }
}
