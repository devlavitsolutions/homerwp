<?php

namespace App\Http\DTOs;

use App\Database\Models\User;
use App\Utilities\General\ShallowSerializable;
use Illuminate\Database\Eloquent\Model;

class UserDTO extends ShallowSerializable
{
    public ?string $token;
    public User $user;

    public function __construct(Model|User $user, ?string $token = null)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
