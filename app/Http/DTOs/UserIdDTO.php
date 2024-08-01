<?php

namespace App\Http\DTOs;

class UserIdDTO
{
    public int $id;
    public string $licenseKey;

    public function __construct(int $id, string $licenseKey)
    {
        $this->id = $id;
        $this->licenseKey = $licenseKey;
    }
}
