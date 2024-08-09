<?php

namespace App\Constants;

class Defaults
{
    public const ENV_PRODUCTION = 'production';
    public const FREE_TOKENS_PER_MONTH = 4;
    public const MAX_OPENAI_WAIT_TIME = 40;
    public const PAGE = 1;
    public const PAGE_SIZE = 20;
    public const PERIOD_BETWEEN_ACTIVATIONS_FOR_FREE_USER = 'P1M';
    public const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    public const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';

    private function __construct() {}
}
