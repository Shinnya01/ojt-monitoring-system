<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->validated());

        return to_route('tasks.index');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->ensureOwnership($request, $task);

        $task->update($request->validated());

        return to_route('tasks.index');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnership($request, $task);

        $task->delete();

        return to_route('tasks.index');
    }

    public function toggle(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnership($request, $task);

        $task->update([
            'is_done' => ! $task->is_done,
        ]);

        return to_route('tasks.index');
    }

    protected function ensureOwnership(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }
}
