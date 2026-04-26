<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $status = 'up';
        $services = [];

        try {
            DB::connection()->getPdo();
            $services['database'] = ['status' => 'up'];
        } catch (\Exception $e) {
            $status = 'down';
            $services['database'] = ['status' => 'down', 'message' => $e->getMessage()];
        }

        try {
            Cache::store('redis')->put('__health__', '1', 5);
            $val = Cache::store('redis')->get('__health__');
            if ($val !== '1') {
                throw new \RuntimeException('Redis read-back failed');
            }
            $services['redis'] = ['status' => 'up'];
        } catch (\Exception $e) {
            $status = 'down';
            $services['redis'] = ['status' => 'down', 'message' => $e->getMessage()];
        }

        return response()->json(
            ['status' => $status, 'services' => $services],
            $status === 'up' ? 200 : 503
        );
    }
}
