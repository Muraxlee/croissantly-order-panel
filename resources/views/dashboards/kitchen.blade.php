@extends('layouts.app')

@section('content')
    @php
        $productionOrders = $orders->whereIn('status', ['approved', 'cooking']);
        $productGroups = $productionOrders
            ->flatMap(fn ($order) => $order->items->map(fn ($item) => [
                'item' => $item,
                'order' => $order,
                'client' => $order->client,
                'name' => $order->customerName(),
                'phone' => $order->customerPhone(),
            ]))
            ->groupBy(fn ($row) => $row['item']->product_name);
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Kitchen production</p>
            <h1>Make by product</h1>
        </div>
        @if($productionOrders->contains(fn ($order) => $order->status === 'approved'))
            <form method="post" action="{{ route('kitchen.orders.start-all') }}">
                @csrf
                <button class="primary-button">Start Cooking</button>
            </form>
        @endif
        <a class="secondary-button" href="{{ route('kitchen.orders.completed') }}">Completed orders</a>
    </header>

    <section class="kitchen-production">
        @forelse($productGroups as $productName => $rows)
            <article class="product-ticket">
                <div class="product-ticket-head">
                    <div>
                        <span>Product</span>
                        <h2>{{ $productName }}</h2>
                    </div>
                    <strong>{{ $rows->sum(fn ($row) => $row['item']->quantity) }}</strong>
                </div>

                <div class="client-quantity-list">
                    @foreach($rows->groupBy(fn ($row) => $row['order']->id) as $clientRows)
                        @php
                            $firstRow = $clientRows->first();
                            $name = $firstRow['name'];
                            $phone = $firstRow['phone'] ?: 'No phone';
                            $quantity = $clientRows->sum(fn ($row) => $row['item']->quantity);
                        @endphp
                        <div class="client-quantity-row">
                            <div>
                                <strong>{{ $name }}</strong>
                                <small>{{ $phone }}</small>
                            </div>
                            <div>
                                <span>{{ $quantity }}</span>
                                <small>ordered</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="panel empty">No approved or cooking orders waiting for kitchen.</div>
        @endforelse
    </section>

    @if($orders->isNotEmpty())
        <section class="panel order-control-panel">
            <div class="section-head">
                <div>
                    <h2>Order controls</h2>
                    <p>Approve pending orders before production. Start Cooking begins current approved orders.</p>
                </div>
            </div>

            <div class="order-control-list">
                @foreach($orders as $order)
                    @php($orderedItems = $order->items->map(fn ($item) => $item->quantity.'x '.$item->product_name)->join(', '))
                    <div class="order-control-row">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <small>{{ $order->customerName() }} &middot; {{ $order->customerPhone() ?: 'No phone' }} &middot; {{ $order->required_at?->format('d M, H:i') ?? 'Flexible' }}</small>
                            <div class="order-control-items">
                                @foreach($order->items as $item)
                                    <span class="line-chip">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                @endforeach
                            </div>
                        </div>
                        <span class="status {{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        <div class="action-row">
                            <a class="secondary-button" href="{{ route('orders.docket', $order) }}" target="_blank" rel="noopener">Docket</a>
                            @if($order->status === 'pending')
                                <form method="post" action="{{ route('kitchen.orders.approve', $order) }}" data-confirm="Approve {{ $order->order_number }} for {{ $order->customerName() }}? Products: {{ $orderedItems }}">
                                    @csrf
                                    <button class="secondary-button">Approve</button>
                                </form>
                                <form method="post" action="{{ route('kitchen.orders.reject', $order) }}" data-confirm="Reject {{ $order->order_number }} for {{ $order->customerName() }}? Products: {{ $orderedItems }}">
                                    @csrf
                                    <button class="danger-button">Reject</button>
                                </form>
                            @endif
                            @if($order->status === 'cooking')
                                <form method="post" action="{{ route('kitchen.orders.packed', $order) }}">
                                    @csrf
                                    <button class="secondary-button">Packed</button>
                                </form>
                            @endif
                            @if($order->status === 'packed')
                                <form method="post" action="{{ route('kitchen.orders.complete', $order) }}">
                                    @csrf
                                    <button class="secondary-button">Complete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
