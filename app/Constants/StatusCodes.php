<?php

namespace App\Constants;

enum StatusCodes: int {
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;

    case UNAUTHORIZED = 401;
    case UNPROCESSABLE = 422;
}