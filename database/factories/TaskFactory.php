<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'due_date' => fake()->optional()->dateTimeBetween('today', '+2 weeks'),
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'is_done' => false,
        ];
    }
}
