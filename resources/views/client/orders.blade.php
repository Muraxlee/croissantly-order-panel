@extends('layouts.app')

@section('content')
    @php
        $clientStatusLabels = [
            'pending' => 'Waiting approval',
            'approved' => 'Yet to start prepare',
            'cooking' => 'Started preparing',
            'packed' => 'Ready for delivery',
            'completed' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    @endphp

    <header class="page-header">
        <div>
            <p class="eyebrow">Client orders</p>
            <h1>Your orders</h1>
        </div>
    </header>

    <section class="panel">
        <div class="section-head"><div><h2>Order history</h2><p>Use Edit to view the full order and add more menu items before preparation starts.</p></div></div>
        <div class="client-order-list">
            @forelse($orders as $order)
                <article class="client-order-card">
                    <div>
                        <strong>{{ $order->order_number }}</strong>
                        <small>{{ $order->items->sum('quantity') }} items &middot; {{ $order->required_at?->format('d M, H:i') ?? 'Flexible time' }}</small>
                    </div>
                    <span class="status {{ $order->status }}">{{ $clientStatusLabels[$order->status] ?? ucfirst($order->status) }}</span>
                    <button class="secondary-button" type="button" data-open-modal="order-modal-{{ $order->id }}">Edit</button>
                </article>

                <dialog class="order-modal" id="order-modal-{{ $order->id }}">
                    <div class="modal-shell">
                        <div class="modal-head">
                            <div>
                                <p class="eyebrow">Your order</p>
                                <h2>{{ $order->order_number }}</h2>
                                <small>{{ $clientStatusLabels[$order->status] ?? ucfirst($order->status) }}</small>
                            </div>
                            <button class="icon-button" type="button" data-close-modal aria-label="Close">x</button>
                        </div>

                        <form method="post" action="{{ route('client.orders.update', $order) }}" class="order-form">
                            @csrf
                            @method('put')
                            <div class="menu-grid">
                                @foreach($products as $product)
                                    @php($existing = $order->items->firstWhere('product_id', $product->id))
                                    <label class="product-pick">
                                        <span>
                                            <strong>{{ $product->name }}</strong>
                                            <small>{{ $existing ? 'Current minimum: '.$existing->quantity : 'Add new item' }}</small>
                                            <em>&pound;{{ number_format($product->price, 2) }}</em>
                                        </span>
                                        <input type="number" name="items[{{ $product->id }}]" min="{{ $existing?->quantity ?? 0 }}" value="{{ $existing?->quantity ?? 0 }}" @disabled(! $order->canClientEdit())>
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-grid compact">
                                <label><span>Needed by</span><input type="datetime-local" name="required_at" value="{{ $order->required_at?->format('Y-m-d\TH:i') }}" @disabled(! $order->canClientEdit())></label>
                                <label class="wide"><span>Notes</span><input name="notes" value="{{ $order->notes }}" @disabled(! $order->canClientEdit())></label>
                                @if($order->canClientEdit())
                                    <button class="primary-button">Add to order</button>
                                @else
                                    <span class="locked-note">Preparation has started. This order is locked.</span>
                                @endif
                            </div>
                        </form>
                    </div>
                </dialog>
            @empty
                <div class="empty">No orders yet.</div>
            @endforelse
        </div>
    </section>
@endsection
