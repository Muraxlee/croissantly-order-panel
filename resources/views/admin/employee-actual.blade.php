@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Actual work time</p>
            <h1>{{ $employee->name }}</h1>
        </div>
        <a class="secondary-button" href="{{ route('admin.slots.index', ['start' => $periodStart->toDateString()]) }}">Back to calendar</a>
    </header>

    <section class="panel page-section">
        <div class="section-head"><div><h2>Employee cost</h2><p>This rate is used in the timesheet cost report.</p></div></div>
        <form method="post" action="{{ route('admin.employees.profile', $employee) }}" class="form-grid compact">
            @csrf
            @method('patch')
            <label><span>Phone</span><input name="phone" value="{{ $employee->employeeProfile?->phone }}"></label>
            <label><span>Hourly cost</span><input type="number" step="0.01" name="hourly_rate" value="{{ $employee->employeeProfile?->hourly_rate ?? 0 }}" required></label>
            <button class="primary-button">Save cost</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Update actual time</h2>
                <p>{{ $periodStart->format('D d M') }} - {{ $periodEnd->format('D d M') }}. Break is always 30 minutes when ticked.</p>
            </div>
            <div class="period-nav">
                @if($previousPeriodStart)
                    <a class="secondary-button" href="{{ route('admin.employees.actual-time', ['employee' => $employee, 'start' => $previousPeriodStart->toDateString()]) }}">&larr; Previous</a>
                @else
                    <span class="secondary-button disabled-button">&larr; Previous</span>
                @endif
                @if($nextPeriodStart)
                    <a class="secondary-button" href="{{ route('admin.employees.actual-time', ['employee' => $employee, 'start' => $nextPeriodStart->toDateString()]) }}">Next &rarr;</a>
                @else
                    <span class="secondary-button disabled-button">Next &rarr;</span>
                @endif
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Planned</th>
                        <th>Actual from</th>
                        <th>Actual to</th>
                        <th>30m break</th>
                        <th>Hours</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td><strong>{{ $slot->slot_date->format('D d M') }}</strong></td>
                            <td>
                                @if($slot->is_off)
                                    OFF
                                @else
                                    {{ substr($slot->starts_at, 0, 5) }}-{{ substr($slot->ends_at, 0, 5) }}
                                    <small>{{ $slot->has_break ? '30m planned break' : 'No planned break' }}</small>
                                @endif
                            </td>
                            @if($slot->is_off)
                                <td colspan="5"><span class="pill">OFF</span></td>
                            @else
                                <td><input form="actual-slot-{{ $slot->id }}" type="time" name="actual_starts_at" value="{{ $slot->actual_starts_at ? substr($slot->actual_starts_at, 0, 5) : ($slot->starts_at ? substr($slot->starts_at, 0, 5) : '') }}" required></td>
                                <td><input form="actual-slot-{{ $slot->id }}" type="time" name="actual_ends_at" value="{{ $slot->actual_ends_at ? substr($slot->actual_ends_at, 0, 5) : ($slot->ends_at ? substr($slot->ends_at, 0, 5) : '') }}" required></td>
                                <td><label class="check-row"><input form="actual-slot-{{ $slot->id }}" type="checkbox" name="has_actual_break" value="1" @checked(($slot->actual_break_minutes ?? $slot->break_minutes) >= 30)><span>Break</span></label></td>
                                <td>{{ $slot->hasActualTime() ? $slot->actualHours().'h' : 'Pending' }}</td>
                                <td><button form="actual-slot-{{ $slot->id }}" class="small-button">Save</button></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No planned slots for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach($slots as $slot)
            @unless($slot->is_off)
                <form id="actual-slot-{{ $slot->id }}" method="post" action="{{ route('admin.slots.actual-time', $slot) }}">
                    @csrf
                    @method('patch')
                </form>
            @endunless
        @endforeach
    </section>
@endsection
