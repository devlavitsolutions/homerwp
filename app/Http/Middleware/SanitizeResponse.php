<?php

namespace App\Http\Middleware;

use App\Constants\Defaults;
use App\Http\Constants\Field;
use App\Http\Constants\Messages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SanitizeResponse
{
    protected const BLACKLIST = [
        'exception',
        'file',
        'line',
        'trace',
    ];

    public function handle(Request $request, \Closure $next)
    {
        // Handle the response
        $response = $next($request);

        if (App::environment(Defaults::ENV_PRODUCTION)) {
            if ($response instanceof JsonResponse) {
                $status = $response->status();
                if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                    $response->setData([
                        Field::MESSAGE => Messages::INTERNAL_ERROR,
                    ]);
                }
                $originalContent = $response->getData(true);
                $sanitizedContent = $this->performSanitization($originalContent);
                $response->setData($sanitizedContent);
            }
        }

        return $response;
    }

    protected function performSanitization(array $array): array
    {
        $sanitizedArray = [];

        foreach ($array as $key => $value) {
            if ( ! in_array($key, self::BLACKLIST)) {
                $sanitizedArray[$key] = $value;
            }
        }

        return $sanitizedArray;
    }
}
