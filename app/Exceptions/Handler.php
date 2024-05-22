<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\QueryException;

use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (QueryException $e, $request) {
            // Handle unique constraint violation
            if ($e->getCode() == 23000) {
                return response()->json([
                    'message' => 'Database error: Duplicate entry or constraint violation',
                ], 422);
            }

            // Handle other database-related errors
            return response()->json([
                'message' => 'Database error occurred',
            ], 500);
        });
    }
    /**
     * Register the exception handling callbacks for the application.
     */
    /*public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        });
        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'Record  not found'
            ], 404);
        });
        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);


        });

        $this->renderable(function (QueryException $e, $request) {
            // Handle unique constraint violation
            if ($e->getCode() == 23000) { // SQLSTATE[23000]: Integrity constraint violation
                return response()->json([
                    'message' => 'Database error: Duplicate entry or constraint violation'
                ], 422);
            }
             // Handle other database-related errors
             return response()->json([
                'message' => 'Database error occurred'
            ], 500);
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 401) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            if ($e->getStatusCode() === 404) {
                return response()->json([
                    'message' => 'Record  not found'
                ], 404);
            }

            if ($e->getStatusCode() === 422) {
                return response()->json([
                    'message' => 'Validation failed'
                ], 422);
            }

            // For other HTTP exceptions, handle generically or add more specific cases as needed
            return response()->json([
                'message' => $e->getMessage() ?: 'An error occurred'
            ], $e->getStatusCode());
        });

    }*/
}
