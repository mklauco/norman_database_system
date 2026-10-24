<?php

namespace Tests\Feature\Ecotox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowestPnecIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_renders_with_a_detail_link_template(): void
    {
        $response = $this->get(route('ecotox.lowestpnec.index'));

        $response->assertOk();
        $response->assertSee(route('ecotox.lowestpnec.show', ['sus_id' => '__SUS_ID__']), false);
    }
}
