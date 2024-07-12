<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrontendCase
{
    public const CASE_SNAKE = 'snake';
    public const CASE_CAMEL = 'camel';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Convert request keys to snake_case
        $request->replace($this->convertArrayKeys($request->all(), self::CASE_SNAKE));

        // Handle the response
        $response = $next($request);

        // Convert response keys to camelCase
        if ($response instanceof JsonResponse) {
            $originalContent = $response->getData(true);
            $camelCasedContent = $this->convertArrayKeys($originalContent, self::CASE_CAMEL);
            $response->setData($camelCasedContent);
        }

        return $response;
    }

    /**
     * Convert the keys of an array to a specific case.
     *
     * @param  array  $array
     * @param  string  $case
     * @return array
     */
    protected function convertArrayKeys(array $array, string $case): array
    {
        $convertedArray = [];

        foreach ($array as $key => $value) {
            $newKey = $case === self::CASE_CAMEL ? Str::camel($key) : Str::snake($key);

            if (is_array($value)) {
                $value = $this->convertArrayKeys($value, $case);
            }

            $convertedArray[$newKey] = $value;
        }

        return $convertedArray;
    }
}
