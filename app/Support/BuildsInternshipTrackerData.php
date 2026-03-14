<?php

namespace App\Support;

use App\Models\InternshipSetting;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;

class BuildsInternshipTrackerData
{
    /**
     * @return array<string, mixed>
     */
    public static function dashboard(User $user): array
    {
        $setting = $user->internshipSetting;
        $activeSession = self::activeSession($user);
        $summary = self::summary($user, $setting, $activeSession);

        return [
            'summary' => $summary,
            'activeSession' => $activeSession ? self::transformSession($activeSession) : null,
            'taskSummary' => self::taskSummary($user),
            'internshipSettings' => self::transformSettings($setting),
            'showSetupDialog' => ! $setting?->isSetupComplete(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function hrCounter(User $user): array
    {
        $setting = $user->internshipSetting;
        $activeSession = self::activeSession($user);
        $summary = self::summary($user, $setting, $activeSession);
        $recentSessions = $user->workSessions()
            ->latest('start_time')
            ->limit(12)
            ->get();

        return [
            'summary' => $summary,
            'activeSession' => $activeSession ? self::transformSession($activeSession) : null,
            'recentSessions' => $recentSessions->map(fn (WorkSession $session) => self::transformSession($session)),
            'calendarDays' => self::calendarDays($user, $activeSession),
            'dailyNotes' => $user->dailyNotes()
                ->orderBy('date')
                ->get()
                ->map(fn ($note) => [
                    'date' => $note->date->toDateString(),
                    'note' => $note->note,
                ]),
            'internshipSettings' => self::transformSettings($setting),
            'manualEntryDefaults' => [
                'date' => now()->toDateString(),
                'startTime' => self::defaultTime($setting?->default_start_time, '09:00'),
                'endTime' => self::defaultTime($setting?->default_end_time, '18:00'),
                'breakMinutes' => 60,
            ],
            'showSetupDialog' => ! $setting?->isSetupComplete(),
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    protected static function calendarDays(User $user, ?WorkSession $activeSession): array
    {
        $days = $user->workSessions()
            ->toBase()
            ->selectRaw('date, count(*) as session_count, coalesce(sum(duration_minutes), 0) as total_minutes')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function (object $row): array {
                return [
                    'date' => (string) $row->date,
                    'sessionCount' => (int) $row->session_count,
                    'totalMinutes' => (int) $row->total_minutes,
                ];
            })
            ->keyBy('date');

        if ($activeSession !== null) {
            $activeDate = $activeSession->date->toDateString();
            $activeMinutes = CalculatesWorkSessionDuration::forPeriod($activeSession->start_time, now(), $activeSession->break_minutes);
            $existing = $days->get($activeDate, [
                'date' => $activeDate,
                'sessionCount' => 1,
                'totalMinutes' => 0,
            ]);

            $existing['totalMinutes'] = (int) $existing['totalMinutes'] + $activeMinutes;

            $days->put($activeDate, $existing);
        }

        return $days
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function tasks(User $user): array
    {
        $tasks = $user->tasks()
            ->orderBy('is_done')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->latest('created_at')
            ->get();

        return [
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'notes' => $task->notes,
                'dueDate' => $task->due_date?->toDateString(),
                'priority' => $task->priority,
                'isDone' => $task->is_done,
            ]),
            'counts' => [
                'pending' => $tasks->where('is_done', false)->count(),
                'completed' => $tasks->where('is_done', true)->count(),
                'total' => $tasks->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function summary(User $user, ?InternshipSetting $setting, ?WorkSession $activeSession): array
    {
        $completedMinutes = (int) $user->workSessions()->sum('duration_minutes');
        $todayMinutes = (int) $user->workSessions()
            ->whereDate('date', now()->toDateString())
            ->sum('duration_minutes');
        $activeMinutes = $activeSession
            ? CalculatesWorkSessionDuration::forPeriod($activeSession->start_time, now(), $activeSession->break_minutes)
            : 0;
        $requiredMinutes = max(((int) ($setting?->required_hours ?? 0)) * 60, 0);
        $liveCompletedMinutes = $completedMinutes + $activeMinutes;

        return [
            'completedMinutes' => $completedMinutes,
            'liveCompletedMinutes' => $liveCompletedMinutes,
            'todayMinutes' => $todayMinutes,
            'liveTodayMinutes' => $todayMinutes + $activeMinutes,
            'remainingMinutes' => max($requiredMinutes - $liveCompletedMinutes, 0),
            'completionPercentage' => $requiredMinutes > 0
                ? min((int) floor(($liveCompletedMinutes / $requiredMinutes) * 100), 100)
                : 0,
            'completedSessions' => $user->workSessions()->whereNotNull('end_time')->count(),
            'hasInternshipSettings' => $setting?->isSetupComplete() ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function taskSummary(User $user): array
    {
        $tasks = $user->tasks()->get();

        return [
            'pending' => $tasks->where('is_done', false)->count(),
            'completed' => $tasks->where('is_done', true)->count(),
            'total' => $tasks->count(),
        ];
    }

    protected static function activeSession(User $user): ?WorkSession
    {
        return $user->workSessions()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected static function transformSession(WorkSession $session): array
    {
        return [
            'id' => $session->id,
            'date' => $session->date->toDateString(),
            'startTime' => $session->start_time->toIso8601String(),
            'endTime' => $session->end_time?->toIso8601String(),
            'breakMinutes' => $session->break_minutes,
            'durationMinutes' => $session->end_time
                ? $session->duration_minutes
                : CalculatesWorkSessionDuration::forPeriod($session->start_time, now(), $session->break_minutes),
            'notes' => $session->notes,
            'isRunning' => $session->isRunning(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function transformSettings(?InternshipSetting $setting): ?array
    {
        if ($setting === null) {
            return null;
        }

        return [
            'startDate' => $setting->start_date?->toDateString(),
            'expectedEndDate' => $setting->expectedEndDate()?->toDateString(),
            'requiredHours' => $setting->required_hours,
            'regularWorkdays' => $setting->regular_workdays ?? [],
            'defaultStartTime' => self::defaultTime($setting->default_start_time, null),
            'defaultEndTime' => self::defaultTime($setting->default_end_time, null),
            'isSetupComplete' => $setting->isSetupComplete(),
        ];
    }

    protected static function defaultTime(null|string $value, ?string $fallback): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        return substr($value, 0, 5);
    }
}
