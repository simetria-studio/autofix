<?php

namespace Database\Factories;

use App\Models\Error;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Error>
 */
class ErrorFactory extends Factory
{
    protected $model = Error::class;

    public function definition(): array
    {
        return [
            'fingerprint' => null,
            'server_name' => fake()->optional()->domainName(),
            'log_source' => fake()->randomElement(['server', 'application']),
            'message' => fake()->sentence(),
            'solution' => 'Análise manual necessária',
            'resolved' => false,
            'occurrence_count' => 1,
        ];
    }
}
