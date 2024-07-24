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

    private function getValidUserLicenseKeyFromRouteParams(Request $request)
    {
        $licenseKey = $request->route(Routes::LICENSE_KEY);

        $request->merge([Persist::LICENSE_KEY => $licenseKey]);
        $request->validate([Persist::LICENSE_KEY => Persist::VALIDATE_EXISTING_LICENSE_KEY]);

        return $licenseKey;
    }

    private function checkIfDateBelongsToCurrentMonth(?string $dateTimeLastUsed)
    {
        if ($dateTimeLastUsed === null) {
            return false;
        }

        $currentDate = new DateTime();
        $startOfMonth = $currentDate->setTime(0, 0, 0);
        $lastDateTime = new DateTime($dateTimeLastUsed);

        return $lastDateTime >= $startOfMonth;
    }

    private function calculateRemainingFreeTokens(
        ?int $freeTokensUsedThisMonth,
        ?string $dateTimelastUsed
    ) {
        $usedTokens = $freeTokensUsedThisMonth ?? 0;

        if ($this->checkIfDateBelongsToCurrentMonth($dateTimelastUsed)) {
            return max(Defaults::FREE_TOKENS_PER_MONTH - $$usedTokens, 0);
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

        $user = User::where(Persist::EMAIL, $fields[Persist::EMAIL])->firstOrFail();

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

        $users = User::paginate(Defaults::PAGE_SIZE, ['*'], $pageName, $page);

        return $users;
    }

    /**
     * Allows Admin to see one users detailed info.
     * GET /users/{license-key}
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $user = User
            ::where(
                Persist::USERS . '.' . Persist::LICENSE_KEY,
                '=',
                $licenseKey
            )
            ->leftJoin(
                Persist::TOKENS,
                Persist::USERS . '.' . Persist::ID,
                '=',
                Persist::TOKENS . '.' . Persist::USER_ID,
            )
            ->select(
                Persist::USERS . '.*',
                Persist::TOKENS . '.' . Persist::FREE_TOKENS,
                Persist::TOKENS . '.' . Persist::PAID_TOKENS,
                Persist::TOKENS . '.' . Persist::LAST_USED,
            )
            ->with([Persist::ACTIVATIONS => function ($query) {
                $query->select(Persist::WEBSITE, Persist::USER_ID);
            }])
            ->firstOrFail();

        $user[Persist::WEBSITES] = array_map(function ($entry) {
            return $entry[Persist::WEBSITE];
        }, $user->activations->toArray());
        unset($user[Persist::ACTIVATIONS]);

        $user[Labels::FREE_TOKENS] = $this->calculateRemainingFreeTokens(
            $user[Persist::FREE_TOKENS],
            $user[Persist::LAST_USED]
        );
        unset($user[Persist::FREE_TOKENS]);

        return [
            Labels::USER => $user
        ];
    }

    /**
     * Allows Admin to change email of a user.
     * PUT /users/{license-key}/email
     * 
     * @urlParam license-key License Key.
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $fields = $request->validate(([
            Persist::EMAIL => Persist::VALIDATE_EMAIL,
        ]));

        $email = $fields[Persist::EMAIL];

        User::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->limit(1)
            ->update([Persist::EMAIL => $email]);

        return [
            Persist::LICENSE_KEY => $licenseKey,
            Persist::EMAIL => $email,
        ];
    }

    /**
     * Allows Admin to set how many tokens a user has.
     * POST /users/{license-key}/tokens-count
     * 
     * @urlParam license-key License Key.
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PAID_TOKENS => Persist::VALIDATE_PAID_TOKENS,
        ]));

        $tokensCount = $fields[Persist::PAID_TOKENS];

        $relatedUser = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->firstOrFail();

        Token::updateOrCreate(
            [Persist::USER_ID => $relatedUser[Persist::ID]],
            [
                Persist::LICENSE_KEY => $licenseKey,
                Persist::PAID_TOKENS => $tokensCount,
                Persist::IS_PREMIUM => true
            ],
        );

        User::where(Persist::ID, '=', $relatedUser[Persist::ID])
            ->take(1)
            ->update([Persist::IS_PREMIUM => true]);

        error_log($relatedUser[Persist::ID]);

        return [
            Persist::LICENSE_KEY => $licenseKey,
            Persist::PAID_TOKENS => (int)$tokensCount,
        ];
    }

    /**
     * Allows Admin to give more tokens to user.
     * PUT /users/{license-key}/tokens-count
     * 
     * @urlParam license-key License Key.
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PAID_TOKENS => Persist::VALIDATE_PAID_TOKENS,
        ]));

        $tokensCount = $fields[Persist::PAID_TOKENS];

        $relatedUser = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->firstOrFail();

        $token = Token::firstOrNew(
            [Persist::USER_ID => $relatedUser[Persist::ID]],
            [
                Persist::FREE_TOKENS => 0,
                Persist::PAID_TOKENS => 0,
            ]
        );
        $token->save();
        $token->increment(Persist::PAID_TOKENS, $tokensCount);

        $newTokensCount = $token->fresh()[Persist::PAID_TOKENS];

        User::find($relatedUser[Persist::ID])->update([Persist::IS_PREMIUM => true]);

        return [
            Persist::LICENSE_KEY => $licenseKey,
            Persist::PAID_TOKENS => $newTokensCount,
        ];
    }

    /**
     * Allows Admin to reset user tokens to 0.
     * DELETE /users/{license-key}/tokens-count
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $relatedUser = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->firstOrFail();

        Token::updateOrCreate(
            [Persist::USER_ID => $relatedUser[Persist::ID]],
            [
                Persist::PAID_TOKENS => 0,
                Persist::LICENSE_KEY => $licenseKey,
            ],

        );

        return [
            Persist::LICENSE_KEY => $licenseKey,
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

        $licenseKey = User::findOrFail($userId);

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $licenseKey[Persist::LICENSE_KEY]
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

        $newLicenseKey = Generators::generateLicenseKey();

        User::findOrFail($userId)
            ->update([Persist::LICENSE_KEY => $newLicenseKey]);

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $newLicenseKey
        ];
    }

    /**
     * Allows Admin to change user's enabled/disabled state.
     * PUT /users/{license-key}/is-disabled
     * 
     * @urlParam license-key License Key.
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $newIsDisabledState = $request->input(Persist::IS_DISABLED);

        $user = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->first();

        if ($newIsDisabledState && $user->is_admin) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Messages::BAD_REQUEST_DISABLE_ADMIN,
            );
        }

        $user->update([Persist::IS_DISABLED => $newIsDisabledState]);

        return [
            Persist::LICENSE_KEY => $licenseKey,
            Persist::IS_DISABLED => $newIsDisabledState
        ];
    }

    /**
     * Allows Admin to change user's admin privilege.
     * Can't remove last admin's role.
     * PUT /users/{license-key}/is-admin
     * 
     * @urlParam license-key License Key.
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
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $user = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->first();

        $newIsAdminState = $request->input(Persist::IS_ADMIN);

        if ($user->is_admin && !$newIsAdminState) {
            $adminCount = User::where(Persist::IS_ADMIN, '=', true)
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
            Persist::LICENSE_KEY => $licenseKey,
            Persist::IS_ADMIN => $newIsAdminState
        ];
    }

    /**
     * Allows Admin to change user's password.
     * PUT /users/{license-key}/password
     * 
     * @urlParam license-key License Key.
     * @bodyParam password New password. Must satisfy requirements.
     * 
     * @response 204
     * 
     * @throws 401
     * @throws 422
     */
    public function setPassword(Request $request)
    {
        $licenseKey = $this->getValidUserLicenseKeyFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]));

        $user = User::where(Persist::LICENSE_KEY, '=', $licenseKey);

        $user->update([Persist::PASSWORD => Generators::encryptPassword($fields[Persist::PASSWORD])]);

        return response()->noContent();
    }
}
