@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Kitchen archive</p>
            <h1>Completed orders</h1>
            <p class="muted">Completed orders are grouped by completion date. Use filters to find old dockets quickly.</p>
        </div>
        <a class="secondary-button" href="{{ route('kitchen.dashboard') }}">Back to kitchen</a>
    </header>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Search completed orders</h2>
                <p>{{ $orders->count() }} completed {{ Str::plural('order', $orders->count()) }} found.</p>
            </div>
            <form method="get" action="{{ route('kitchen.orders.completed') }}" class="archive-filter">
                <label><span>From</span><input type="date" name="from" value="{{ $from }}"></label>
                <label><span>To</span><input type="date" name="to" value="{{ $to }}"></label>
                <label><span>Search</span><input name="q" value="{{ $search }}" placeholder="Order, customer, phone, item"></label>
                <button class="secondary-button">Filter</button>
                @if($from || $to || $search !== '')
                    <a class="secondary-button" href="{{ route('kitchen.orders.completed') }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="archive-order-list">
            @forelse($ordersByDate as $date => $dateOrders)
                <section class="archive-day-group">
                    <div class="archive-day-head">
                        <h2>{{ \Carbon\Carbon::parse($date)->format('D d M Y') }}</h2>
                        <span class="pill">{{ $dateOrders->count() }} {{ Str::plural('order', $dateOrders->count()) }}</span>
                    </div>

                    <div class="order-control-list">
                        @foreach($dateOrders as $order)
                            <article class="order-control-row archive-order-row">
                                <div>
                                    <strong>{{ $order->order_number }}</strong>
                                    <small>{{ $order->customerName() }} &middot; {{ $order->customerPhone() ?: 'No phone' }} &middot; Completed {{ $order->updated_at->format('H:i') }}</small>
                                    <div class="order-control-items">
                                        @foreach($order->items as $item)
                                            <span class="line-chip">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="status completed">Completed</span>
                                <a class="secondary-button" href="{{ route('orders.docket', $order) }}" target="_blank" rel="noopener">Docket</a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="empty">No completed orders found{{ $search !== '' ? ' for "'.$search.'"' : '' }}.</div>
            @endforelse
        </div>
    </section>
@endsection
