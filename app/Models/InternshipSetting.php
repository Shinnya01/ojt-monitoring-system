<?php

namespace App\Models;

use Database\Factories\InternshipSettingFactory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class InternshipSetting extends Model
{
    /** @use HasFactory<InternshipSettingFactory> */
    use HasFactory;

    public const WORKDAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'start_date',
        'expected_end_date',
        'required_hours',
        'regular_workdays',
        'default_start_time',
        'default_end_time',
        'setup_completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'regular_workdays' => 'array',
            'required_hours' => 'integer',
            'setup_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSetupComplete(): bool
    {
        return $this->setup_completed_at !== null;
    }

    public function expectedEndDate(): ?CarbonInterface
    {
        if ($this->start_date === null || $this->required_hours === null || empty($this->regular_workdays)) {
            return null;
        }

        $dailyMinutes = $this->projectedDailyMinutes();

        if ($dailyMinutes <= 0) {
            return null;
        }

        $requiredMinutes = $this->required_hours * 60;
        $completedMinutes = $this->completedMinutes();
        $remainingMinutes = max($requiredMinutes - $completedMinutes, 0);

        if ($remainingMinutes === 0) {
            $lastAttendanceDate = $this->attendanceByDate()->keys()->last();

            return $lastAttendanceDate !== null
                ? Carbon::parse($lastAttendanceDate)
                : $this->start_date;
        }

        $accumulatedMinutes = 0;
        $date = Carbon::parse(max($this->start_date->toDateString(), today()->toDateString()))->startOfDay();

        // Safety guard for malformed schedules while still allowing multi-year internships.
        for ($index = 0; $index < 3660; $index += 1) {
            if (in_array(strtolower($date->englishDayOfWeek), $this->regular_workdays ?? [], true)) {
                $accumulatedMinutes += $dailyMinutes;

                if ($accumulatedMinutes >= $remainingMinutes) {
                    return $date;
                }
            }

            $date = $date->addDay();
        }

        return null;
    }

    protected function projectedDailyMinutes(): int
    {
        $attendanceAverage = $this->attendanceByDate()
            ->avg(fn (int $minutes): int => $minutes);

        if ($attendanceAverage !== null && $attendanceAverage > 0) {
            return (int) round($attendanceAverage);
        }

        return $this->dailyScheduledMinutes();
    }

    protected function completedMinutes(): int
    {
        return (int) ($this->user?->workSessions()
            ->whereNotNull('end_time')
            ->sum('duration_minutes') ?? 0);
    }

    /**
     * @return Collection<string, int>
     */
    protected function attendanceByDate(): Collection
    {
        if ($this->user === null) {
            return collect();
        }

        return $this->user->workSessions()
            ->whereNotNull('end_time')
            ->toBase()
            ->selectRaw('date, coalesce(sum(duration_minutes), 0) as total_minutes')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $row->date => (int) $row->total_minutes,
            ]);
    }

    protected function dailyScheduledMinutes(): int
    {
        if ($this->default_start_time === null || $this->default_end_time === null) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i:s', (string) $this->default_start_time);
        $end = Carbon::createFromFormat('H:i:s', (string) $this->default_end_time);

        return max($start->diffInMinutes($end, false), 0);
    }
}
