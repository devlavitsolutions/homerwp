<?php

namespace App\Http\DTOs;

class IsDisabledDTO
{
    public bool $isDisabled;
    public string $licenseKey;

    public function __construct(bool $isDisabled, string $licenseKey)
    {
        $this->isDisabled = $isDisabled;
        $this->licenseKey = $licenseKey;
    }
}
