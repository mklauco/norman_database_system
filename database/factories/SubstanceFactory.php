<?php

namespace Database\Factories;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubstanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Substance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->unique()->numberBetween(10000, 99999),
            'name' => $this->faker->unique()->word.' '.$this->faker->word,
            'name_dashboard' => $this->faker->unique()->word.' '.$this->faker->word,
            'name_chemspider' => $this->faker->unique()->word.' '.$this->faker->word,
            'name_iupac' => $this->faker->unique()->word.' '.$this->faker->word,
            'cas_number' => $this->faker->unique()->numberBetween(100, 999).'-'.
                           $this->faker->numberBetween(10, 99).'-'.
                           $this->faker->numberBetween(1, 9),
            'smiles' => $this->faker->regexify('[A-Z]{10}'),
            'smiles_dashboard' => $this->faker->regexify('[A-Z]{10}'),
            'stdinchi' => $this->faker->regexify('[A-Z]{20}'),
            'stdinchikey' => $this->faker->regexify('[A-Z]{27}'),
            'pubchem_cid' => $this->faker->unique()->numberBetween(1000000, 9999999),
            'chemspider_id' => $this->faker->unique()->numberBetween(100000, 999999),
            'dtxid' => 'DTXID'.$this->faker->unique()->numberBetween(100000, 999999),
            'molecular_formula' => $this->faker->regexify('[A-Z][a-z][0-9]{2}'),
            'mass_iso' => $this->faker->randomFloat(2, 50, 500),
            'metadata_synonyms' => [],
            'metadata_cas' => [],
            'metadata_ms_ready' => [],
            'metadata_general' => [],
            'added_by' => 1,
        ];
    }
}
