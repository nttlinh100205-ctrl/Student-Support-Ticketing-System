<?php

namespace Database\Factories;

use App\Models\SupportRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportRequestFactory extends Factory
{
    protected $model = SupportRequest::class;

    public function definition(): array
    {
        return [
            'code' => 'YC-'.now()->format('Y').'-'.$this->faker->unique()->numerify('######'),
            'student_id' => $this->faker->numberBetween(1, 50),
            'department_id' => $this->faker->numberBetween(1, 5),
            'support_type_id' => $this->faker->numberBetween(1, 8),
            'assigned_to' => null,
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => 'new',
            'cancelled_reason' => null,
        ];
    }
}
