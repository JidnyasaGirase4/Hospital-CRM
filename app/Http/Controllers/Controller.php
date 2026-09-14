<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    use AuthorizesRequests;

    protected function success(mixed $data = null, string $message = 'Success', int $status = 200, array $meta = [])
    {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    protected function error(string $message = 'Something went wrong', int $status = 400, mixed $errors = null)
    {
        return ApiResponse::error($message, $status, $errors);
    }

    /**
     * Standard envelope for a paginated index response: resourceClass wraps
     * each item, pagination metadata goes under meta.pagination.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'Retrieved successfully')
    {
        return $this->success($resourceClass::collection($paginator->items()), $message, 200, [
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
