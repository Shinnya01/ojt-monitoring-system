<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyNoteRequest;
use App\Models\DailyNote;
use Illuminate\Http\RedirectResponse;

class DailyNoteController extends Controller
{
    public function store(StoreDailyNoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $note = trim((string) ($validated['note'] ?? ''));

        if ($note === '') {
            DailyNote::query()->where('user_id', $request->user()->id)
                ->whereDate('date', $validated['date'])
                ->delete();

            return to_route('hr-counter')->with('success', 'Daily note cleared.');
        }

        DailyNote::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'date' => $validated['date'],
            ],
            [
                'note' => $note,
            ],
        );

        return to_route('hr-counter')->with('success', 'Daily note saved.');
    }
}
