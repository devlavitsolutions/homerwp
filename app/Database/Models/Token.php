<?php

namespace App\Database\Models;

use App\Database\Constants\Cast;
use App\Database\Constants\TokenCol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        TokenCol::ID,
        TokenCol::USER_ID,
        TokenCol::FREE_TOKENS,
        TokenCol::PAID_TOKENS,
        TokenCol::LAST_USED,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        TokenCol::LAST_USED => Cast::DATETIME,
    ];

    public $timestamps = false;
}
