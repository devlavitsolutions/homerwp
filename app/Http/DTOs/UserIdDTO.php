<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class UserIdDTO extends ShallowSerializable
{
    public int $id;
    public string $licenseKey;

    public function __construct(int $id, string $licenseKey)
    {
        $this->id = $id;
        $this->licenseKey = $licenseKey;
    }
}
