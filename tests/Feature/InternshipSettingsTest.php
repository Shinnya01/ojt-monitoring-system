<?php

namespace Tests\Feature;

use App\Models\InternshipSetting;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InternshipSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_internship_settings_can_be_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('internship-settings.store'), [
                'start_date' => '2026-03-01',
                'required_hours' => 486,
                'regular_workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'default_start_time' => '09:00',
                'default_end_time' => '18:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_settings', [
            'user_id' => $user->id,
            'required_hours' => 486,
            'default_start_time' => '09:00:00',
            'default_end_time' => '18:00:00',
        ]);
    }

    public function test_internship_settings_can_be_updated(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'required_hours' => 300,
            'default_start_time' => '08:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('internship-settings.store'), [
                'start_date' => '2026-03-02',
                'required_hours' => 600,
                'regular_workdays' => ['monday', 'wednesday'],
                'default_start_time' => '10:00',
                'default_end_time' => '19:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('internship_settings', [
            'user_id' => $user->id,
            'required_hours' => 600,
            'default_start_time' => '10:00:00',
            'default_end_time' => '19:00:00',
        ]);
    }

    public function test_invalid_internship_settings_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('internship-settings.store'), [
                'start_date' => '2026-03-02',
                'required_hours' => 0,
                'regular_workdays' => [],
                'default_start_time' => '18:00',
                'default_end_time' => '09:00',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'required_hours',
                'regular_workdays',
                'default_end_time',
            ]);
    }

    public function test_completed_setup_disables_dashboard_prompt(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('showSetupDialog', false),
            );
    }

    public function test_expected_end_date_is_projected_from_attendance_pace(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'start_date' => '2026-03-03',
            'required_hours' => 20,
            'regular_workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'default_start_time' => '09:00:00',
            'default_end_time' => '18:00:00',
        ]);

        WorkSession::factory()->for($user)->create([
            'date' => '2026-03-03',
            'start_time' => '2026-03-03 09:00:00',
            'end_time' => '2026-03-03 14:00:00',
            'duration_minutes' => 300,
        ]);

        WorkSession::factory()->for($user)->create([
            'date' => '2026-03-04',
            'start_time' => '2026-03-04 09:00:00',
            'end_time' => '2026-03-04 14:00:00',
            'duration_minutes' => 300,
        ]);

        $this->travelTo(Carbon::create(2026, 3, 5, 9, 0));

        $this->actingAs($user)
            ->get(route('hr-counter'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('internshipSettings.expectedEndDate', '2026-03-06'),
            );
    }
}
