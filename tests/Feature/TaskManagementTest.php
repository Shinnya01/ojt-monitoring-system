<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_page_renders_tasks_data(): void
    {
        $user = User::factory()->create();

        Task::factory()->count(2)->for($user)->create();

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks')
                ->has('tasks', 2)
                ->where('counts.total', 2),
            );
    }

    public function test_task_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Ask mentor for code review',
                'notes' => 'Follow up before Friday.',
                'due_date' => '2026-03-20',
                'priority' => 'high',
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Ask mentor for code review',
            'priority' => 'high',
            'is_done' => false,
        ]);
    }

    public function test_task_can_be_updated(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->for($user)->create([
            'title' => 'Initial task',
            'priority' => 'low',
        ]);

        $this->actingAs($user)
            ->patch(route('tasks.update', $task), [
                'title' => 'Updated task',
                'notes' => 'Now with details',
                'due_date' => '2026-03-18',
                'priority' => 'medium',
                'is_done' => true,
            ])
            ->assertRedirect(route('tasks.index'));

        $task->refresh();

        $this->assertSame('Updated task', $task->title);
        $this->assertSame('medium', $task->priority);
        $this->assertTrue($task->is_done);
    }

    public function test_task_can_be_toggled(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->for($user)->create([
            'is_done' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('tasks.toggle', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertTrue($task->refresh()->is_done);
    }

    public function test_task_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }
}
