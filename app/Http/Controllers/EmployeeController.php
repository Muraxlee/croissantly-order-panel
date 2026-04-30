<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSlot;
use App\Support\StaffPeriod;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isEmployee(), 403);

        [$periodStart, $periodEnd] = StaffPeriod::resolve($request->query('start'));
        $slots = EmployeeSlot::where('employee_id', auth()->id())
            ->whereBetween('slot_date', [$periodStart, $periodEnd])
            ->orderBy('slot_date')
            ->get();

        $scheduleDays = StaffPeriod::days($periodStart)->map(fn ($day) => [
            'date' => $day,
            'slot' => $slots->first(fn ($slot) => $slot->slot_date->toDateString() === $day->toDateString()),
        ]);

        $todaySlot = EmployeeSlot::where('employee_id', auth()->id())
            ->whereDate('slot_date', today())
            ->first();

        $nextSlot = EmployeeSlot::where('employee_id', auth()->id())
            ->whereDate('slot_date', '>=', today())
            ->where('is_off', false)
            ->whereNotNull('starts_at')
            ->orderBy('slot_date')
            ->first();

        return view('dashboards.employee', [
            'slots' => $slots,
            'upcomingSlots' => $slots,
            'workSlots' => $slots->sortByDesc('slot_date'),
            'scheduleDays' => $scheduleDays,
            'todaySlot' => $todaySlot,
            'nextSlot' => $nextSlot,
            'actualHours' => $slots->sum(fn ($slot) => $slot->actualHours()),
            'pendingActualCount' => $slots->filter(fn ($slot) => ! $slot->is_off && ! $slot->hasActualTime())->count(),
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            ...StaffPeriod::navigation($periodStart, auth()->id()),
        ]);
    }
}
