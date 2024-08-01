<?php

namespace App\Http\DTOs;

class TokenDTO
{
    public int $paidTokens;
    public string $licenseKey;
    public string $userId;

    public function __construct(int $paidTokens, string $licenseKey, string $userId)
    {
        $this->paidTokens = $paidTokens;
        $this->licenseKey = $licenseKey;
        $this->userId = $userId;
    }
}
