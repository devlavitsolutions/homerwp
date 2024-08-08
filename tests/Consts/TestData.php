<?php

namespace Tests\Consts;

class TestData
{
    const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';
    const USER1_EMAIL = 'user1@testing.com';
    const USER1_PASSWORD = 'user1_!@#$';
    const USER2_EMAIL = 'user2@testing.com';
    const USER2_PASSWORD = 'user2_p@s5';
    const BAD_EMAIL = 'no_monkey_tail.com';
    const SHORT_PASSWORD = 'pwd';
    const HEAD = 'HEAD';
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const DB_SEED = 'db:seed';
    const NEGATIVE_TOKEN_COUNT = -1;
    const SET_TOKEN_COUNT = 25;
    const ADD_TOKEN_COUNT = 7;
    const FREE_MONTHLY_TOKENS = 4;
    public static function INDEXED_USER(int $index)
    {
        return "indexed_user_{$index}@testing.com";
    }

    private function __construct()
    {
    }
}
