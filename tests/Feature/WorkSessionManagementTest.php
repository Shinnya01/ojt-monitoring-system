<?php

namespace Tests\Feature;

use App\Models\InternshipSetting;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_counter_page_uses_saved_default_times(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'default_start_time' => '08:00:00',
            'default_end_time' => '17:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('hr-counter'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HrCounter')
                ->where('manualEntryDefaults.startTime', '08:00')
                ->where('manualEntryDefaults.endTime', '17:30'),
            );
    }

    public function test_clock_in_creates_an_active_session(): void
    {
        $user = User::factory()->create();

        $this->travelTo(Carbon::create(2026, 3, 14, 9, 0));

        $this->actingAs($user)
            ->post(route('work-sessions.clock-in'))
            ->assertRedirect(route('hr-counter'));

        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $user->id,
            'date' => '2026-03-14',
            'end_time' => null,
            'break_minutes' => 0,
        ]);
    }

    public function test_second_clock_in_is_blocked_when_a_session_is_already_running(): void
    {
        $user = User::factory()->create();

        WorkSession::factory()->for($user)->running()->create([
            'date' => '2026-03-14',
            'start_time' => '2026-03-14 09:00:00',
        ]);

        $this->actingAs($user)
            ->from(route('hr-counter'))
            ->post(route('work-sessions.clock-in'))
            ->assertRedirect(route('hr-counter'))
            ->assertSessionHasErrors('clock');

        $this->assertSame(1, $user->workSessions()->count());
    }

    public function test_clock_out_closes_the_active_session_and_computes_duration(): void
    {
        $user = User::factory()->create();

        $this->travelTo(Carbon::create(2026, 3, 14, 9, 0));

        $session = WorkSession::factory()->for($user)->running()->create([
            'date' => '2026-03-14',
            'start_time' => Carbon::create(2026, 3, 14, 9, 0),
            'break_minutes' => 0,
        ]);

        $this->travelTo(Carbon::create(2026, 3, 14, 17, 0));

        $this->actingAs($user)
            ->post(route('work-sessions.clock-out'), [
                'break_minutes' => 30,
                'notes' => 'Worked on onboarding docs.',
            ])
            ->assertRedirect(route('hr-counter'));

        $session->refresh();

        $this->assertNotNull($session->end_time);
        $this->assertSame(30, $session->break_minutes);
        $this->assertSame(450, $session->duration_minutes);
        $this->assertSame('Worked on onboarding docs.', $session->notes);
    }

    public function test_manual_entry_stores_duration_with_break_deduction(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('work-sessions.store'), [
                'date' => '2026-03-14',
                'start_time' => '08:30',
                'end_time' => '17:00',
                'break_minutes' => 45,
                'notes' => 'Training and ticket review.',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $user->id,
            'date' => '2026-03-14',
            'break_minutes' => 45,
            'duration_minutes' => 465,
            'notes' => 'Training and ticket review.',
        ]);
    }

    public function test_updating_a_session_recalculates_duration(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::factory()->for($user)->create([
            'date' => '2026-03-14',
            'start_time' => '2026-03-14 09:00:00',
            'end_time' => '2026-03-14 17:00:00',
            'break_minutes' => 60,
            'duration_minutes' => 420,
        ]);

        $this->actingAs($user)
            ->patch(route('work-sessions.update', $session), [
                'date' => '2026-03-14',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_minutes' => 30,
                'notes' => 'Updated after overtime.',
            ])
            ->assertRedirect(route('hr-counter'));

        $session->refresh();

        $this->assertSame(510, $session->duration_minutes);
        $this->assertSame(30, $session->break_minutes);
        $this->assertSame('Updated after overtime.', $session->notes);
    }

    public function test_deleting_a_session_removes_it(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::factory()->for($user)->create([
            'duration_minutes' => 300,
        ]);

        $this->actingAs($user)
            ->delete(route('work-sessions.destroy', $session))
            ->assertRedirect(route('hr-counter'));

        $this->assertDatabaseMissing('work_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_deleting_work_sessions_by_date_removes_all_sessions_for_that_day(): void
    {
        $user = User::factory()->create();

        WorkSession::factory()->for($user)->count(2)->create([
            'date' => '2026-03-14',
            'start_time' => '2026-03-14 09:00:00',
            'end_time' => '2026-03-14 12:00:00',
            'duration_minutes' => 180,
        ]);

        WorkSession::factory()->for($user)->create([
            'date' => '2026-03-15',
            'start_time' => '2026-03-15 09:00:00',
            'end_time' => '2026-03-15 12:00:00',
            'duration_minutes' => 180,
        ]);

        $this->actingAs($user)
            ->delete(route('work-sessions.destroy-by-date'), [
                'date' => '2026-03-14',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertSame(1, $user->fresh()->workSessions()->count());
        $this->assertDatabaseMissing('work_sessions', [
            'user_id' => $user->id,
            'date' => '2026-03-14',
        ]);
        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $user->id,
            'date' => '2026-03-15',
        ]);
    }

    public function test_bulk_add_creates_sessions_from_internship_schedule(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'start_date' => '2026-01-06',
            'required_hours' => 486,
            'regular_workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'default_start_time' => '09:00:00',
            'default_end_time' => '18:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('work-sessions.bulk-store'), [
                'start_date' => '2026-01-06',
                'end_date' => '2026-01-09',
                'break_minutes' => 60,
                'notes' => 'Bulk added from internship schedule.',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertSame(4, $user->workSessions()->count());
        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $user->id,
            'date' => '2026-01-06',
            'duration_minutes' => 480,
            'notes' => 'Bulk added from internship schedule.',
        ]);
    }

    public function test_bulk_add_skips_dates_that_already_have_sessions(): void
    {
        $user = User::factory()->create();

        InternshipSetting::factory()->for($user)->create([
            'regular_workdays' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'default_start_time' => '09:00:00',
            'default_end_time' => '18:00:00',
        ]);

        WorkSession::factory()->for($user)->create([
            'date' => '2026-01-06',
            'start_time' => '2026-01-06 09:00:00',
            'end_time' => '2026-01-06 18:00:00',
            'break_minutes' => 60,
            'duration_minutes' => 480,
        ]);

        $this->actingAs($user)
            ->post(route('work-sessions.bulk-store'), [
                'start_date' => '2026-01-06',
                'end_date' => '2026-01-07',
                'break_minutes' => 60,
                'notes' => 'Bulk added from internship schedule.',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertSame(2, $user->workSessions()->count());
    }
}
