@extends('layouts.app')

@section('content')
    <header class="page-header employee-dashboard-header">
        <div>
            <p class="eyebrow">Staff schedule</p>
            <h1>{{ auth()->user()->name }} timesheet</h1>
            <p class="muted">{{ $periodStart->format('D d M') }} - {{ $periodEnd->format('D d M') }}</p>
        </div>
        <div class="period-nav">
            @if($previousPeriodStart)
                <a class="secondary-button" href="{{ route('employee.dashboard', ['start' => $previousPeriodStart->toDateString()]) }}">&larr; Previous</a>
            @else
                <span class="secondary-button disabled-button">&larr; Previous</span>
            @endif
            @if($nextPeriodStart)
                <a class="secondary-button" href="{{ route('employee.dashboard', ['start' => $nextPeriodStart->toDateString()]) }}">Next &rarr;</a>
            @else
                <span class="secondary-button disabled-button">Next &rarr;</span>
            @endif
        </div>
    </header>

    <section class="employee-shift-hero">
        <div class="employee-next-shift">
            <span>Today</span>
            @if($todaySlot)
                <strong>
                    @if($todaySlot->is_off)
                        OFF
                    @else
                        {{ substr($todaySlot->starts_at, 0, 5) }}-{{ substr($todaySlot->ends_at, 0, 5) }}
                    @endif
                </strong>
                <small>
                    @if($todaySlot->is_off)
                        No planned work today
                    @elseif($todaySlot->hasActualTime())
                        Actual updated: {{ substr($todaySlot->actual_starts_at, 0, 5) }}-{{ substr($todaySlot->actual_ends_at, 0, 5) }}
                    @else
                        {{ $todaySlot->has_break ? $todaySlot->break_minutes.'m planned break' : 'No planned break' }}
                    @endif
                </small>
            @else
                <strong>No shift today</strong>
                <small>
                    @if($nextSlot)
                        Next: {{ $nextSlot->slot_date->format('D d M') }} · {{ substr($nextSlot->starts_at, 0, 5) }}-{{ substr($nextSlot->ends_at, 0, 5) }}
                    @else
                        No upcoming shift has been planned.
                    @endif
                </small>
            @endif
        </div>

        <div class="employee-shift-stats">
            <div>
                <strong>{{ $slots->where('is_off', false)->count() }}</strong>
                <span>Planned shifts</span>
            </div>
            <div>
                <strong>{{ number_format($actualHours, 2) }}</strong>
                <span>Actual hours</span>
            </div>
            <div>
                <strong>{{ $pendingActualCount }}</strong>
                <span>Pending updates</span>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="section-head"><div><h2>Your 7-day schedule</h2><p>Only your own planned slots are visible here.</p></div></div>
        <div class="schedule-grid employee-week-grid">
            @foreach($scheduleDays as $day)
                @php($slot = $day['slot'])
                <article @class(['slot-card', 'off' => $slot?->is_off, 'empty-slot' => ! $slot])>
                    <strong>{{ $day['date']->format('D d M') }}</strong>
                    @if($slot)
                        @if($slot->is_off)
                            <span>OFF</span>
                            <small>No planned work</small>
                        @else
                            <span>{{ substr($slot->starts_at, 0, 5) }}-{{ substr($slot->ends_at, 0, 5) }}</span>
                            <small>{{ $slot->has_break ? $slot->break_minutes.'m planned break' : 'No planned break' }}</small>
                            @if($slot->hasActualTime())
                                <small>Actual {{ substr($slot->actual_starts_at, 0, 5) }}-{{ substr($slot->actual_ends_at, 0, 5) }}</small>
                            @else
                                <small>Actual pending</small>
                            @endif
                        @endif
                    @else
                        <span>No shift</span>
                        <small>Not planned</small>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="panel">
        <div class="section-head"><div><h2>Work history</h2><p>Admin updates actual start, end, and break minutes after work is completed.</p></div></div>

        <div class="employee-history-list employee-history-list-visible">
            @forelse($workSlots as $slot)
                <article class="employee-history-card">
                    <div>
                        <strong>{{ $slot->slot_date->format('D d M') }}</strong>
                        @if($slot->is_off)
                            <span class="status-pill off">OFF</span>
                        @elseif($slot->hasActualTime())
                            <span class="status-pill complete">Updated</span>
                        @else
                            <span class="status-pill pending">Pending</span>
                        @endif
                    </div>
                    <dl>
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
                            <dt>Actual</dt>
                            <dd>
                                @if($slot->hasActualTime())
                                    {{ substr($slot->actual_starts_at, 0, 5) }}-{{ substr($slot->actual_ends_at, 0, 5) }}
                                @else
                                    Pending
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Break</dt>
                            <dd>{{ $slot->hasActualTime() ? ($slot->actual_break_minutes ?? 0).'m' : '-' }}</dd>
                        </div>
                        <div>
                            <dt>Hours</dt>
                            <dd>{{ $slot->hasActualTime() ? $slot->actualHours().'h' : '-' }}</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="empty">No work history yet.</div>
            @endforelse
        </div>
    </section>
@endsection
