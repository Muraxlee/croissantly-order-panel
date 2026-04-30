@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Admin orders</p>
            <h1>Order board</h1>
        </div>
        <button class="primary-button" type="button" data-open-modal="create-order-modal">Create order</button>
    </header>

    <dialog class="order-modal" id="create-order-modal" @if($errors->any()) data-auto-open-modal @endif>
        <form method="post" action="{{ route('admin.orders.store') }}" class="modal-shell order-form">
            @csrf
            <div class="modal-head">
                <div>
                    <p class="eyebrow">Admin orders</p>
                    <h2>Create order</h2>
                    <small>Select a customer account to place an order on their behalf, or leave it as walk-in for one-off orders.</small>
                </div>
                <button class="icon-button" type="button" data-close-modal aria-label="Close modal">&times;</button>
            </div>

            <div class="form-grid">
                <label class="wide"><span>Customer account</span>
                    <select name="client_id">
                        <option value="">Walk-in / phone customer</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                {{ $client->name }}{{ $client->clientProfile?->business_name ? ' - '.$client->clientProfile->business_name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <small>When a customer is selected, this order appears in their client dashboard.</small>
                </label>
                <label><span>Customer name</span><input name="customer_name" value="{{ old('customer_name') }}" placeholder="Required for walk-in only"></label>
                <label><span>Phone number</span><input name="customer_phone" value="{{ old('customer_phone') }}"></label>
                <label class="wide"><span>Address</span><input name="customer_address" value="{{ old('customer_address') }}"></label>
                <label><span>Needed by</span><input type="datetime-local" name="required_at" value="{{ old('required_at') }}"></label>
                <label><span>Notes</span><input name="notes" value="{{ old('notes') }}"></label>
            </div>
            <div class="menu-grid">
                @foreach($products as $product)
                    <label class="product-pick">
                        <span>
                            <strong>{{ $product->name }}</strong>
                            <small>{{ $product->description }}</small>
                            <em>&pound;{{ number_format($product->price, 2) }}</em>
                        </span>
                        <input type="number" name="items[{{ $product->id }}]" min="0" value="0">
                    </label>
                @endforeach
            </div>
            <button class="primary-button">Create order</button>
        </form>
    </dialog>

    <section class="panel">
        <div class="section-head">
            <div>
                <h2>Orders</h2>
                <p>Approve orders, watch cooking locks, and keep the day visible.</p>
            </div>
            <div class="section-actions">
                <form method="get" action="{{ route('admin.orders.index') }}" class="search-filter">
                    @if($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <label><span>Search orders</span><input name="q" value="{{ $search }}" placeholder="Order, customer, phone, item"></label>
                    <button class="secondary-button">Search</button>
                    @if($search !== '')
                        <a class="secondary-button" href="{{ route('admin.orders.index', $status ? ['status' => $status] : []) }}">Clear</a>
                    @endif
                </form>
                <div class="segmented">
                    @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'cooking' => 'Cooking', 'packed' => 'Packed', 'completed' => 'Done', 'cancelled' => 'Rejected'] as $key => $label)
                        <a @class(['selected' => $status === $key || ($key === '' && !$status)]) href="{{ route('admin.orders.index', array_filter(['status' => $key ?: null, 'q' => $search ?: null])) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Client</th>
                        <th>Items</th>
                        <th>Required</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php($orderedItems = $order->items->map(fn ($item) => $item->quantity.'x '.$item->product_name)->join(', '))
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong><small>{{ $order->notes }}</small></td>
                            <td>
                                <strong>{{ $order->customerName() }}</strong>
                                <small>{{ $order->customerPhone() ?: 'No phone' }}</small>
                                @if($order->customer_address)
                                    <small>{{ $order->customer_address }}</small>
                                @endif
                            </td>
                            <td>
                                @foreach($order->items as $item)
                                    <span class="line-chip">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $order->required_at?->format('d M, H:i') ?? 'Flexible' }}</td>
                            <td><span class="status {{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                            <td>&pound;{{ number_format($order->total, 2) }}</td>
                            <td>
                                @if($order->status === 'pending')
                                    <div class="action-row">
                                        <a class="secondary-button" href="{{ route('orders.docket', $order) }}" target="_blank" rel="noopener">Docket</a>
                                        <form method="post" action="{{ route('admin.orders.approve', $order) }}" data-confirm="Approve {{ $order->order_number }} for {{ $order->customerName() }}? Products: {{ $orderedItems }}">
                                            @csrf
                                            <button class="small-button">Approve</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.orders.reject', $order) }}" data-confirm="Reject {{ $order->order_number }} for {{ $order->customerName() }}? Products: {{ $orderedItems }}">
                                            @csrf
                                            <button class="danger-button">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="action-row">
                                        <a class="secondary-button" href="{{ route('orders.docket', $order) }}" target="_blank" rel="noopener">Docket</a>
                                        <span class="muted">{{ $order->locked_at ? 'Locked' : 'Editable' }}</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No orders found{{ $search !== '' ? ' for "'.$search.'"' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
