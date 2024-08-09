<?php

namespace App\Http\Constants;

class InputRule
{
    public const EMAIL = 'required|email:rfc,dns|unique:users,email';
    public const EXISTING_EMAIL = 'required|email:rfc,dns|exists:users,email';
    public const EXISTING_LICENSE_KEY = 'required|string|exists:users,license_key';
    public const ID = 'required|integer|exists:users,id';
    public const KEYWORDS = 'required|string';
    public const LICENSE_KEY = 'required|string';
    public const PAGE = 'sometimes|nullable|integer|min:1';
    public const PAID_TOKENS = 'required|integer|gte:0';
    public const PASSWORD = 'required|string|min:8';
    public const REQUIRED = 'required';
    public const SINGLE_BOOLEAN_CHANGE = 'required|boolean';
    public const WEBSITE = 'required|url|unique:activations,website';
    public const WEBSITE_EXISTS = 'required|url|exists:activations,website';

    private function __construct() {}
}
