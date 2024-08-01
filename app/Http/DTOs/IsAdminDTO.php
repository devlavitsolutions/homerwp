<?php

namespace App\Http\DTOs;

class IsAdminDTO
{
    public bool $isAdmin;
    public string $licenseKey;

    public function __construct(bool $isAdmin, string $licenseKey)
    {
        $this->isAdmin = $isAdmin;
        $this->licenseKey = $licenseKey;
    }
}
