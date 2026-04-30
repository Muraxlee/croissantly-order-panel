<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmployeeSlot extends Model
{
    protected $fillable = [
        'employee_id',
        'slot_date',
        'starts_at',
        'ends_at',
        'actual_starts_at',
        'actual_ends_at',
        'actual_break_minutes',
        'completed_at',
        'is_off',
        'has_break',
        'break_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'completed_at' => 'datetime',
            'is_off' => 'boolean',
            'has_break' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function hours(): float
    {
        return $this->actualHours() ?: $this->scheduledHours();
    }

    public function scheduledHours(): float
    {
        if ($this->is_off || ! $this->starts_at || ! $this->ends_at) {
            return 0;
        }

        $start = Carbon::parse($this->slot_date->toDateString().' '.$this->starts_at);
        $end = Carbon::parse($this->slot_date->toDateString().' '.$this->ends_at);
        $minutes = max(0, $start->diffInMinutes($end) - (int) $this->break_minutes);

        return round($minutes / 60, 2);
    }

    public function actualHours(): float
    {
        if ($this->is_off || ! $this->actual_starts_at || ! $this->actual_ends_at) {
            return 0;
        }

        $start = Carbon::parse($this->slot_date->toDateString().' '.$this->actual_starts_at);
        $end = Carbon::parse($this->slot_date->toDateString().' '.$this->actual_ends_at);
        $minutes = max(0, $start->diffInMinutes($end) - (int) $this->actual_break_minutes);

        return round($minutes / 60, 2);
    }

    public function hasActualTime(): bool
    {
        return (bool) ($this->actual_starts_at && $this->actual_ends_at);
    }
}
