<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DatabaseEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatabaseEntityFactory extends Factory
{
    protected $model = DatabaseEntity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'image_path' => null,
            'code' => $this->faker->unique()->slug(2),
            'dashboard_route_name' => null,
            'last_update' => now(),
            'number_of_records' => 0,
            'parent_id' => null,
            'show_in_dashboard' => true,
            'has_templates' => false,
            'is_public' => true,
        ];
    }

    public function public(): self
    {
        return $this->state(fn () => ['is_public' => true]);
    }

    public function private(): self
    {
        return $this->state(fn () => ['is_public' => false]);
    }
}
