<?php

namespace App\Database\Interfaces;

use App\Database\Models\Token;

interface ITokenDbService
{
    function setPaidTokens(
        string $userId,
        int $paidTokens,
    ): Token;

    function addPaidTokens(
        string $userId,
        int $paidTokens,
    ): Token;
}
