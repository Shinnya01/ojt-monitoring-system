<?php

namespace Tests\Feature;

use App\Models\DailyNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyNoteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_counter_includes_saved_daily_notes(): void
    {
        $user = User::factory()->create();

        DailyNote::query()->create([
            'user_id' => $user->id,
            'date' => '2026-03-14',
            'note' => 'Worked on onboarding pages and intern checklist.',
        ]);

        $this->actingAs($user)
            ->get(route('hr-counter'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HrCounter')
                ->has('dailyNotes', 1)
                ->where('dailyNotes.0.date', '2026-03-14')
                ->where('dailyNotes.0.note', 'Worked on onboarding pages and intern checklist.'),
            );
    }

    public function test_daily_note_can_be_saved_for_any_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('daily-notes.store'), [
                'date' => '2026-03-10',
                'note' => 'Observed deployment flow and documented blockers.',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertDatabaseHas('daily_notes', [
            'user_id' => $user->id,
            'date' => '2026-03-10',
            'note' => 'Observed deployment flow and documented blockers.',
        ]);
    }

    public function test_blank_daily_note_clears_existing_note(): void
    {
        $user = User::factory()->create();

        DailyNote::query()->create([
            'user_id' => $user->id,
            'date' => '2026-03-10',
            'note' => 'Old note.',
        ]);

        $this->actingAs($user)
            ->post(route('daily-notes.store'), [
                'date' => '2026-03-10',
                'note' => '   ',
            ])
            ->assertRedirect(route('hr-counter'));

        $this->assertDatabaseMissing('daily_notes', [
            'user_id' => $user->id,
            'date' => '2026-03-10',
        ]);
    }
}
