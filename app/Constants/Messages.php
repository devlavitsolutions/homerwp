<?php

namespace App\Constants;

class Messages {
    const BAD_CREDENTIALS = 'Username or password is wrong.';
    const LOGOUT_SUCCESS = 'Successfully logged out.';
    const BAD_REQUEST_DISABLE_ADMIN = 'Cannot disable admin users. Please first change their roles.';
    const BAD_REQUEST_LAST_ADMIN = 'Cannot remove last admin. At least one must remain.';
}
