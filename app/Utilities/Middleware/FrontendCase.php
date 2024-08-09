<?php

namespace App\Utilities\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrontendCase
{
    protected const CASE_CAMEL = 'camel';
    protected const CASE_SNAKE = 'snake';

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // Convert request keys to camelCase.
        $request->replace($this->convertArrayKeys($request->all(), self::CASE_CAMEL));

        // Handle the response.
        $response = $next($request);

        // Convert response keys to camelCase.
        if ($response instanceof JsonResponse) {
            $originalContent = $response->getData(true);
            $camelCasedContent = $this->convertArrayKeys($originalContent, self::CASE_CAMEL);
            $response->setData($camelCasedContent);
        }

        return $response;
    }

    /**
     * Convert the keys of an array to a specific case.
     */
    protected function convertArrayKeys(array $array, string $case): array
    {
        $convertedArray = [];

        foreach ($array as $key => $value) {
            $newKey = (self::CASE_CAMEL === $case) ? Str::camel($key) : Str::snake($key);

            if (true === is_array($value)) {
                $value = $this->convertArrayKeys($value, $case);
            }

            $convertedArray[$newKey] = $value;
        }

        return $convertedArray;
    }
}
