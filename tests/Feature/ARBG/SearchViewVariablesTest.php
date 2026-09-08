<?php

declare(strict_types=1);

namespace Tests\Feature\ARBG;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Search Bacteria and Search Genes result views read displayOption and the
 * four search criteria back out of the request. They used to reach the view
 * only through the array merged into view(), so any URL that left one of them
 * out rendered an undefined variable and returned a 500.
 */
class SearchViewVariablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_gene_search_renders_without_any_query_parameters(): void
    {
        $this->get(route('arbg.gene.search.search'))->assertOk();
    }

    public function test_bacteria_search_renders_without_any_query_parameters(): void
    {
        $this->get(route('arbg.bacteria.search.search'))->assertOk();
    }

    public function test_gene_search_renders_with_only_one_criterion_in_the_url(): void
    {
        $this->get(route('arbg.gene.search.search', ['matrixSearch' => '["12"]']))->assertOk();
    }

    public function test_bacteria_search_renders_with_only_one_criterion_in_the_url(): void
    {
        $this->get(route('arbg.bacteria.search.search', ['matrixSearch' => '["12"]']))->assertOk();
    }

    public function test_gene_search_renders_with_the_criteria_but_no_display_option(): void
    {
        $response = $this->get(route('arbg.gene.search.search', [
            'countrySearch' => '[]',
            'matrixSearch' => '["12"]',
            'geneNameSearch' => '[]',
            'organisationSearch' => '[]',
        ]));

        $response->assertOk();
    }

    public function test_bacteria_search_renders_with_the_criteria_but_no_display_option(): void
    {
        $response = $this->get(route('arbg.bacteria.search.search', [
            'countrySearch' => '[]',
            'matrixSearch' => '["12"]',
            'bacterialGroupSearch' => '[]',
            'organisationSearch' => '[]',
        ]));

        $response->assertOk();
    }
}
