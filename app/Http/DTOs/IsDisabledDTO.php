<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class IsDisabledDTO extends ShallowSerializable
{
    public bool $isDisabled;
    public string $licenseKey;

    public function __construct(bool $isDisabled, string $licenseKey)
    {
        $this->isDisabled = $isDisabled;
        $this->licenseKey = $licenseKey;
    }
}
