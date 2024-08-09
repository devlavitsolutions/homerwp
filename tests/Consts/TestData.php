<?php

namespace Tests\Consts;

class TestData
{
    public const ADD_TOKEN_COUNT = 7;
    public const BAD_EMAIL = 'no_monkey_tail.com';
    public const DB_SEED = 'db:seed';
    public const DELETE = 'DELETE';
    public const FREE_MONTHLY_TOKENS = 4;
    public const GET = 'GET';
    public const HEAD = 'HEAD';
    public const NEGATIVE_TOKEN_COUNT = -1;
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    public const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';
    public const SET_TOKEN_COUNT = 25;
    public const SHORT_PASSWORD = 'pwd';
    public const USER1_EMAIL = 'user1@testing.com';
    public const USER1_PASSWORD = 'user1_!@#$';
    public const USER2_EMAIL = 'user2@testing.com';
    public const USER2_PASSWORD = 'user2_p@s5';

    private function __construct() {}

    public static function INDEXED_USER(int $index)
    {
        return "indexed_user_{$index}@testing.com";
    }
}
