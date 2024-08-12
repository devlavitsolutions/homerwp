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
    public function validateGetAllUsersEndpoint(Request $request): int;

    public function validateLicenseKeyRouteParam(Request $request): string;

    public function validateLoginEndpoint(Request $request): User;

    public function validateRegisterEndpoint(Request $request): CredentialsDTO;

    public function validateSetEmailEndpoint(Request $request): EmailDTO;

    public function validateSetIsAdminEndpoint(Request $request): IsAdminDTO;

    public function validateSetIsDisabledEndpoint(Request $request): IsDisabledDTO;

    public function validateSetPasswordEndpoint(Request $request): PasswordDTO;

    public function validateTokensEndpoint(Request $request): TokenDTO;

    public function validateUserIdRouteParam(Request $request): string;
}
