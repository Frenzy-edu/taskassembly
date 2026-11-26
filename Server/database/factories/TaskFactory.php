<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => fake()->optional(0.65)->dateTimeBetween('today', '+1 month')?->format('Y-m-d'),
            'status' => fake()->randomElement([
                Task::STATUS_PENDING,
                Task::STATUS_COMPLETED,
            ]),
        ];
    }
}
