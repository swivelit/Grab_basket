<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
    {
        $this->renderable(function (Throwable $e, $request) {
            // If the exception is the missing PHP intl extension runtime error,
            // show a clear instructions page instead of a generic 500.
            if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'intl')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Server misconfiguration: PHP "intl" extension is missing. Please enable it on the server.'
                    ], 500);
                }

                return response()->view('errors.missing_intl', [], 500);
            }

            // Fallback: existing generic behavior
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Oops! Something went wrong. Please try again later.'
                ], 500);
            }

            return response()->view('errors.custom', [
                'message' => 'Oops! Something went wrong. Please try again later.'
            ], 500);
        });
    }
}
