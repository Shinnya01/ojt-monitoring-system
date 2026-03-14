<?php

namespace Database\Factories;

use App\Models\InternshipSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipSetting>
 */
class InternshipSettingFactory extends Factory
{
    protected $model = InternshipSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'start_date' => fake()->dateTimeBetween('-2 months', 'today'),
            'required_hours' => 486,
            'regular_workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'default_start_time' => '09:00:00',
            'default_end_time' => '18:00:00',
            'setup_completed_at' => now(),
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (): array => [
            'start_date' => null,
            'required_hours' => null,
            'regular_workdays' => null,
            'default_start_time' => null,
            'default_end_time' => null,
            'setup_completed_at' => null,
        ]);
    }
}
