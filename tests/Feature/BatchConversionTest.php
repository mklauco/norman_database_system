<?php

namespace Tests\Feature;

use App\Models\Susdat\Substance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_conversion_form_loads()
    {
        $response = $this->get('/susdat/batch');
        $response->assertStatus(200);
        $response->assertSee('Batch Conversion of Identifiers');
    }

    public function test_batch_conversion_with_susdat_id()
    {
        // Create a test substance
        $substance = Substance::factory()->create([
            'code' => '12345',
            'name' => 'Test Substance',
            'cas_number' => '67-56-1',
            'stdinchikey' => 'TEST123',
        ]);

        $response = $this->post('/susdat/batch/convert', [
            'identifiers' => "NS12345\n67890",
            'input_type' => 'susdat_id',
            'exact_match' => true,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Test Substance');
    }

    public function test_batch_conversion_with_exact_match()
    {
        // Create a test substance
        $substance = Substance::factory()->create([
            'code' => '12345',
            'name' => 'Test Substance',
            'cas_number' => '67-56-1',
            'stdinchikey' => 'TEST123',
        ]);

        $response = $this->post('/susdat/batch/convert', [
            'identifiers' => '67-56-1',
            'input_type' => 'cas_no',
            'exact_match' => true,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Test Substance');
    }

    public function test_batch_conversion_with_partial_match()
    {
        // Create a test substance
        $substance = Substance::factory()->create([
            'code' => '12345',
            'name' => 'Test Substance',
            'cas_number' => '67-56-1',
            'stdinchikey' => 'TEST123',
        ]);

        $response = $this->post('/susdat/batch/convert', [
            'identifiers' => 'Test',
            'input_type' => 'substance_name',
            'exact_match' => false,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Test Substance');
    }
}
