<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkStoreWorkSessionsRequest;
use App\Http\Requests\ClockOutWorkSessionRequest;
use App\Http\Requests\StoreWorkSessionRequest;
use App\Http\Requests\UpdateWorkSessionRequest;
use App\Models\InternshipSetting;
use App\Models\WorkSession;
use App\Support\CalculatesWorkSessionDuration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WorkSessionController extends Controller
{
    public function store(StoreWorkSessionRequest $request): RedirectResponse
    {
        [$startTime, $endTime] = $this->makeDateTimes(
            $request->string('date')->toString(),
            $request->string('start_time')->toString(),
            $request->string('end_time')->toString(),
        );

        $breakMinutes = (int) $request->integer('break_minutes');

        $request->user()->workSessions()->create([
            'date' => $request->string('date')->toString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
            'duration_minutes' => CalculatesWorkSessionDuration::forPeriod($startTime, $endTime, $breakMinutes),
            'notes' => $request->input('notes'),
        ]);

        return to_route('hr-counter');
    }

    public function bulkStore(BulkStoreWorkSessionsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $setting = $user->internshipSetting;

        if (! $setting?->isSetupComplete()) {
            return to_route('hr-counter')->withErrors([
                'bulk' => 'Complete your internship setup before using bulk add.',
            ]);
        }

        if ($setting->default_start_time === null || $setting->default_end_time === null || empty($setting->regular_workdays)) {
            return to_route('hr-counter')->withErrors([
                'bulk' => 'Your internship setup needs workdays and default time range before bulk add can run.',
            ]);
        }

        $startDate = Carbon::parse($request->string('start_date')->toString())->startOfDay();
        $endDate = Carbon::parse($request->string('end_date')->toString())->startOfDay();
        $breakMinutes = (int) $request->integer('break_minutes');
        $notes = $request->filled('notes')
            ? $request->string('notes')->toString()
            : 'Bulk added from internship schedule.';

        $existingDates = $user->workSessions()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();

        $existingDateLookup = array_fill_keys($existingDates, true);
        $created = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $weekdayKey = strtolower($date->englishDayOfWeek);

            if (! in_array($weekdayKey, $setting->regular_workdays ?? [], true)) {
                continue;
            }

            if (isset($existingDateLookup[$date->toDateString()])) {
                continue;
            }

            [$startTime, $endTime] = $this->makeDateTimes(
                $date->toDateString(),
                substr((string) $setting->default_start_time, 0, 5),
                substr((string) $setting->default_end_time, 0, 5),
            );

            $user->workSessions()->create([
                'date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'break_minutes' => $breakMinutes,
                'duration_minutes' => CalculatesWorkSessionDuration::forPeriod($startTime, $endTime, $breakMinutes),
                'notes' => $notes,
            ]);

            $created++;
        }

        return to_route('hr-counter')->with('success', $created > 0
            ? "Bulk add created {$created} work sessions."
            : 'Bulk add found no new dates to create.');
    }

    public function update(UpdateWorkSessionRequest $request, WorkSession $workSession): RedirectResponse
    {
        $this->ensureOwnership($request, $workSession);

        [$startTime, $endTime] = $this->makeDateTimes(
            $request->string('date')->toString(),
            $request->string('start_time')->toString(),
            $request->string('end_time')->toString(),
        );

        $breakMinutes = (int) $request->integer('break_minutes');

        $workSession->update([
            'date' => $request->string('date')->toString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
            'duration_minutes' => CalculatesWorkSessionDuration::forPeriod($startTime, $endTime, $breakMinutes),
            'notes' => $request->input('notes'),
        ]);

        return to_route('hr-counter');
    }

    public function destroy(Request $request, WorkSession $workSession): RedirectResponse
    {
        $this->ensureOwnership($request, $workSession);

        $workSession->delete();

        return to_route('hr-counter');
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->workSessions()->whereNull('end_time')->exists()) {
            return to_route('hr-counter')->withErrors([
                'clock' => 'You already have an active session.',
            ]);
        }

        $now = now();

        $user->workSessions()->create([
            'date' => $now->toDateString(),
            'start_time' => $now,
            'break_minutes' => 0,
        ]);

        return to_route('hr-counter');
    }

    public function clockOut(ClockOutWorkSessionRequest $request): RedirectResponse
    {
        $activeSession = $request->user()->workSessions()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($activeSession === null) {
            return to_route('hr-counter')->withErrors([
                'clock' => 'There is no active session to clock out.',
            ]);
        }

        $breakMinutes = (int) $request->integer('break_minutes');
        $endTime = now();

        $activeSession->update([
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
            'duration_minutes' => CalculatesWorkSessionDuration::forPeriod($activeSession->start_time, $endTime, $breakMinutes),
            'notes' => $request->filled('notes') ? $request->string('notes')->toString() : $activeSession->notes,
        ]);

        return to_route('hr-counter');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function makeDateTimes(string $date, string $startTime, string $endTime): array
    {
        return [
            Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}"),
            Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}"),
        ];
    }

    protected function ensureOwnership(Request $request, WorkSession $workSession): void
    {
        abort_unless($workSession->user_id === $request->user()->id, 404);
    }
}
