<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use App\Constants\Persist;
use App\Constants\Roles;
use Illuminate\Http\Request;

use App\Models\User;
use App\Constants\Routes;
use App\Constants\Labels;
use App\Constants\Messages;
use App\Helpers\Generators;
use App\Models\Token;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    private function getValidUserIdFromRouteParams(Request $request)
    {
        $userId = $request->route(Routes::USER_ID);

        $request->merge([Routes::USER_ID => $userId]);
        $request->validate([Routes::USER_ID => Persist::VALIDATE_ID]);

        return $userId;
    }

    private function checkIfDateBelongsToCurrentMonth(?DateTime $dateTimeLastUsed)
    {
        if ($dateTimeLastUsed === null) {
            return false;
        }

        $currentDate = new DateTime();
        $startOfMonth = $currentDate->setTime(0, 0, 0);

        return $dateTimeLastUsed >= $startOfMonth;
    }

    private function calculateRemainingFreeTokens(
        int $freeTokensUsedThisMonth,
        ?DateTime $dateTimelastUsed
    ) {
        if ($this->checkIfDateBelongsToCurrentMonth($dateTimelastUsed)) {
            return max(Defaults::FREE_TOKENS_PER_MONTH - $freeTokensUsedThisMonth, 0);
        } else {
            return Defaults::FREE_TOKENS_PER_MONTH;
        }
    }

    /**
     * Allows admin to create new User.
     * POST /register
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
        $fields = $request->validate(([
            Persist::EMAIL => Persist::VALIDATE_EMAIL,
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]));

        $user = User::create([
            Persist::EMAIL => $fields[Persist::EMAIL],
            Persist::PASSWORD => Generators::encryptPassword($fields[Persist::PASSWORD]),
            Persist::LICENSE_KEY => Generators::generateLicenseKey(),
            Persist::IS_ADMIN => false
        ]);

        $response = [
            Labels::USER => $user
        ];

        return response($response, Response::HTTP_CREATED);
    }

    /**
     * Allows User to login, most often admin.
     * POST /login
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
        $fields = $request->validate([
            Persist::EMAIL => Persist::VALIDATE_REQUIRED,
            Persist::PASSWORD => Persist::VALIDATE_REQUIRED,
        ]);

        $user = User::where(Persist::EMAIL, $fields[Persist::EMAIL])->first();

        if (
            !$user
            || !Generators::checkPassword($fields[Persist::PASSWORD], $user->password)
        ) {
            return response(
                [Labels::MESSAGE => Messages::BAD_CREDENTIALS],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $abilities = $user->is_admin ? [Roles::Admin->value] : [];

        $token = $user->createToken($user->email, $abilities)->plainTextToken;

        $response = [
            Labels::USER => $user,
            Labels::TOKEN => $token
        ];

        return response($response, Response::HTTP_OK);
    }

    /**
     * Allows User to clear all tokens.
     * POST /logout
     * 
     * @response 200 {
     *  "message": string
     * }
     * 
     * @throws 401
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response(
            [Labels::MESSAGE => Messages::LOGOUT_SUCCESS],
            Response::HTTP_OK,
        );
    }

    /**
     * Allows Admin to see all users basic info.
     * GET /users[?page={index}]
     * 
     * @response 200 {
     *  "data": [User]
     * }
     * 
     * @throws 401
     */
    public function getAllUsers(Request $request)
    {
        $pageName = 'page';

        $page = $request->page ?: Defaults::PAGE;
        if ($page <= 0) {
            $page = Defaults::PAGE;
        }

        $users = User::paginate(Defaults::PAGE_SIZE, [
            Persist::ID,
            Persist::EMAIL,
            Persist::IS_ADMIN,
            Persist::IS_DISABLED
        ], $pageName, $page);

        return $users;
    }

    /**
     * Allows Admin to see one users detailed info.
     * GET /users/{id}
     * 
     * @urlParam id User id.
     * 
     * @response 200 {
     *  "user": User
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function getUser(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $user = User::whereKey([$userId])->first();
        $token = Token::where(Persist::USER_ID, '=', $userId)
            ->select([Persist::FREE_TOKENS, Persist::PAID_TOKENS, Persist::LAST_USED])
            ->first()
            ?: [
                Persist::FREE_TOKENS => 0,
                Persist::PAID_TOKENS => 0,
                Persist::LAST_USED => null
            ];

        return [
            Labels::USER => [
                ...$user->toArray(),
                Persist::PAID_TOKENS => $token[Persist::PAID_TOKENS],
                Labels::FREE_TOKENS => $this->calculateRemainingFreeTokens(
                    $token[Persist::FREE_TOKENS],
                    $token[Persist::LAST_USED]
                ),
                Persist::LAST_USED => $token[Persist::LAST_USED],
            ]
        ];
    }

    /**
     * Allows Admin to change email of a user.
     * PUT /users/{id}/email
     * 
     * @urlParam id User id.
     * @bodyParam email New, valid, email.
     * 
     * @response 200 {
     *  "id": User id,
     *  "email": New email
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function setEmail(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $fields = $request->validate(([
            Persist::EMAIL => Persist::VALIDATE_EMAIL,
        ]));

        $email = $fields[Persist::EMAIL];

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::EMAIL => $email]);

        return [
            Persist::ID => $userId,
            Persist::EMAIL => $email,
        ];
    }

    /**
     * Allows Admin to set how many tokens a user has.
     * POST /users/{id}/tokens-count
     * 
     * @urlParam id User id.
     * @bodyParam tokensCount How many tokens a user should have.
     * 
     * @response 200 {
     *  "id": User id,
     *  "tokensCount": New number of tokens available to the user
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function setTokensCount(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PAID_TOKENS => Persist::VALIDATE_PAID_TOKENS,
        ]));

        $tokensCount = $fields[Persist::PAID_TOKENS];

        Token::updateOrCreate(
            [Persist::USER_ID => $userId],
            [
                Persist::USER_ID => $userId,
                Persist::PAID_TOKENS => $tokensCount,
                Persist::FREE_TOKENS => 0
            ],

        );

        return [
            Persist::ID => $userId,
            Persist::PAID_TOKENS => (int)$tokensCount,
        ];
    }

    /**
     * Allows Admin to give more tokens to user.
     * PUT /users/{id}/tokens-count
     * 
     * @urlParam id User id.
     * @bodyParam tokensCount How many tokens a user should have.
     * 
     * @response 200 {
     *  "id": User id,
     *  "tokensCount": New number of tokens available to the user
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function addTokensCount(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PAID_TOKENS => Persist::VALIDATE_PAID_TOKENS,
        ]));

        $tokensCount = $fields[Persist::PAID_TOKENS];

        $token = Token::firstOrNew(
            [Persist::USER_ID => $userId],
            [
                Persist::FREE_TOKENS => 0,
                Persist::PAID_TOKENS => 0
            ]
        );
        $token->save();
        $token->increment(Persist::PAID_TOKENS, $tokensCount);

        $newTokensCount = $token->fresh()[Persist::PAID_TOKENS];

        return [
            Persist::ID => $userId,
            Persist::PAID_TOKENS => $newTokensCount,
        ];
    }

    /**
     * Allows Admin to reset user tokens to 0.
     * DELETE /users/{id}/tokens-count
     * 
     * @urlParam id User id.
     * 
     * @response 200 {
     *  "id": User id,
     *  "tokensCount": 0
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function deleteTokensCount(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        Token::updateOrCreate(
            [Persist::USER_ID => $userId],
            [
                Persist::PAID_TOKENS => 0,
                Persist::USER_ID => $userId,
                Persist::FREE_TOKENS => 0
            ],

        );

        return [
            Persist::ID => $userId,
            Persist::PAID_TOKENS => 0,
        ];
    }

    /**
     * Allows Admin to see user license.
     * GET /users/{id}/license-key
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
        $userId = $this->getValidUserIdFromRouteParams($request);

        $licenseKey = User::whereKey([$userId])
            ->first([Persist::LICENSE_KEY]);

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $licenseKey->license_key
        ];
    }

    /**
     * Allows Admin to change user's license key to new one.
     * New license key will be generated on the server.
     * DELETE /users/{id}/license-key
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
        $userId = $this->getValidUserIdFromRouteParams($request);

        $licenseKey = Generators::generateLicenseKey();

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::LICENSE_KEY => $licenseKey]);

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $licenseKey
        ];
    }

    /**
     * Allows Admin to change user's enabled/disabled state.
     * PUT /users/{id}/license-key
     * 
     * @urlParam id User id.
     * @bodyParam isDisabled Boolean value indicating new state.
     * 
     * @response 200 {
     *  "id": User id,
     *  "isDisabled": User's new state
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function setIsDisabled(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $newIsDisabledState = $request->input(Persist::IS_DISABLED);

        $user = User::findOrFail($userId);

        if ($newIsDisabledState && $user->is_admin) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Messages::BAD_REQUEST_DISABLE_ADMIN,
            );
        }

        $user->update([Persist::IS_DISABLED => $newIsDisabledState]);

        return [
            Persist::ID => $userId,
            Persist::IS_DISABLED => $newIsDisabledState
        ];
    }

    /**
     * Allows Admin to change user's admin privilege.
     * Can't remove last admin's role.
     * PUT /users/{id}/is-admin
     * 
     * @urlParam id User id.
     * @bodyParam isAdmin Boolean value indicating new state.
     * 
     * @response 200 {
     *  "id": User id,
     *  "isAdmin": User's new state
     * }
     * 
     * @throws 401
     * @throws 422
     */
    public function setIsAdmin(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $user = User::findOrFail($userId);

        $newIsAdminState = $request->input(Persist::IS_ADMIN);

        if ($user->is_admin && !$newIsAdminState) {
            $adminCount = User::where(Persist::IS_ADMIN, true)
                ->count();

            if ($adminCount <= 1) {
                abort(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    Messages::BAD_REQUEST_LAST_ADMIN,
                );
            }
        }

        $user->update([Persist::IS_ADMIN => $newIsAdminState]);

        return [
            Persist::ID => $userId,
            Persist::IS_ADMIN => $newIsAdminState
        ];
    }

    /**
     * Allows Admin to change user's password.
     * PUT /users/{id}/password
     * 
     * @urlParam id User id.
     * @bodyParam password New password. Must satisfy requirements.
     * 
     * @response 204
     * 
     * @throws 401
     * @throws 422
     */
    public function setPassword(Request $request)
    {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]));

        $user = User::findOrFail($userId);

        $user->update([Persist::PASSWORD => Generators::encryptPassword($fields[Persist::PASSWORD])]);

        return response()->noContent();
    }
}
