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

    private const VAR_BOOLEAN = 'boolean';
    private const VAR_HASHED = 'hashed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        Persist::ID,
        Persist::EMAIL,
        Persist::PASSWORD,
        Persist::LICENSE_KEY,
        Persist::IS_ADMIN,
        Persist::IS_DISABLED,
        Persist::CID
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
        Persist::PASSWORD => self::VAR_HASHED,
        Persist::IS_ADMIN => self::VAR_BOOLEAN,
        Persist::IS_DISABLED => self::VAR_BOOLEAN,
        Persist::IS_PREMIUM => self::VAR_BOOLEAN,
    ];

    public function activations()
    {
        return $this->hasMany(Activation::class);
    }
}
