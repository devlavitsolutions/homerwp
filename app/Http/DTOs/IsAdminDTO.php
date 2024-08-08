<?php

namespace App\Http\DTOs;

use App\Utilities\General\ShallowSerializable;

class IsAdminDTO extends ShallowSerializable
{
    public bool $isAdmin;
    public string $licenseKey;

    public function __construct(bool $isAdmin, string $licenseKey)
    {
        $this->isAdmin = $isAdmin;
        $this->licenseKey = $licenseKey;
    }
}
