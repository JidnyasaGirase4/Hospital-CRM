<?php

namespace App\Http\Responses;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Single place that shapes every API response so the frontend can rely on
 * a consistent { success, message, data, errors } envelope in all cases.
 */
class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data instanceof JsonResource ? $data : $data,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(
        string $message = 'Something went wrong',
        int $status = 400,
        mixed $errors = null,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $status);
    }

    public static function fromException(Throwable $e): JsonResponse
    {
        $debug = App::hasDebugModeEnabled();

        return match (true) {
            $e instanceof ValidationException => self::error(
                'Validation failed',
                422,
                $e->errors()
            ),

            $e instanceof AuthenticationException => self::error(
                'Unauthenticated',
                401
            ),

            $e instanceof AuthorizationException => self::error(
                $e->getMessage() ?: 'This action is unauthorized',
                403
            ),

            $e instanceof ModelNotFoundException => self::error(
                'Resource not found',
                404
            ),

            $e instanceof NotFoundHttpException => self::error(
                'Endpoint not found',
                404
            ),

            $e instanceof HttpExceptionInterface => self::error(
                $e->getMessage() ?: 'Request failed',
                $e->getStatusCode()
            ),

            default => self::error(
                $debug ? $e->getMessage() : 'Internal server error',
                500,
                $debug ? [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(10)->toArray(),
                ] : null
            ),
        };
    }
}
