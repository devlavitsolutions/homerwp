<?php

namespace App\Http\Interfaces;

use App\Database\Models\User;
use App\Http\DTOs\CredentialsDTO;
use App\Http\DTOs\EmailDTO;
use App\Http\DTOs\IsAdminDTO;
use App\Http\DTOs\IsDisabledDTO;
use App\Http\DTOs\PasswordDTO;
use App\Http\DTOs\TokenDTO;
use Illuminate\Http\Request;

interface IAuthService
{
    function validateLoginEndpoint(Request $request): User;
    function validateRegisterEndpoint(Request $request): CredentialsDTO;
    function validateGetAllUsersEndpoint(Request $request): int;
    function validateLicenseKeyRouteParam(Request $request): string;
    function validateUserIdRouteParam(Request $request): string;
    function validateSetEmailEndpoint(Request $request): EmailDTO;
    function validateTokensEndpoint(Request $request): TokenDTO;
    function validateSetIsDisabledEndpoint(Request $request): IsDisabledDTO;
    function validateSetIsAdminEndpoint(Request $request): IsAdminDTO;
    function validateSetPasswordEndpoint(Request $request): PasswordDTO;
}
