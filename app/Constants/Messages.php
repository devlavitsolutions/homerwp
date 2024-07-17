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
}
