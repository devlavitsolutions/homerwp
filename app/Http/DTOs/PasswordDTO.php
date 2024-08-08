<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class PasswordDTO extends ShallowSerializable
{
    public string $password;
    public string $licenseKey;

    public function __construct(string $password, string $licenseKey)
    {
        $this->password = $password;
        $this->licenseKey = $licenseKey;
    }
}
