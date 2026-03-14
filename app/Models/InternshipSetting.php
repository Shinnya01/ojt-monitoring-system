<?php

namespace App\Models;

use Database\Factories\InternshipSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
