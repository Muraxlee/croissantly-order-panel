<?php

namespace App\Support;

use App\Models\EmployeeSlot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StaffPeriod
{
    public const DAYS = 7;

    public static function currentStart(): Carbon
    {
        return self::startFor(today());
    }

    public static function startFor($date): Carbon
    {
        return Carbon::parse($date)->startOfDay()->startOfWeek(Carbon::MONDAY);
    }

    public static function endFor(Carbon $periodStart): Carbon
    {
        return $periodStart->copy()->addDays(self::DAYS - 1)->endOfDay();
    }

    public static function days(Carbon $periodStart): Collection
    {
        return collect(iterator_to_array(CarbonPeriod::create($periodStart, self::endFor($periodStart))));
    }

    public static function resolve(?string $date = null): array
    {
        $periodStart = $date ? self::startFor($date) : self::currentStart();

        return [$periodStart, self::endFor($periodStart)];
    }

    public static function nextUncreatedStart(?int $employeeId = null): Carbon
    {
        $periodStart = self::currentStart();

        while (self::hasSlots($periodStart, $employeeId)) {
            $periodStart->addDays(self::DAYS);
        }

        return $periodStart;
    }

    public static function navigation(Carbon $selectedStart, ?int $employeeId = null): array
    {
        $periodStarts = self::createdStarts($employeeId)
            ->push(self::currentStart()->toDateString())
            ->unique()
            ->sort()
            ->values();

        $selected = $selectedStart->toDateString();
        $previousDate = $periodStarts->filter(fn ($date) => $date < $selected)->last();
        $nextDate = $periodStarts->first(fn ($date) => $date > $selected);

        return [
            'previousPeriodStart' => $previousDate ? Carbon::parse($previousDate) : null,
            'nextPeriodStart' => $nextDate ? Carbon::parse($nextDate) : null,
        ];
    }

    private static function hasSlots(Carbon $periodStart, ?int $employeeId = null): bool
    {
        return self::slotQuery($employeeId)
            ->whereBetween('slot_date', [$periodStart->toDateString(), self::endFor($periodStart)->toDateString()])
            ->exists();
    }

    private static function createdStarts(?int $employeeId = null): Collection
    {
        return self::slotQuery($employeeId)
            ->select('slot_date')
            ->distinct()
            ->pluck('slot_date')
            ->map(fn ($date) => self::startFor($date)->toDateString());
    }

    private static function slotQuery(?int $employeeId = null): Builder
    {
        return EmployeeSlot::query()
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId));
    }

}
