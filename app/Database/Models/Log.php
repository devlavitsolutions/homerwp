<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'keywords',
        'website',
        'license_key',
        'response',
    ];
}
