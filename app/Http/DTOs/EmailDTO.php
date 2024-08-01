<?php

namespace App\Http\DTOs;

class EmailDTO
{
    public string $email;
    public string $licenseKey;

    public function __construct(string $email, string $licenseKey)
    {
        $this->email = $email;
        $this->password = $licenseKey;
    }
}
