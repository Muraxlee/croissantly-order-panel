@extends('layouts.app')

@section('content')
    @php
        $totalHours = $slots->sum(fn ($slot) => $slot->actualHours());
        $totalCost = $slots->sum(fn ($slot) => $slot->actualHours() * (float) ($slot->employee->employeeProfile?->hourly_rate ?? 0));
        $selectedEmployee = $employees->firstWhere('id', (int) $selectedEmployeeId);
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Admin timesheets</p>
            <h1>{{ $selectedEmployee ? $selectedEmployee->name.' work cost' : 'Actual work cost' }}</h1>
        </div>
    </header>

    <section class="metric-grid">
        <div><strong>{{ $slots->where(fn ($slot) => $slot->hasActualTime())->count() }}</strong><span>Updated shifts</span></div>
        <div><strong>{{ number_format($totalHours, 2) }}</strong><span>Total hours</span></div>
        <div><strong>&pound;{{ number_format($totalCost, 2) }}</strong><span>Total staff cost</span></div>
    </section>

    <section class="panel">
        <div class="section-head timesheet-section-head">
            <div>
                <h2>{{ $selectedEmployee ? $selectedEmployee->name.' actual time' : 'All staff actual time' }}</h2>
                <p>{{ $periodStart->format('D d M') }} - {{ $periodEnd->format('D d M') }}. Only actual updated shifts count toward hours and cost.</p>
            </div>
            <form method="get" action="{{ route('admin.timesheets.index') }}" class="timesheet-filter timesheet-range-filter">
                <label><span>Start date</span><input type="date" name="start_date" value="{{ $periodStart->toDateString() }}"></label>
                <label><span>End date</span><input type="date" name="end_date" value="{{ $periodEnd->toDateString() }}"></label>
                <label><span>Employee</span>
                    <select name="employee_id">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $selectedEmployeeId === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label><span>Search</span><input name="q" value="{{ $search }}" placeholder="Name, login, phone"></label>
                <button class="secondary-button">View</button>
                @if($search !== '')
                    <a class="secondary-button" href="{{ route('admin.timesheets.index', array_filter([
                        'start_date' => $periodStart->toDateString(),
                        'end_date' => $periodEnd->toDateString(),
                        'employee_id' => $selectedEmployeeId,
                    ])) }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Actual time</th>
                        <th>Break</th>
                        <th>Hours</th>
                        <th>Hourly cost</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                        @php
                            $rate = (float) ($slot->employee->employeeProfile?->hourly_rate ?? 0);
                            $hours = $slot->actualHours();
                        @endphp
                        <tr>
                            <td>{{ $slot->slot_date->format('d M Y') }}</td>
                            <td><strong>{{ $slot->employee->name }}</strong><small>{{ $slot->employee->username }}</small></td>
                            <td>
                                @if($slot->hasActualTime())
                                    {{ substr($slot->actual_starts_at, 0, 5) }}-{{ substr($slot->actual_ends_at, 0, 5) }}
                                @else
                                    Pending
                                @endif
                            </td>
                            <td>{{ $slot->hasActualTime() ? ($slot->actual_break_minutes ? '30m' : 'No') : '-' }}</td>
                            <td>{{ $slot->hasActualTime() ? number_format($hours, 2) : '-' }}</td>
                            <td>&pound;{{ number_format($rate, 2) }}</td>
                            <td>&pound;{{ number_format($hours * $rate, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No shifts found{{ $search !== '' ? ' for "'.$search.'"' : '' }} in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
