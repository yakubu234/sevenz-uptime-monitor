<?php

namespace Tests\Feature;

use App\Mail\MonitorDownMail;
use App\Mail\MonitorRecoveredMail;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckMonitorsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_successful_check_and_marks_monitor_up(): void
    {
        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        $this->artisan('app:check-monitors')
            ->expectsOutput('Processed 1 monitor(s).')
            ->assertSuccessful();

        $monitor->refresh();

        $this->assertSame('up', $monitor->status);
        $this->assertSame(0, $monitor->consecutive_failures);
        $this->assertDatabaseHas('monitor_checks', [
            'monitor_id' => $monitor->id,
            'status_code' => 200,
            'is_up' => true,
        ]);
    }

    public function test_it_uses_zero_status_code_for_connection_failures(): void
    {
        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 1,
            'status' => 'pending',
        ]);

        Http::fake(function (): void {
            throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
        });

        $this->artisan('app:check-monitors')->assertSuccessful();

        $this->assertDatabaseHas('monitor_checks', [
            'monitor_id' => $monitor->id,
            'status_code' => 0,
            'response_time_ms' => null,
            'is_up' => false,
        ]);
    }

    public function test_it_marks_monitor_down_only_after_reaching_threshold_and_sends_email(): void
    {
        config()->set('services.uptime.alert_email', 'alerts@example.com');

        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 2,
            'status' => 'up',
        ]);

        Mail::fake();
        Http::fake([
            'https://example.com' => Http::response('', 500),
        ]);

        $this->artisan('app:check-monitors')->assertSuccessful();
        $monitor->refresh();

        $this->assertSame('up', $monitor->status);
        $this->assertSame(1, $monitor->consecutive_failures);
        Mail::assertNothingSent();

        $monitor->forceFill([
            'last_checked_at' => now()->subMinutes(10),
        ])->save();

        $this->artisan('app:check-monitors')->assertSuccessful();
        $monitor->refresh();

        $this->assertSame('down', $monitor->status);
        $this->assertSame(2, $monitor->consecutive_failures);
        Mail::assertSent(MonitorDownMail::class, 1);
    }

    public function test_it_sends_recovery_email_when_a_down_monitor_comes_back_up(): void
    {
        config()->set('services.uptime.alert_email', 'alerts@example.com');

        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 1,
            'status' => 'down',
            'consecutive_failures' => 1,
        ]);

        Mail::fake();
        Http::fake([
            'https://example.com' => Http::response('', 302),
        ]);

        $this->artisan('app:check-monitors')->assertSuccessful();

        $monitor->refresh();

        $this->assertSame('up', $monitor->status);
        $this->assertSame(0, $monitor->consecutive_failures);
        Mail::assertSent(MonitorRecoveredMail::class, 1);
    }
}
