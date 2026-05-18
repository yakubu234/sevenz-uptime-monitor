The monitored URL {{ $monitor->url }} is currently down.

Current status: {{ $monitor->status }}
Checked at: {{ $monitor->last_checked_at?->toIso8601String() }}
