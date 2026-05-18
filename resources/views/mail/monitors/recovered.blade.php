The monitored URL {{ $monitor->url }} is back up.

Current status: {{ $monitor->status }}
Checked at: {{ $monitor->last_checked_at?->toIso8601String() }}
