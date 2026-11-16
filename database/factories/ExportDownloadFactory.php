<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Backend\ExportDownload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportDownload>
 */
class ExportDownloadFactory extends Factory
{
    protected $model = ExportDownload::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'filename' => $this->faker->unique()->lexify('export_????????').'.csv',
            'format' => 'csv',
            'ip_address' => $this->faker->ipv4(),
            'record_count' => $this->faker->numberBetween(1, 1000),
            'database_key' => 'empodat',
            'status' => 'completed',
        ];
    }

    public function forModule(string $databaseKey): static
    {
        return $this->state(fn (): array => ['database_key' => $databaseKey]);
    }

    public function forUser(?int $userId): static
    {
        return $this->state(fn (): array => ['user_id' => $userId]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => 'failed']);
    }

    public function at(string $datetime): static
    {
        return $this->state(fn (): array => ['created_at' => $datetime, 'updated_at' => $datetime]);
    }
}
