<?php

namespace App\Http\Interfaces;

use App\Http\DTOs\ActivationDTO;
use Illuminate\Http\Request;

interface IActivationService
{
    public function validateDeleteActivationEndpoint(Request $request): ActivationDTO;

    public function validatePostActivationEndpoint(Request $request): ActivationDTO;
}
