<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'unhealthy',
                'database' => 'unreachable',
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'database' => 'ok',
        ]);
    }
}
