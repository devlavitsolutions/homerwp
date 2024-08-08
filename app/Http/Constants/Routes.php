<?php

namespace App\Http\Constants;

class Routes
{
    const REGISTER = 'register';
    const LOGIN = 'login';
    const LOGOUT = 'logout';

    const USERS = 'users';
    const USER_ID = 'userId';
    const EMAIL = 'email';
    const TOKENS_COUNT = 'tokensCount';
    const LICENSE_KEY = 'licenseKey';
    const IS_DISABLED = 'isDisabled';
    const IS_ADMIN = 'isAdmin';
    const PASSWORD = 'password';
    const CONTENT = 'content';
    const ACTIVATIONS = 'activations';
    const DELETE = 'delete';

    const OPENAI_BASE_URL = 'https://api.openai.com/v1';
    const THREADS = '/threads';
    const MESSAGES = '/messages';
    const RUNS = '/runs';
}
