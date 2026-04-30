<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiController extends Controller
{
    protected function itemResponse(JsonResource $resource, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [
            'data' => $resource->resolve(),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    protected function messageResponse(string $message, array $extra = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            ...$extra,
        ], $status);
    }
}
