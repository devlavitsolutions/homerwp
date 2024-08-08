<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class EmailDTO extends ShallowSerializable
{
    public string $email;
    public string $licenseKey;

    public function __construct(string $email, string $licenseKey)
    {
        $this->email = $email;
        $this->licenseKey = $licenseKey;
    }
}
