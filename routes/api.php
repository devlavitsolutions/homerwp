<?php

use App\Constants\Roles;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
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

// Public routes

Route::post('/'.Routes::LOGIN, [AuthController::class, 'login']);

// Admin-protected routes

Route::group(['middleware' => ['auth:sanctum', 'abilities:'.Roles::Admin->value]], function() {
    $userById = '/'.Routes::USERS.'/{'.Routes::USER_ID.'}';

    Route::post(
        '/'.Routes::REGISTER,
        [AuthController::class, 'register']
    );

    Route::get(
        '/'.Routes::USERS,
        [AuthController::class, 'getAllUsers']
    );
    Route::get(
        $userById,
        [AuthController::class, 'getUser']
    );

    Route::put(
        $userById.'/'.Routes::EMAIL,
        [AuthController::class, 'setEmail']
    );

    Route::post(
        $userById.'/'.Routes::TOKENS_COUNT,
        [AuthController::class, 'setTokensCount']
    );
    Route::put(
        $userById.'/'.Routes::TOKENS_COUNT,
        [AuthController::class, 'addTokensCount']
    );
    Route::delete(
        $userById.'/'.Routes::TOKENS_COUNT,
        [AuthController::class, 'deleteTokensCount']
    );

    Route::get(
        $userById.'/'.Routes::LICENSE_KEY,
        [AuthController::class, 'getLicenseKey']
    );
    Route::delete(
        $userById.'/'.Routes::LICENSE_KEY,
        [AuthController::class, 'resetLicenseKey']
    );

    Route::put(
        $userById.'/'.Routes::IS_DISABLED,
        [AuthController::class, 'setIsDisabled']
    );

    Route::put(
        $userById.'/'.Routes::IS_ADMIN,
        [AuthController::class, 'setIsAdmin']
    );

    Route::put(
        $userById.'/'.Routes::PASSWORD,
        [AuthController::class, 'setPassword']
    );
});

// Auth-protected rotues

Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post(
        '/'.Routes::LOGOUT,
        [AuthController::class, 'logout']
    );
});
