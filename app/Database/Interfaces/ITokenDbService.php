<?php

namespace App\Database\Interfaces;

use App\Database\Models\Token;

interface ITokenDbService
{
    public function addPaidTokens(
        string $userId,
        int $paidTokens,
    ): Token;

    public function setPaidTokens(
        string $userId,
        int $paidTokens,
    ): Token;
}
