<?php

namespace App\Services;

use App\Models\Monitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MonitorRunner
{
    public function __construct(
        private readonly UptimeCheckService $uptimeCheckService,
        private readonly MonitorNotificationService $notificationService,
    ) {
    }

    public function shouldRun(Monitor $monitor, ?CarbonInterface $now = null): bool
    {
        if ($monitor->last_checked_at === null) {
            return true;
        }

        $now ??= now();

        return $monitor->last_checked_at
            ->copy()
            ->addMinutes($monitor->check_interval)
            ->lessThanOrEqualTo($now);
    }

    public function run(Monitor $monitor, ?CarbonInterface $checkedAt = null): void
    {
        $checkedAt = $checkedAt ? Carbon::instance($checkedAt) : now();
        $result = $this->uptimeCheckService->check($monitor);

        $monitor->checks()->create([
            'status_code' => $result->statusCode,
            'response_time_ms' => $result->responseTimeMs,
            'is_up' => $result->isUp,
            'checked_at' => $checkedAt,
        ]);

        $previousStatus = $monitor->status;
        $nextStatus = $previousStatus;
        $consecutiveFailures = $result->isUp ? 0 : $monitor->consecutive_failures + 1;

        if ($result->isUp) {
            $nextStatus = 'up';
        } elseif ($consecutiveFailures >= $monitor->threshold) {
            $nextStatus = 'down';
        }

        $monitor->forceFill([
            'status' => $nextStatus,
            'last_checked_at' => $checkedAt,
            'consecutive_failures' => $consecutiveFailures,
            'last_status_change_at' => $nextStatus !== $previousStatus ? $checkedAt : $monitor->last_status_change_at,
        ])->save();

        if ($previousStatus !== 'down' && $nextStatus === 'down') {
            $this->notificationService->sendDown($monitor->fresh());
        }

        if ($previousStatus === 'down' && $nextStatus === 'up') {
            $this->notificationService->sendRecovered($monitor->fresh());
        }
    }
}
