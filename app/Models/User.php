<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Constants\Persist;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        Persist::EMAIL,
        Persist::PASSWORD,
        Persist::LICENSE_KEY,
        Persist::TOKENS_COUNT,
        Persist::IS_ADMIN,
        Persist::IS_DISABLED,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        Persist::PASSWORD,
        Persist::REMEMBER_TOKEN,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        Persist::PASSWORD => 'hashed',
        Persist::IS_ADMIN => 'boolean',
        Persist::IS_DISABLED => 'boolean',
    ];
}
