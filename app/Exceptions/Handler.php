<?php

namespace App\Exceptions;

use App\Constants\Labels;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Get the status code for the exception.
     *
     * @param  Throwable  $exception
     * @return int
     */
    private function getExceptionStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response
     */
    // public function render($request, Throwable $e)
    // {
    //     $response = [
    //         Labels::MESSAGE => $e->getMessage(),
    //     ];

    //     if ($e instanceof ValidationException) {
    //         $response[Labels::ERRORS] = $e->errors();
    //     }

    //     return response()->json(
    //         $response,
    //         $this->getExceptionStatusCode($e),
    //     );
    // }
}
