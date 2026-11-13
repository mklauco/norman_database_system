<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class MultiselectComponentTest extends TestCase
{
    public function test_it_renders_hidden_input_initial_selection_and_escaped_options(): void
    {
        $view = $this->blade(
            '<x-multiselect name="countrySearch" :options="$options" :selected="$selected" />',
            [
                'options' => [
                    ['id' => 1, 'name' => 'Bosnia & "Herzegovina"'],
                    ['id' => 2, 'label' => "O'Brien </script> \\ path"],
                    ['id' => 3, 'title' => 'Plain'],
                ],
                'selected' => '["2","3"]',
            ]
        );

        $view->assertSee('name="countrySearch"', false);
        $view->assertSee('value="[&quot;2&quot;,&quot;3&quot;]"', false);
        $view->assertSee('<script type="application/json" x-ref="options">', false);
        $safeFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $view->assertSee(trim(json_encode('Bosnia & "Herzegovina"', $safeFlags), '"'), false);
        $view->assertSee(trim(json_encode("O'Brien </script> \\ path", $safeFlags), '"'), false);
        $view->assertDontSee('Bosnia & "Herzegovina"', false);
        $view->assertDontSee("O'Brien </script>", false);
        $view->assertSee('aria-label="Country"', false);
    }

    public function test_it_accepts_a_simple_key_label_list_and_array_selection(): void
    {
        $view = $this->blade(
            '<x-multiselect name="matrixSearch" label="Matrix" :options="$options" :selected="$selected" />',
            [
                'options' => [10 => 'Water', 20 => 'Soil'],
                'selected' => [20],
            ]
        );

        $view->assertSee('value="[&quot;20&quot;]"', false);
        $view->assertSee('"value":"10","label":"Water"', false);
        $view->assertSee('aria-label="Matrix"', false);
    }
}
