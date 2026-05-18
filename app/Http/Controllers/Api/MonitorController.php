<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;

class MonitorController extends Controller
{
    public function index(): JsonResponse
    {
        $monitors = Monitor::query()
            ->withCount([
                'checks as total_checks',
                'checks as up_checks_count' => fn ($query) => $query->where('is_up', true),
            ])
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => MonitorResource::collection($monitors)->resolve(),
        ]);
    }

    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $monitor = Monitor::query()->create([
            'url' => $request->string('url')->toString(),
            'check_interval' => $request->integer('check_interval', 5),
            'threshold' => $request->integer('threshold', 3),
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => MonitorResource::make($monitor)->resolve(),
        ], 201);
    }
}
