<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class TokenDTO extends ShallowSerializable
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
