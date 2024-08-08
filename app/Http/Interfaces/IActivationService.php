<?php

namespace App\Http\Interfaces;

use App\Http\DTOs\ActivationDTO;
use Illuminate\Http\Request;

interface IActivationService
{
    function validatePostActivationEndpoint(Request $request): ActivationDTO;
    function validateDeleteActivationEndpoint(Request $request): ActivationDTO;
}
