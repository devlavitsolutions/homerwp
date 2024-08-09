<?php

namespace App\Http\Controllers;

use App\Constants\Roles;
use App\Database\Constants\TokenCol;
use App\Database\Constants\UserCol;
use App\Database\Interfaces\ITokenDbService;
use App\Database\Interfaces\IUserDbService;
use App\Helpers\Generators;
use App\Http\DTOs\TokenDTO;
use App\Http\DTOs\UserDTO;
use App\Http\DTOs\UserIdDTO;
use App\Http\Interfaces\IAuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private IUserDbService $userDbService,
        private ITokenDbService $tokenDbService,
        private IAuthService $authService,
    ) {}

    /**
     * Allows Admin to give more tokens to user.
     * PUT /users/{license-key}/tokens-count.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam tokensCount How many tokens a user should have.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "tokensCount": New number of tokens available to the user
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function addTokensCount(Request $request)
    {
        $tokenData = $this->authService->validateTokensEndpoint($request);

        $tokenModel = $this->tokenDbService->addPaidTokens(
            $tokenData->userId,
            $tokenData->paidTokens,
        );

        $this->userDbService->updateField(
            UserCol::IS_PREMIUM,
            true,
            UserCol::ID,
            $tokenData->userId,
        );

        return new TokenDTO(
            $tokenModel[TokenCol::PAID_TOKENS],
            $tokenData->licenseKey,
            $tokenData->userId,
        );
    }

    /**
     * Allows Admin to reset user tokens to 0.
     * DELETE /users/{license-key}/tokens-count.
     *
     * @urlParam license-key License Key.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "tokensCount": 0
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function deleteTokensCount(Request $request)
    {
        $licenseKey = $this->authService->validateLicenseKeyRouteParam($request);

        $relatedUser = $this->userDbService->selectUserByLicenseKey($licenseKey);
        $userId = $relatedUser[UserCol::ID];

        $this->tokenDbService->setPaidTokens($userId, 0);

        return new TokenDTO(0, $licenseKey, $userId);
    }

    /**
     * Allows Admin to see all users basic info.
     * GET /users[?page={index}].
     *
     * @response 200 {
     *  "data": [User]
     * }
     *
     * @throws 401
     */
    public function getAllUsers(Request $request)
    {
        $pageIndex = $this->authService->validateGetAllUsersEndpoint($request);

        return $this->userDbService->selectAllUsers($pageIndex);
    }

    /**
     * Allows Admin to see user license.
     * GET /users/{id}/license-key.
     *
     * @urlParam id User id.
     *
     * @response 200 {
     *  "id": User id,
     *  "licenseKey": User's current license
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function getLicenseKey(Request $request)
    {
        $userId = $this->authService->validateUserIdRouteParam($request);

        $user = $this->userDbService->selectUserById($userId);

        return new UserIdDTO($userId, $user[UserCol::LICENSE_KEY]);
    }

    /**
     * Allows Admin to see one users detailed info.
     * GET /users/{license-key}.
     *
     * @urlParam license-key License Key.
     *
     * @response 200 {
     *  "user": User
     * }
     *
     * @throws 401
     * @throws 404
     * @throws 422
     */
    public function getUser(Request $request)
    {
        $licenseKey = $this->authService->validateLicenseKeyRouteParam($request);

        $user = $this->userDbService->selectUserDetailsByLicenseKey($licenseKey);

        return new UserDTO($user);
    }

    /**
     * Allows User to login, most often admin.
     * POST /login.
     *
     * @bodyParam email Valid email of existing user.
     * @bodyParam password Valid password, not encrypted.
     *
     * @response 200 {
     *  "user": User
     *  "token": JWT-like string
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function login(Request $request)
    {
        $user = $this->authService->validateLoginEndpoint($request);

        $token = $user->createToken(
            $user[UserCol::EMAIL],
            $user[UserCol::IS_ADMIN] ? [Roles::ADMIN] : [],
        )->plainTextToken;

        return new UserDTO($user, $token);
    }

    /**
     * Allows User to clear all tokens.
     * POST /logout.
     *
     * @response 204
     *
     * @throws 401
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->noContent();
    }

    /**
     * Allows admin to create new User.
     * POST /register.
     *
     * @bodyParam email Valid, unique email of new user.
     * @bodyParam password Valid password, not encrypted.
     *
     * @response 201 {
     *  "user": {
     *      "id": integer,
     *      "email": string,
     *      "licenseKey": string,
     *  }
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function register(Request $request)
    {
        $credentials = $this->authService->validateRegisterEndpoint($request);

        $user = $this->userDbService->create($credentials->email, $credentials->password);
        $userData = new UserDTO($user);

        return response($userData, Response::HTTP_CREATED);
    }

    /**
     * Allows Admin to change user's license key to new one.
     * New license key will be generated on the server.
     * DELETE /users/{id}/license-key.
     *
     * @urlParam id User id.
     *
     * @response 200 {
     *  "id": User id,
     *  "licenseKey": User's new license
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function resetLicenseKey(Request $request)
    {
        $userId = $this->authService->validateUserIdRouteParam($request);

        $newLicenseKey = Generators::generateLicenseKey();

        $this->userDbService->updateField(
            UserCol::LICENSE_KEY,
            $newLicenseKey,
            UserCol::ID,
            $userId,
        );

        return new UserIdDTO($userId, $newLicenseKey);
    }

    /**
     * Allows Admin to change email of a user.
     * PUT /users/{license-key}/email.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam email New, valid, email.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "email": New email
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function setEmail(Request $request)
    {
        $emailData = $this->authService->validateSetEmailEndpoint($request);

        $this->userDbService->updateField(
            UserCol::EMAIL,
            $emailData->email,
            UserCol::LICENSE_KEY,
            $emailData->licenseKey,
        );

        return $emailData;
    }

    /**
     * Allows Admin to change user's admin privilege.
     * Can't remove last admin's role.
     * PUT /users/{license-key}/is-admin.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam isAdmin Boolean value indicating new state.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "isAdmin": User's new state
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function setIsAdmin(Request $request)
    {
        $isAdminData = $this->authService->validateSetIsAdminEndpoint($request);

        $this->userDbService->updateField(
            UserCol::IS_ADMIN,
            $isAdminData->isAdmin,
            UserCol::LICENSE_KEY,
            $isAdminData->licenseKey,
        );

        return $isAdminData;
    }

    /**
     * Allows Admin to change user's enabled/disabled state.
     * PUT /users/{license-key}/is-disabled.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam isDisabled Boolean value indicating new state.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "isDisabled": User's new state
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function setIsDisabled(Request $request)
    {
        $isDisabledData = $this->authService->validateSetIsDisabledEndpoint($request);

        $this->userDbService->updateField(
            UserCol::IS_DISABLED,
            $isDisabledData->isDisabled,
            UserCol::LICENSE_KEY,
            $isDisabledData->licenseKey,
        );

        return $isDisabledData;
    }

    /**
     * Allows Admin to change user's password.
     * PUT /users/{license-key}/password.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam password New password. Must satisfy requirements.
     *
     * @response 204
     *
     * @throws 401
     * @throws 422
     */
    public function setPassword(Request $request)
    {
        $passwordData = $this->authService->validateSetPasswordEndpoint($request);

        $this->userDbService->updateField(
            UserCol::PASSWORD,
            $passwordData->password,
            UserCol::LICENSE_KEY,
            $passwordData->licenseKey,
        );

        return response()->noContent();
    }

    /**
     * Allows Admin to set how many tokens a user has.
     * POST /users/{license-key}/tokens-count.
     *
     * @urlParam license-key License Key.
     *
     * @bodyParam tokensCount How many tokens a user should have.
     *
     * @response 200 {
     *  "licenseKey": License Key,
     *  "tokensCount": New number of tokens available to the user
     * }
     *
     * @throws 401
     * @throws 422
     */
    public function setTokensCount(Request $request)
    {
        $tokenData = $this->authService->validateTokensEndpoint($request);

        $this->tokenDbService->setPaidTokens(
            $tokenData->userId,
            $tokenData->paidTokens,
        );

        $this->userDbService->updateField(
            UserCol::IS_PREMIUM,
            true,
            UserCol::ID,
            $tokenData->userId,
        );

        return $tokenData;
    }
}
