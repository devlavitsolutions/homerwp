<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use App\Constants\Persist;
use App\Constants\Roles;
use App\Constants\StatusCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Constants\Routes;
use App\Constants\Labels;
use App\Constants\Messages;

class AuthController extends Controller
{
    private function getValidUserIdFromRouteParams(Request $request) {
        $USER_ID = 'userId';

        $userId = $request->route(Routes::USER_ID);

        $request->merge([$USER_ID => $userId]);
        $request->validate([$USER_ID => Persist::VALIDATE_ID]);

        return $userId;
    }

    public function register(Request $request) {
        $fields = $request->validate(([
            Persist::EMAIL => Persist::VALIDATE_EMAIL,
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]));

        $user = User::create([
            Persist::EMAIL => $fields[Persist::EMAIL],
            Persist::PASSWORD => bcrypt($fields[Persist::PASSWORD]),
            Persist::LICENSE_KEY => Str::uuid()->toString(),
        ]);

        $response = [
            Labels::USER => $user
        ];

        return response($response, StatusCodes::CREATED);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            Persist::EMAIL => Persist::VALIDATE_EMAIL,
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]);

        $user = User::where(Persist::EMAIL, $fields[Persist::EMAIL])->first();

        if (!$user || !Hash::check($fields[Persist::PASSWORD], $user->password)) {
            return response([Labels::MESSAGE => Messages::BAD_CREDENTIALS], StatusCodes::UNAUTHORIZED);
        }

        $abilities = $user->is_admin ? [Roles::Admin->value] : [];

        $token = $user->createToken($user->email, $abilities)->plainTextToken;

        $response = [
            Labels::USER => $user,
            Labels::TOKEN => $token
        ];

        return response($response, StatusCodes::OK);
    }

    public function logout() {
        auth()->user()->tokens()->delete();

        return response([Labels::MESSAGE => Messages::LOGOUT_SUCCESS], StatusCodes::OK);
    }

    public function getAllUsers(Request $request) {
        $pageName = 'page';

        $page = $request->page ?: Defaults::PAGE;

        $users = User::paginate(Defaults::PAGE_SIZE, [
            Persist::ID,
            Persist::EMAIL,
            Persist::TOKENS_COUNT,
            Persist::IS_ADMIN,
            Persist::IS_DISABLED
        ], $pageName, $page);

        return $users;
    }

    public function getUser(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $user = User::whereKey([$userId])->first();

        return [
            Labels::USER => $user
        ];
    }

    public function setEmail(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::EMAIL => $request->email]);

        return [
            Persist::ID => $userId,
            Persist::EMAIL => $request->email,
        ];
    }

    public function setTokensCount(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::TOKENS_COUNT => $request->tokensCount]);

        return [
            Persist::ID => $userId,
            Persist::TOKENS_COUNT => $request->tokensCount,
        ];
    }

    public function addTokensCount(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $user = User::find($userId);

        $user->increment(Persist::TOKENS_COUNT, $request->tokensCount);

        $newTokensCount = $user->fresh()->tokens_count;

        return [
            Persist::ID => $userId,
            Persist::TOKENS_COUNT => $newTokensCount,
        ];
    }

    public function clearTokensCount(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::TOKENS_COUNT => 0]);

        return [
            Persist::ID => $userId,
            Persist::TOKENS_COUNT => 0,
        ];
    }

    public function getLicenseKey(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $licenseKey = User::whereKey([$userId])
            ->get([Persist::LICENSE_KEY]);

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $licenseKey
        ];
    }

    public function resetLicenseKey(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $licenseKey = Str::uuid()->toString();

        User::whereKey([$userId])
            ->limit(1)
            ->update([Persist::LICENSE_KEY => $licenseKey])
            ->save();

        return [
            Persist::ID => $userId,
            Persist::LICENSE_KEY => $licenseKey
        ];
    }

    public function setIsDisabled(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $newIsDisabledState = $request->isDisabled;

        $user = User::findOrFail($userId);

        if ($newIsDisabledState && $user->is_admin) {
            abort(StatusCodes::UNPROCESSABLE, Messages::BAD_REQUEST_DISABLE_ADMIN);
        }

        $user->update([Persist::IS_DISABLED => $newIsDisabledState]);

        return [
            Persist::ID => $userId,
            Persist::IS_DISABLED => $newIsDisabledState
        ];
    }

    public function setIsAdmin(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $user = User::findOrFail($userId);

        $newIsAdminState = $request->is_admin;

        if ($user->is_admin && !$newIsAdminState) {
            $adminCount = User::where([Persist::IS_ADMIN, true])
                ->count();
            
            if ($adminCount <= 1) {
                abort(StatusCodes::UNPROCESSABLE, Messages::BAD_REQUEST_LAST_ADMIN);
            }
        }

        $user->update([Persist::IS_ADMIN => $newIsAdminState]);

        return [
            Persist::ID => $userId,
            Persist::IS_ADMIN => $newIsAdminState
        ];
    }

    public function setPassword(Request $request) {
        $userId = $this->getValidUserIdFromRouteParams($request);

        $fields = $request->validate(([
            Persist::PASSWORD => Persist::VALIDATE_PASSWORD,
        ]));

        $user = User::findOrFail($userId);

        $user->update([Persist::PASSWORD => bcrypt($fields[Persist::PASSWORD])]);

        return response(StatusCodes::NO_CONTENT);
    }
}
