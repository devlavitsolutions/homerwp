<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class CredentialsDTO extends ShallowSerializable
{
    public string $email;
    public string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
