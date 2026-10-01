<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Respuesta común de la API: success, message, data y timestamp.
     */
    public static function response(bool $success, string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
