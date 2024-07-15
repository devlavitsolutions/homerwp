<?php

namespace App\Models;

use App\Constants\Persist;
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
        Persist::ID,
        Persist::USER_ID,
        Persist::FREE_TOKENS,
        Persist::PAID_TOKENS,
        Persist::LAST_USED,
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
    protected $casts = [];

    public $timestamps = false;
}
