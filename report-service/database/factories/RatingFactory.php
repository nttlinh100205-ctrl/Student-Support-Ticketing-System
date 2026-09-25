<?php

namespace Database\Factories;

use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => fake()->unique()->numberBetween(100, 9999),
            'student_id' => fake()->numberBetween(1, 50),
            'department_id' => fake()->numberBetween(1, 5),
            'support_type_id' => fake()->numberBetween(1, 6),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
