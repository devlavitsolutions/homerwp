<?php

namespace App\Http\Services;

use App\Constants\Defaults;
use App\Http\Constants\Messages;
use App\Database\Constants\UserCol;
use App\Database\Interfaces\IUserDbService;
use App\Database\Models\User;
use App\Helpers\Generators;
use App\Http\Constants\Field;
use App\Http\Constants\InputRule;
use App\Http\Constants\Param;
use App\Http\Constants\Query;
use App\Http\DTOs\CredentialsDTO;
use App\Http\DTOs\EmailDTO;
use App\Http\DTOs\IsAdminDTO;
use App\Http\DTOs\IsDisabledDTO;
use App\Http\DTOs\PasswordDTO;
use App\Http\DTOs\TokenDTO;
use App\Http\Interfaces\IAuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthService implements IAuthService
{
    public function __construct(
        private IUserDbService $userDbService,
    ) {
    }

    public function validateLoginEndpoint(Request $request): User
    {
        $fields = $request->validate([
            Field::EMAIL => InputRule::REQUIRED,
            Field::PASSWORD => InputRule::REQUIRED,
        ]);

        $user = $this->userDbService->selectUserByEmail($fields[Field::EMAIL]);

        if (
            !$user
            || !Generators::checkPassword($fields[Field::PASSWORD], $user[UserCol::PASSWORD])
        ) {
            abort(
                Response::HTTP_UNAUTHORIZED,
                Messages::BAD_CREDENTIALS,
            );
        }

        return $user;
    }

    public function validateRegisterEndpoint(Request $request): CredentialsDTO
    {
        $fields = $request->validate([
            Field::EMAIL => InputRule::EMAIL,
            Field::PASSWORD => InputRule::PASSWORD,
        ]);

        return new CredentialsDTO($fields[Field::EMAIL], $fields[Field::PASSWORD]);
    }

    public function validateGetAllUsersEndpoint(Request $request): int
    {
        $request->merge([Query::PAGE => $request->query(Query::PAGE)]);
        $request->validate([Query::PAGE => InputRule::PAGE]);

        return $request->page ?: Defaults::PAGE;
    }

    public function validateLicenseKeyRouteParam(Request $request): string
    {
        $licenseKey = $request->route(Param::LICENSE_KEY);

        $request->merge([Param::LICENSE_KEY => $licenseKey]);
        $request->validate([Param::LICENSE_KEY => InputRule::EXISTING_LICENSE_KEY]);

        return $licenseKey;
    }

    public function validateUserIdRouteParam(Request $request): string
    {
        $userId = $request->route(Param::USER_ID);

        $request->merge([Param::USER_ID => $userId]);
        $request->validate([Param::USER_ID => InputRule::ID]);

        return $userId;
    }

    public function validateSetEmailEndpoint(Request $request): EmailDTO
    {
        $licenseKey = $this->validateLicenseKeyRouteParam($request);
        $fields = $request->validate([
            Field::EMAIL => InputRule::EMAIL,
        ]);

        return new EmailDTO($fields[Field::EMAIL], $licenseKey);
    }

    public function validateTokensEndpoint(Request $request): TokenDTO
    {
        $licenseKey = $this->validateLicenseKeyRouteParam($request);

        $fields = $request->validate([
            Field::PAID_TOKENS => InputRule::PAID_TOKENS,
        ]);

        $user = $this->userDbService->selectUserByLicenseKey($licenseKey);

        return new TokenDTO(
            (int)$fields[Field::PAID_TOKENS],
            $licenseKey,
            $user[UserCol::ID],
        );
    }

    public function validateSetIsDisabledEndpoint(Request $request): IsDisabledDTO
    {
        $licenseKey = $this->validateLicenseKeyRouteParam($request);

        $fields = $request->validate([
            Field::IS_DISABLED => InputRule::SINGLE_BOOLEAN_CHANGE,
        ]);
        $isDisabled = $fields[Field::IS_DISABLED];

        $user = $this->userDbService->selectUserByLicenseKey($licenseKey);

        if ($isDisabled && $user[UserCol::IS_ADMIN]) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Messages::BAD_REQUEST_DISABLE_ADMIN,
            );
        }

        return new IsDisabledDTO($isDisabled, $licenseKey);
    }

    public function validateSetIsAdminEndpoint(Request $request): IsAdminDTO
    {
        $licenseKey = $this->validateLicenseKeyRouteParam($request);

        $fields = $request->validate([
            Field::IS_ADMIN => InputRule::SINGLE_BOOLEAN_CHANGE,
        ]);
        $isAdmin = $fields[Field::IS_ADMIN];

        $user = $this->userDbService->selectUserDetailsByLicenseKey($licenseKey);

        if (!$isAdmin && $user[UserCol::IS_ADMIN]) {
            $adminCount = $this->userDbService->countAdmins();

            if ($adminCount <= 1) {
                abort(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    Messages::BAD_REQUEST_LAST_ADMIN,
                );
            }
        }

        return new IsAdminDTO($isAdmin, $licenseKey);
    }

    public function validateSetPasswordEndpoint(Request $request): PasswordDTO
    {
        $licenseKey = $this->validateLicenseKeyRouteParam($request);

        $fields = $request->validate([
            Field::PASSWORD => InputRule::PASSWORD,
        ]);
        $password = $fields[Field::PASSWORD];

        $encryptedPassword = Generators::encryptPassword($password);

        return new PasswordDTO($encryptedPassword, $licenseKey);
    }
}
