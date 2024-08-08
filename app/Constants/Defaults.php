<?php

namespace App\Constants;

class Defaults
{
    const PAGE = 1;
    const PAGE_SIZE = 20;
    const FREE_TOKENS_PER_MONTH = 4;
    const MAX_OPENAI_WAIT_TIME = 40;
    const PERIOD_BETWEEN_ACTIVATIONS_FOR_FREE_USER = 'P1M';
    const ENV_PRODUCTION = 'production';
    const SEED_EMAIL = 'stefan.jankovic@lavitsolutions.com';
    const SEED_PASSWORD = 'seedpas5word_CHANGE_IT';

    private function __construct()
    {
    }
}
