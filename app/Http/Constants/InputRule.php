<?php

namespace App\Http\Constants;

class InputRule
{
    const REQUIRED = 'required';
    const ID = 'required|integer|exists:users,id';
    const EMAIL = 'required|email:rfc,dns|unique:users,email';
    const PASSWORD = 'required|string|min:8';
    const PAID_TOKENS = 'required|integer|gte:0';
    const KEYWORDS = 'required|string';
    const LICENSE_KEY = 'required|string';
    const WEBSITE = 'required|url|unique:activations,website';
    const WEBSITE_EXISTS = 'required|url|exists:activations,website';
    const EXISTING_EMAIL = 'required|email:rfc,dns|exists:users,email';
    const EXISTING_LICENSE_KEY = 'required|string|exists:users,license_key';
    const PAGE = 'sometimes|nullable|integer|min:1';
    const SINGLE_BOOLEAN_CHANGE = 'required|boolean';

    private function __construct()
    {
    }
}
