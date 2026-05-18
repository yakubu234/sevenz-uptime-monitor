<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MonitorHistoryRequest;
use App\Http\Resources\MonitorCheckResource;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;

class MonitorHistoryController extends Controller
{
    public function index(MonitorHistoryRequest $request, int $id): JsonResponse
    {
        $monitor = Monitor::query()->find($id);

        if (! $monitor) {
            return response()->json([
                'message' => 'Monitor not found.',
            ], 404);
        }

        $perPage = $request->integer('per_page', 15);
        $history = $monitor->checks()
            ->orderByDesc('checked_at')
            ->paginate($perPage);

        return response()->json([
            'data' => MonitorCheckResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
