<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class ActivationDTO extends ShallowSerializable
{
    public string $userId;
    public bool $userIsPremium;
    public string $licenseKey;
    public string $website;

    public function __construct(
        string $userId,
        bool $userIsPremium,
        string $licenseKey,
        string $website
    ) {
        $this->userId = $userId;
        $this->userIsPremium = $userIsPremium;
        $this->licenseKey = $licenseKey;
        $this->website = $website;
    }
}
