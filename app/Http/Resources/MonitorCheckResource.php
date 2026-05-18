<?php

namespace App\Http\Resources;

use App\Models\MonitorCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MonitorCheck */
class MonitorCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monitor_id' => $this->monitor_id,
            'status_code' => $this->status_code,
            'response_time_ms' => $this->response_time_ms,
            'is_up' => $this->is_up,
            'checked_at' => $this->checked_at->toJSON(),
        ];
    }
}
