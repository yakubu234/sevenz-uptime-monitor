<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use App\Services\MonitorRunner;
use Illuminate\Console\Command;

class CheckMonitorsCommand extends Command
{
    protected $signature = 'app:check-monitors';

    protected $description = 'Run uptime checks for monitors that are due';

    public function handle(MonitorRunner $runner): int
    {
        $processed = 0;
        $now = now();

        Monitor::query()
            ->orderBy('id')
            ->each(function (Monitor $monitor) use ($runner, $now, &$processed): void {
                if (! $runner->shouldRun($monitor, $now)) {
                    return;
                }

                $runner->run($monitor, $now);
                $processed++;
            });

        $this->info("Processed {$processed} monitor(s).");

        return self::SUCCESS;
    }
}
