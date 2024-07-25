<?php

use App\Constants\Labels;
use App\Constants\Roles;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\ActivationController;
use App\Constants\Routes;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Admin-protected routes

Route::group([Labels::MIDDLEWARE_INDICATOR => [
    Labels::AUTH_MIDDLEWARE,
    Labels::AUTH_ROLES . ':' . Roles::Admin->value
]], function () {
    Route::post(
        '/' . Routes::REGISTER,
        [AuthController::class, 'register']
    );

    Route::prefix('/' . Routes::USERS . '/{' . Routes::USER_ID . '}')->group(function () {
        Route::get(
            '/' . Routes::LICENSE_KEY,
            [AuthController::class, 'getLicenseKey']
        );
        Route::delete(
            '/' . Routes::LICENSE_KEY,
            [AuthController::class, 'resetLicenseKey']
        );
    });

    Route::prefix('/' . Routes::USERS . '/{' . Routes::LICENSE_KEY . '}')->group(function () {
        Route::put(
            '/' . Routes::EMAIL,
            [AuthController::class, 'setEmail']
        );

        Route::put(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'setTokensCount']
        );
        Route::post(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'addTokensCount']
        );
        Route::delete(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'deleteTokensCount']
        );

        Route::put(
            '/' . Routes::IS_DISABLED,
            [AuthController::class, 'setIsDisabled']
        );

        Route::put(
            '/' . Routes::IS_ADMIN,
            [AuthController::class, 'setIsAdmin']
        );

        Route::put(
            '/' . Routes::PASSWORD,
            [AuthController::class, 'setPassword']
        );
    });

    Route::get(
        '/' . Routes::USERS,
        [AuthController::class, 'getAllUsers']
    );
});

// Auth-protected rotues

Route::group([Labels::MIDDLEWARE_INDICATOR => [Labels::AUTH_MIDDLEWARE]], function () {
    Route::post(
        '/' . Routes::LOGOUT,
        [AuthController::class, 'logout']
    );
});

// Public routes

Route::post('/' . Routes::LOGIN, [AuthController::class, 'login']);

Route::post('/' . Routes::CONTENT, [OpenAIController::class, 'getAssistantResponse']);

Route::post('/' . Routes::ACTIVATIONS_DELETE, [ActivationController::class, 'deleteActivation']);

Route::post('/' . Routes::ACTIVATIONS, [ActivationController::class, 'postActivation']);

Route::get('/' . Routes::USERS . '/{' . Routes::LICENSE_KEY . '}', [AuthController::class, 'getUser']);
