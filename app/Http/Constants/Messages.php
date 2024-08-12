<?php

namespace App\Http\Constants;

class Messages
{
    public const BAD_CREDENTIALS = 'Username or password is wrong.';
    public const BAD_REQUEST_DISABLE_ADMIN = 'Cannot disable admin users. Please first change their roles.';
    public const BAD_REQUEST_LAST_ADMIN = 'Cannot remove last admin. At least one must remain.';
    public const INTERNAL_ERROR = 'We are terribly sorry. We had issues on our side :( Please try again. If issues persist, please contact administrator.';
    public const OPENAI_ERROR_MESSAGE = 'Sorry, we are unable to generate content at the moment. Please try again later.';
    public const PAYMENT_REQUIRED = 'You have used free tokens for this month. Please upgrade for further access!';
    public const PREMIUM_CONTENT = 'This feature is exclusively available to our premium users. Please upgrade to access this and more!';

    private function __construct() {}
}
