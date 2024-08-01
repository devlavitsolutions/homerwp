<?php

namespace App\Http\DTOs;

class PasswordDTO
{
    public string $password;
    public string $licenseKey;

    public function __construct(string $password, string $licenseKey)
    {
        $this->password = $password;
        $this->licenseKey = $licenseKey;
    }
}
