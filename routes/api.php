<?php

use App\Constants\Roles;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpenAIController;
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

// const MIDDLEWARE_INDICATOR = 'middleware';
// const AUTH_MIDDLEWARE = 'auth:sanctum';
// const AUTH_ROLES = 'abilities';

// Public routes

Route::post('/' . Routes::LOGIN, [AuthController::class, 'login']);

Route::post('/' . Routes::CONTENT, [OpenAIController::class, 'getAssistantResponse']);

// Admin-protected routes

Route::group(['middleware' => [
    'auth:sanctum',
    'abilities' . ':' . Roles::Admin->value
]], function () {
    Route::post(
        '/' . Routes::REGISTER,
        [AuthController::class, 'register']
    );

    Route::get(
        '/' . Routes::USERS,
        [AuthController::class, 'getAllUsers']
    );

    Route::prefix('/' . Routes::USERS . '/{' . Routes::USER_ID . '}')->group(function () {
        Route::get(
            '/',
            [AuthController::class, 'getUser']
        );

        Route::put(
            '/' . Routes::EMAIL,
            [AuthController::class, 'setEmail']
        );

        Route::post(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'setTokensCount']
        );
        Route::put(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'addTokensCount']
        );
        Route::delete(
            '/' . Routes::TOKENS_COUNT,
            [AuthController::class, 'deleteTokensCount']
        );

        Route::get(
            '/' . Routes::LICENSE_KEY,
            [AuthController::class, 'getLicenseKey']
        );
        Route::delete(
            '/' . Routes::LICENSE_KEY,
            [AuthController::class, 'resetLicenseKey']
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
});

// Auth-protected rotues

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post(
        '/' . Routes::LOGOUT,
        [AuthController::class, 'logout']
    );
});

