<?php

namespace Tests\Consts;

class TestResponseMessages
{
    const WRONG_CREDENTIALS = 'Username or password is wrong.';
    const BAD_EMAIL = 'The email field must be a valid email address.';
    const BAD_USER_ID = 'The selected user id is invalid.';
    const BAD_LICENSE_KEY = 'The selected license key is invalid.';
    const ADMIN_USERS_MUST_BE_ENABLED = 'Cannot disable admin users. Please first change their roles.';
    const AT_LEAST_ONE_ADMIN_NEEDED = 'Cannot remove last admin. At least one must remain.';
    const EMAIL_ALREADY_TAKEN = 'The email has already been taken.';
    const REQUIRE_NON_NEGATIVE_TOKENS = 'The paid tokens field must be greater than or equal to 0.';
    const SHORT_PASSWORD = 'The password field must be at least 8 characters.';
    const UNAUTHORIZED = 'Unauthenticated.';
    const LOGGED_OUT = "Successfully logged out.";
    public static function METHOD_NOT_SUPPORTED(
        string $unsupportedMethod,
        string $route,
        string ...$supportedMethods
    ) {
        $trimmedRoute = ltrim($route, '/');
        return "The {$unsupportedMethod} method is not supported for route {$trimmedRoute}. Supported methods: "
            . implode(', ', $supportedMethods)
            . '.';
    }
}
