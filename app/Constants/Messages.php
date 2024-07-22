<?php

namespace App\Constants;

class Messages
{
    const BAD_CREDENTIALS = 'Username or password is wrong.';
    const LOGOUT_SUCCESS = 'Successfully logged out.';
    const BAD_REQUEST_DISABLE_ADMIN = 'Cannot disable admin users. Please first change their roles.';
    const BAD_REQUEST_LAST_ADMIN = 'Cannot remove last admin. At least one must remain.';
    const INTERNAL_ERROR = 'We are terribly sorry. We had issues on our side :( Please try again. If issues persist, please contact administrator.';
    const OPENAI_ERROR_MESSAGE = 'Sorry, we are unable to generate content at the moment. Please try again later.';
    const USER_NOT_OWNER_OF_KEY = 'Selected user does not own the provided license key.';
    const PREMIUM_CONTENT = 'This feature is exclusively available to our premium users. Please upgrade to access this and more!';
    const PAYMENT_REQUIRED = 'You have used free tokens for this month. Please upgrade for further access!';
}
