<?php

namespace App\Providers;

use App\Database\Interfaces\IActivationDbService;
use App\Database\Interfaces\ITokenDbService;
use App\Database\Interfaces\IUserDbService;
use App\Database\Services\ActivationDbService;
use App\Database\Services\TokenDbService;
use App\Database\Services\UserDbService;
use App\Http\Interfaces\IAuthService;
use App\Http\Services\AuthService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IUserDbService::class, UserDbService::class);
        $this->app->bind(ITokenDbService::class, TokenDbService::class);
        $this->app->bind(IActivationDbService::class, ActivationDbService::class);
        $this->app->bind(IAuthService::class, AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
