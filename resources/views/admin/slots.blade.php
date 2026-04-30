@extends('layouts.app')

@section('content')
    <header class="page-header staff-calendar-header">
        <div>
            <p class="eyebrow">Admin staff</p>
            <h1>Staff calendar</h1>
            <p class="muted">Plan one employee shift per date. Planned shifts appear on staff dashboards immediately.</p>
        </div>
        <button class="primary-button staff-header-action" type="button" data-open-modal="staff-slot-modal" data-slot-date="{{ $selectedDate->toDateString() }}">+ Schedule</button>
    </header>

    <section class="staff-calendar-layout">
        <div class="panel staff-month-panel">
            <div class="staff-month-toolbar">
                <a class="icon-button" href="{{ route('admin.slots.index', ['month' => $previousMonth->format('Y-m'), 'date' => $previousMonth->copy()->startOfMonth()->toDateString()]) }}" aria-label="Previous month">&larr;</a>
                <div>
                    <h2>{{ $monthStart->format('F Y') }}</h2>
                    <p>{{ $monthStart->format('d M') }} - {{ $monthEnd->format('d M Y') }}</p>
                </div>
                <a class="icon-button" href="{{ route('admin.slots.index', ['month' => $nextMonth->format('Y-m'), 'date' => $nextMonth->copy()->startOfMonth()->toDateString()]) }}" aria-label="Next month">&rarr;</a>
            </div>

            <div class="staff-month-weekdays" aria-hidden="true">
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span>Fri</span>
                <span>Sat</span>
                <span>Sun</span>
            </div>

            <div class="staff-month-grid">
                @foreach($calendarWeeks as $week)
                    @foreach($week as $day)
                        @php($daySlots = $slotsByDate->get($day->toDateString(), collect()))
                        <article @class([
                            'staff-month-day',
                            'outside-month' => ! $day->isSameMonth($monthStart),
                            'selected' => $day->isSameDay($selectedDate),
                            'today' => $day->isToday(),
                        ])>
                            <a class="staff-month-day-link" href="{{ route('admin.slots.index', ['month' => $monthStart->format('Y-m'), 'date' => $day->toDateString()]) }}" aria-label="View {{ $day->format('D d M') }}">
                                <span>{{ $day->format('j') }}</span>
                                @if($day->isToday())
                                    <small>Today</small>
                                @endif
                            </a>

                            <div class="shift-chip-list">
                                @foreach($daySlots->take(4) as $slot)
                                    <span @class([
                                        'shift-chip',
                                        'off' => $slot->is_off,
                                        'complete' => $slot->hasActualTime(),
                                        'pending' => ! $slot->is_off && ! $slot->hasActualTime(),
                                    ])>
                                        {{ $slot->employee?->name }}
                                        @if($slot->is_off)
                                            OFF
                                        @else
                                            {{ substr($slot->starts_at, 0, 5) }}-{{ substr($slot->ends_at, 0, 5) }}
                                        @endif
                                    </span>
                                @endforeach
                                @if($daySlots->count() > 4)
                                    <span class="shift-chip muted-chip">+{{ $daySlots->count() - 4 }} more</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @endforeach
            </div>
        </div>

        <aside class="panel selected-day-panel">
            <div class="selected-day-head">
                <div>
                    <h2>{{ $selectedDate->format('D d M') }}</h2>
                    <p>{{ $selectedDateSlots->count() }} planned {{ Str::plural('shift', $selectedDateSlots->count()) }}</p>
                </div>
                <button class="secondary-button" type="button" data-open-modal="staff-slot-modal" data-slot-date="{{ $selectedDate->toDateString() }}">+ Schedule</button>
            </div>

            <div class="selected-day-summary">
                <div>
                    <strong>{{ $selectedDateSlots->where('is_off', false)->count() }}</strong>
                    <span>Working</span>
                </div>
                <div>
                    <strong>{{ $selectedDateSlots->filter(fn ($slot) => ! $slot->is_off && ! $slot->hasActualTime())->count() }}</strong>
                    <span>Pending actual</span>
                </div>
                <div>
                    <strong>{{ $selectedDateSlots->filter(fn ($slot) => ! $slot->is_off && $slot->hasActualTime())->count() }}</strong>
                    <span>Updated</span>
                </div>
            </div>

            <div class="selected-day-subhead">
                <strong>Update actual work time</strong>
                <small>Enter final start, end, and break after the shift is completed.</small>
            </div>

            <div class="selected-shift-list">
                @forelse($selectedDateSlots as $slot)
                    <article @class(['selected-shift-card', 'off' => $slot->is_off])>
                        <div class="selected-shift-top">
                            <div>
                                <strong>{{ $slot->employee?->name }}</strong>
                                <small>{{ $slot->employee?->username }} · Hourly &pound;{{ number_format((float) ($slot->employee?->employeeProfile?->hourly_rate ?? 0), 2) }}</small>
                            </div>
                            @if($slot->is_off)
                                <span class="status-pill off">OFF</span>
                            @elseif($slot->hasActualTime())
                                <span class="status-pill complete">Updated</span>
                            @else
                                <span class="status-pill pending">Pending</span>
                            @endif
                        </div>

                        <dl class="shift-facts">
                            <div>
                                <dt>Planned</dt>
                                <dd>
                                    @if($slot->is_off)
                                        OFF
                                    @else
                                        {{ substr($slot->starts_at, 0, 5) }}-{{ substr($slot->ends_at, 0, 5) }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>Break</dt>
                                <dd>{{ $slot->is_off ? '-' : ($slot->has_break ? '30m planned' : 'No planned break') }}</dd>
                            </div>
                        </dl>

                        @if($slot->notes)
                            <p class="shift-note">{{ $slot->notes }}</p>
                        @endif

                        @if(! $slot->is_off)
                            <form method="post" action="{{ route('admin.slots.actual-time', $slot) }}" class="selected-actual-form">
                                @csrf
                                @method('patch')
                                <label><span>Actual from</span><input type="time" name="actual_starts_at" value="{{ $slot->actual_starts_at ? substr($slot->actual_starts_at, 0, 5) : ($slot->starts_at ? substr($slot->starts_at, 0, 5) : '') }}" required></label>
                                <label><span>Actual to</span><input type="time" name="actual_ends_at" value="{{ $slot->actual_ends_at ? substr($slot->actual_ends_at, 0, 5) : ($slot->ends_at ? substr($slot->ends_at, 0, 5) : '') }}" required></label>
                                <label class="check-row"><input type="checkbox" name="has_actual_break" value="1" @checked(($slot->actual_break_minutes ?? $slot->break_minutes) >= 30)><span>30m break</span></label>
                                <button class="small-button">Save actual</button>
                            </form>
                        @endif

                        <a class="detail-link" href="{{ route('admin.employees.actual-time', ['employee' => $slot->employee, 'start' => $periodStart->toDateString()]) }}">Open employee detail</a>
                    </article>
                @empty
                    <div class="empty selected-day-empty">
                        <strong>No shifts planned for {{ $selectedDate->format('D d M') }}.</strong>
                        <button class="secondary-button" type="button" data-open-modal="staff-slot-modal" data-slot-date="{{ $selectedDate->toDateString() }}">Schedule staff</button>
                    </div>
                @endforelse
            </div>
        </aside>
    </section>

    <dialog class="order-modal staff-slot-modal" id="staff-slot-modal">
        <form method="post" action="{{ route('admin.slots.store') }}" class="modal-shell staff-slot-form" data-staff-slot-form>
            @csrf
            <div class="modal-head">
                <div>
                    <h2>Schedule staff shift</h2>
                    <p>Select one employee and one date. Saving updates that employee's dashboard immediately.</p>
                </div>
                <button class="icon-button" type="button" data-close-modal aria-label="Close modal">&times;</button>
            </div>

            <div class="form-grid">
                <label><span>Date</span><input type="date" name="slot_date" value="{{ old('slot_date', $selectedDate->toDateString()) }}" required></label>
                <label><span>Employee</span>
                    <select name="employee_id" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label><span>From time</span><input type="time" name="starts_at" value="{{ old('starts_at') }}"></label>
                <label><span>To time</span><input type="time" name="ends_at" value="{{ old('ends_at') }}"></label>
                <label class="check-row"><input type="checkbox" name="has_break" value="1" @checked(old('has_break'))><span>30m break</span></label>
                <label class="check-row"><input type="checkbox" name="is_off" value="1" @checked(old('is_off'))><span>OFF</span></label>
                <label class="wide"><span>Notes</span><textarea name="notes" rows="3" placeholder="Optional">{{ old('notes') }}</textarea></label>
            </div>

            <button class="primary-button">Save shift</button>
        </form>
    </dialog>
@endsection
