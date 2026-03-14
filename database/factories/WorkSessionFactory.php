<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WorkSession>
 */
class WorkSessionFactory extends Factory
{
    protected $model = WorkSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = Carbon::instance(fake()->dateTimeBetween('-1 week', 'now'))->setSecond(0);
        $endTime = (clone $startTime)->addHours(8);

        return [
            'user_id' => User::factory(),
            'date' => $startTime->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => 60,
            'duration_minutes' => $startTime->diffInMinutes($endTime) - 60,
            'notes' => fake()->sentence(),
        ];
    }

    public function running(): static
    {
        return $this->state(function (array $attributes): array {
            $startTime = $attributes['start_time'] instanceof Carbon
                ? $attributes['start_time']
                : Carbon::parse($attributes['start_time']);

            return [
                'date' => $startTime->toDateString(),
                'end_time' => null,
                'duration_minutes' => null,
            ];
        });
    }
}
