<?php

namespace App\Http\Resources;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Monitor */
class MonitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalChecks = $this->total_checks ?? null;
        $upChecks = $this->up_checks_count ?? null;

        $uptimePercentage = null;

        if ($totalChecks !== null && (int) $totalChecks > 0) {
            $uptimePercentage = round(((int) $upChecks / (int) $totalChecks) * 100, 1);
        }

        return [
            'id' => $this->id,
            'url' => $this->url,
            'check_interval' => $this->check_interval,
            'threshold' => $this->threshold,
            'status' => $this->status,
            'last_checked_at' => $this->last_checked_at?->toJSON(),
            'uptime_percentage' => $uptimePercentage,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
