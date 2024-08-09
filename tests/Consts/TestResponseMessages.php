<?php

namespace Tests\Consts;

class TestResponseMessages
{
    public const ADMIN_USERS_MUST_BE_ENABLED = 'Cannot disable admin users. Please first change their roles.';
    public const AT_LEAST_ONE_ADMIN_NEEDED = 'Cannot remove last admin. At least one must remain.';
    public const BAD_EMAIL = 'The email field must be a valid email address.';
    public const BAD_LICENSE_KEY = 'The selected license key is invalid.';
    public const BAD_USER_ID = 'The selected user id is invalid.';
    public const EMAIL_ALREADY_TAKEN = 'The email has already been taken.';
    public const LOGGED_OUT = 'Successfully logged out.';
    public const REQUIRE_NON_NEGATIVE_TOKENS = 'The paid tokens field must be greater than or equal to 0.';
    public const SHORT_PASSWORD = 'The password field must be at least 8 characters.';
    public const UNAUTHORIZED = 'Unauthenticated.';
    public const WRONG_CREDENTIALS = 'Username or password is wrong.';

    private function __construct() {}

    public static function METHOD_NOT_SUPPORTED(
        string $unsupportedMethod,
        string $route,
        string ...$supportedMethods,
    ) {
        $trimmedRoute = ltrim($route, '/');

        return "The {$unsupportedMethod} method is not supported for route {$trimmedRoute}. Supported methods: "
            .implode(', ', $supportedMethods)
            .'.';
    }
}
