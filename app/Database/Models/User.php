<?php

namespace App\Database\Models;

use App\Database\Constants\Cast;
use App\Database\Constants\UserCol;
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
        UserCol::ID,
        UserCol::EMAIL,
        UserCol::PASSWORD,
        UserCol::LICENSE_KEY,
        UserCol::IS_ADMIN,
        UserCol::IS_DISABLED,
        UserCol::CID
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        UserCol::PASSWORD,
        UserCol::REMEMBER_TOKEN,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        UserCol::PASSWORD => Cast::HASHED,
        UserCol::IS_ADMIN => Cast::BOOLEAN,
        UserCol::IS_DISABLED => Cast::BOOLEAN,
        UserCol::IS_PREMIUM => Cast::BOOLEAN,
    ];

    public function activations()
    {
        return $this->hasMany(Activation::class);
    }
}
