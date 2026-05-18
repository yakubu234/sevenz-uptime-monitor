<?php

namespace App\Services;

class UptimeCheckResult
{
    public function __construct(
        public readonly int $statusCode,
        public readonly ?int $responseTimeMs,
        public readonly bool $isUp,
    ) {
    }
}
