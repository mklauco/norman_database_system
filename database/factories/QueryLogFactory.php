<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Backend\QueryLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueryLog>
 */
class QueryLogFactory extends Factory
{
    protected $model = QueryLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => json_encode(['search' => $this->faker->word()]),
            'query' => 'select 1',
            'user_id' => null,
            'total_count' => $this->faker->numberBetween(0, 1000),
            'actual_count' => $this->faker->numberBetween(0, 1000),
            'database_key' => 'empodat',
            'query_hash' => null,
        ];
    }

    public function forModule(?string $databaseKey): static
    {
        return $this->state(fn (): array => ['database_key' => $databaseKey]);
    }

    public function forUser(?int $userId): static
    {
        return $this->state(fn (): array => ['user_id' => $userId]);
    }

    public function at(string $datetime): static
    {
        return $this->state(fn (): array => ['created_at' => $datetime, 'updated_at' => $datetime]);
    }
}
