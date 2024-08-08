<?php

namespace App\Database\Services;

use App\Database\Constants\{TokenCol};
use App\Database\Interfaces\ITokenDbService;
use App\Database\Models\Token;

class TokenDbService implements ITokenDbService
{
    public function setPaidTokens(string $userId, int $paidTokens): Token
    {
        return Token::updateOrCreate(
            [TokenCol::USER_ID => $userId],
            [TokenCol::PAID_TOKENS => $paidTokens]
        );
    }

    public function addPaidTokens(string $userId, int $paidTokens): Token
    {
        $token = Token::firstOrNew(
            [TokenCol::USER_ID => $userId],
            [
                TokenCol::FREE_TOKENS => 0,
                TokenCol::PAID_TOKENS => 0,
            ]
        );
        $token->save();
        $token->increment(TokenCol::PAID_TOKENS, $paidTokens);
        $token->fresh();
        return $token;
    }
}
