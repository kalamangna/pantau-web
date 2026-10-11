<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Services\TelemetrySyncService;
use App\Services\UptimeCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'email' => 'admin@sinjaikab.go.id',
        ]);
    }

    public function test_admin_can_access_filament_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_websites_resource_page(): void
    {
        Website::create([
            'name' => 'Dinas Komunikasi, Informatika dan Persandian',
            'slug' => 'diskominfo',
            'category' => 'opd',
            'parent_region' => 'Pemerintah Kabupaten Sinjai',
            'domain' => 'diskominfo.sinjaikab.go.id',
            'url' => 'https://diskominfo.sinjaikab.go.id',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/websites');
        $response->assertStatus(200);
        $response->assertSee('Dinas Komunikasi, Informatika dan Persandian');
        $response->assertSee('OPD');
    }

    public function test_uptime_checker_marks_website_online_when_http_is_200(): void
    {
        Http::fake([
            'https://diskominfo.sinjaikab.go.id*' => Http::response('OK', 200),
        ]);

        $website = Website::create([
            'name' => 'Dinas Komunikasi, Informatika dan Persandian',
            'slug' => 'diskominfo',
            'category' => 'opd',
            'domain' => 'diskominfo.sinjaikab.go.id',
            'url' => 'https://diskominfo.sinjaikab.go.id',
            'is_active' => true,
        ]);

        $service = new UptimeCheckerService;
        $result = $service->check($website);

        $this->assertEquals('online', $result['status']);
        $this->assertEquals(200, $result['http_code']);

        $website->refresh();
        $this->assertEquals('online', $website->current_status);
        $this->assertEquals(200, $website->last_http_code);
        $this->assertDatabaseHas('uptime_logs', [
            'website_id' => $website->id,
            'is_online' => true,
            'http_status' => 200,
        ]);
    }

    public function test_uptime_checker_creates_incident_alert_when_offline(): void
    {
        Http::fake([
            'https://diskominfo.sinjaikab.go.id*' => Http::response('Server Error', 500),
        ]);

        $website = Website::create([
            'name' => 'Dinas Komunikasi, Informatika dan Persandian',
            'slug' => 'diskominfo',
            'category' => 'opd',
            'domain' => 'diskominfo.sinjaikab.go.id',
            'url' => 'https://diskominfo.sinjaikab.go.id',
            'current_status' => 'online',
            'is_active' => true,
        ]);

        $service = new UptimeCheckerService;
        $result = $service->check($website);

        $this->assertEquals('offline', $result['status']);

        $this->assertDatabaseHas('incident_alerts', [
            'website_id' => $website->id,
            'alert_type' => 'down',
            'severity' => 'critical',
            'is_resolved' => false,
        ]);
    }

    public function test_telemetry_sync_updates_website_details(): void
    {
        Http::fake([
            'https://tompobulu.desa.id/api/v1/internal/telemetry' => Http::response([
                'status' => 'success',
                'app' => [
                    'name' => 'Website Desa Tompobulu',
                    'cms_version' => '1.28.4',
                    'laravel_version' => '12.0.0',
                    'php_version' => '8.3.10',
                    'debug_mode' => false,
                ],
                'backup' => [
                    'latest_backup_at' => now()->toIso8601String(),
                    'latest_backup_size_mb' => 25.4,
                    'storage_status' => ['local' => ['reachable' => true]],
                ],
                'content_activity' => [
                    'total_posts' => 50,
                    'posts_count_30d' => 6,
                    'latest_post_at' => now()->toIso8601String(),
                ],
                'services_summary' => [
                    'pending_complaints' => 1,
                    'pending_service_requests' => 2,
                    'total_citizens' => 2500,
                    'total_families' => 650,
                ],
                'system_health' => [
                    'database_connection' => true,
                    'storage_writable' => true,
                    'last_cron_executed_at' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        $website = Website::create([
            'name' => 'Desa Tompobulu',
            'slug' => 'desa-tompobulu',
            'category' => 'desa',
            'domain' => 'tompobulu.desa.id',
            'url' => 'https://tompobulu.desa.id',
            'api_key' => 'secret-test-token',
            'is_active' => true,
        ]);

        $service = new TelemetrySyncService;
        $result = $service->sync($website);

        $this->assertEquals('success', $result['status']);

        $website->refresh();
        $this->assertEquals('1.28.4', $website->cms_version);
        $this->assertEquals('success', $website->backup_status);
        $this->assertEquals(6, $website->posts_30d);
        $this->assertEquals(50, $website->total_posts);
        $this->assertEquals(1, $website->pending_complaints);
        $this->assertEquals(2500, $website->total_citizens);

        $this->assertDatabaseHas('telemetry_snapshots', [
            'website_id' => $website->id,
        ]);
    }
}
