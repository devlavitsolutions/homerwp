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
    public static function USER_BY_ID(int $userId)
    {
        return self::USERS . "/{$userId}";
    }
    public static function USER_EMAIL(int $userId)
    {
        return self::USER_BY_ID($userId) . '/email';
    }
    public static function USER_TOKENS(int $userId)
    {
        return self::USER_BY_ID($userId) . '/tokens-count';
    }
    public static function USER_LICENSE_KEY(int $userId)
    {
        return self::USER_BY_ID($userId) . '/license-key';
    }
    public static function USER_IS_DISABLED(int $userId)
    {
        return self::USER_BY_ID($userId) . '/is-disabled';
    }
    public static function USER_IS_ADMIN(int $userId)
    {
        return self::USER_BY_ID($userId) . '/is-admin';
    }
    public static function USER_PASSWORD(int $userId)
    {
        return self::USER_BY_ID($userId) . '/password';
    }
}
