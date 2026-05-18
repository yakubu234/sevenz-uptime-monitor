<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_monitor_with_defaults(): void
    {
        $response = $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'url' => 'https://example.com',
                    'check_interval' => 5,
                    'threshold' => 3,
                    'status' => 'pending',
                    'last_checked_at' => null,
                    'uptime_percentage' => null,
                ],
            ]);

        $this->assertDatabaseHas('monitors', [
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);
    }

    public function test_it_rejects_duplicate_urls(): void
    {
        Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/monitors', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_it_lists_monitors_with_uptime_percentage(): void
    {
        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'up',
            'last_checked_at' => now(),
        ]);

        MonitorCheck::query()->insert([
            [
                'monitor_id' => $monitor->id,
                'status_code' => 200,
                'response_time_ms' => 100,
                'is_up' => true,
                'checked_at' => now()->subMinutes(2),
            ],
            [
                'monitor_id' => $monitor->id,
                'status_code' => 500,
                'response_time_ms' => 200,
                'is_up' => false,
                'checked_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->getJson('/api/monitors');

        $response->assertOk()
            ->assertJsonPath('data.0.uptime_percentage', 50)
            ->assertJsonPath('data.0.status', 'up');
    }

    public function test_it_returns_history_in_descending_order_with_meta(): void
    {
        $monitor = Monitor::query()->create([
            'url' => 'https://example.com',
            'check_interval' => 5,
            'threshold' => 3,
            'status' => 'up',
        ]);

        $older = $monitor->checks()->create([
            'status_code' => 200,
            'response_time_ms' => 150,
            'is_up' => true,
            'checked_at' => now()->subMinutes(2),
        ]);

        $newer = $monitor->checks()->create([
            'status_code' => 500,
            'response_time_ms' => 400,
            'is_up' => false,
            'checked_at' => now()->subMinute(),
        ]);

        $response = $this->getJson("/api/monitors/{$monitor->id}/history?per_page=15");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_returns_custom_not_found_for_missing_monitor_history(): void
    {
        $this->getJson('/api/monitors/999/history')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Monitor not found.',
            ]);
    }
}
