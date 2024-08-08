<?php

namespace App\Http\DTOs;

use App\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\General\ShallowSerializable;

class UserDTO extends ShallowSerializable
{
    public User $user;
    public ?string $token;

    public function __construct(User|Model $user, ?string $token = null)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
