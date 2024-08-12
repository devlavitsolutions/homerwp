<?php

namespace Tests\Consts;

class TestStatusCodes
{
    public const CREATED = 201;
    public const FORBIDDEN = 403;
    public const METHOD_NOT_SUPPORTED = 405;
    public const NO_CONTENT = 204;
    public const OK = 200;
    public const UNAUTHORIZED = 401;
    public const UNPROCESSABLE_ENTITY = 422;

    private function __construct() {}
}
