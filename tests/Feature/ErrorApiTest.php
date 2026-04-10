<?php

namespace Tests\Feature;

use App\Models\Error;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Extensão pdo_sqlite necessária para os testes com RefreshDatabase (habilite em php.ini).');
        }

        parent::setUp();
    }

    public function test_store_persists_error_and_returns_solution(): void
    {
        $response = $this->postJson('/api/errors', [
            'message' => 'SQLSTATE[HY000] test',
            'server_name' => 'srv1.test',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('deduplicated', false);
        $response->assertJsonPath('occurrence_count', 1);
        $this->assertStringContainsStringIgnoringCase('banco', $response->json('solution'));

        $this->assertDatabaseHas('errors', [
            'server_name' => 'srv1.test',
            'log_source' => 'server',
            'occurrence_count' => 1,
        ]);
    }

    public function test_store_validates_message_required(): void
    {
        $this->postJson('/api/errors', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_store_deduplicates_same_fingerprint_within_window(): void
    {
        $payload = [
            'message' => 'Same nginx error line',
            'server_name' => 'web01',
        ];

        $first = $this->postJson('/api/errors', $payload);
        $first->assertCreated();
        $id = $first->json('id');

        $second = $this->postJson('/api/errors', $payload);
        $second->assertOk();
        $second->assertJsonPath('id', $id);
        $second->assertJsonPath('deduplicated', true);
        $second->assertJsonPath('occurrence_count', 2);

        $this->assertDatabaseCount('errors', 1);
    }

    public function test_store_accepts_log_source_application(): void
    {
        $this->postJson('/api/errors', [
            'message' => 'Same text',
            'server_name' => 'x',
            'log_source' => 'application',
        ])->assertCreated();

        $this->postJson('/api/errors', [
            'message' => 'Same text',
            'server_name' => 'x',
            'log_source' => 'server',
        ])->assertCreated();

        $this->assertDatabaseCount('errors', 2);
    }

    public function test_index_paginates_errors(): void
    {
        Error::factory()->count(3)->create();

        $response = $this->getJson('/api/errors?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
    }
}
