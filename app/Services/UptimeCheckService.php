<?php

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UptimeCheckService
{
    public function check(Monitor $monitor): UptimeCheckResult
    {
        $startedAt = microtime(true);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(10)
                ->withOptions(['allow_redirects' => false])
                ->get($monitor->url);
        } catch (ConnectionException) {
            return new UptimeCheckResult(
                statusCode: 0,
                responseTimeMs: null,
                isUp: false,
            );
        }

        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
        $statusCode = $response->status();

        return new UptimeCheckResult(
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            isUp: $statusCode >= 200 && $statusCode < 400,
        );
    }
}
