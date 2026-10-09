<?php

namespace Tests\Feature\Services;

use App\Services\Clients\RequestServiceClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ServiceClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.request_service.mock' => false, 'services.request_service.url' => 'http://requests.test']);
        Http::preventStrayRequests();
        Route::get('/api/integration-report', fn (RequestServiceClient $client) => $client->getRequests(['staff_id' => 7]));
    }

    public function test_fetches_all_pages_and_maps_assignee_fields(): void
    {
        Http::fake([
            'http://requests.test/api/requests*' => Http::sequence()
                ->push(['data' => [['id' => 10, 'assigned_to' => 7, 'assigned_staff_name' => 'Staff']], 'meta' => ['last_page' => 2]])
                ->push(['data' => [['id' => 11, 'assigned_to' => 7]], 'meta' => ['last_page' => 2]]),
        ]);
        $this->withToken('verified')->getJson('/api/integration-report')
            ->assertOk()->assertJsonCount(2)->assertJsonPath('0.staff_id', 7)->assertJsonPath('1.id', 11);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => (int) $request['assigned_to'] === 7 && (int) $request['page'] === 2 && $request->hasHeader('Authorization', 'Bearer verified'));
    }

    public function test_returns_503_on_upstream_failure_instead_of_mock_records(): void
    {
        Http::fake(['http://requests.test/api/requests*' => Http::response([], 500)]);
        $this->withToken('verified')->getJson('/api/integration-report')->assertStatus(503);
        Http::assertSentCount(1);
    }
}
