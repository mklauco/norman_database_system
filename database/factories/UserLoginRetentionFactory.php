<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Backend\UserLoginRetention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLoginRetention>
 */
class UserLoginRetentionFactory extends Factory
{
    protected $model = UserLoginRetention::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'login_datetime' => now(),
            'meta_data' => ['country' => null],
        ];
    }

    public function at(string $datetime): static
    {
        return $this->state(fn (): array => ['login_datetime' => $datetime]);
    }

    public function fromCountry(?string $code): static
    {
        return $this->state(fn (): array => ['meta_data' => ['country' => $code]]);
    }
}
