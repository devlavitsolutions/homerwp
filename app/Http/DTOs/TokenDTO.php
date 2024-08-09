<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class TokenDTO extends ShallowSerializable
{
    public string $licenseKey;
    public int $paidTokens;
    public string $userId;

    public function __construct(int $paidTokens, string $licenseKey, string $userId)
    {
        $this->paidTokens = $paidTokens;
        $this->licenseKey = $licenseKey;
        $this->userId = $userId;
    }
}
