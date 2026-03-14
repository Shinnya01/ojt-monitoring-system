<?php

namespace Tests\Feature;

use App\Models\InternshipSetting;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_shows_summary_only_props(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'required_hours' => 300,
        ]);

        WorkSession::factory()->for($user)->create([
            'date' => '2026-03-14',
            'start_time' => '2026-03-14 09:00:00',
            'end_time' => '2026-03-14 17:00:00',
            'break_minutes' => 60,
            'duration_minutes' => 420,
        ]);

        Task::factory()->for($user)->create([
            'is_done' => false,
        ]);

        $this->travelTo(Carbon::create(2026, 3, 14, 18, 0));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.liveCompletedMinutes', 420)
                ->where('taskSummary.pending', 1)
                ->where('internshipSettings.requiredHours', 300)
                ->where('showSetupDialog', false)
                ->has('activeSession')
                ->missing('recentSessions')
                ->missing('tasks'),
            );
    }

    public function test_dashboard_prompts_for_setup_when_not_completed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('showSetupDialog', true)
                ->where('internshipSettings', null),
            );
    }
}
