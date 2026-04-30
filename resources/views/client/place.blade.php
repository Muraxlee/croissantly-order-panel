@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Client ordering</p>
            <h1>Place order</h1>
        </div>
    </header>

    <section class="panel">
        <div class="section-head"><div><h2>New order</h2><p>You can add more items until the kitchen starts preparing.</p></div></div>
        <form method="post" action="{{ route('client.orders.store') }}" class="order-form">
            @csrf
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
            <div class="form-grid compact">
                <label><span>Needed by</span><input type="datetime-local" name="required_at"></label>
                <label class="wide"><span>Notes</span><input name="notes" placeholder="Packing, pickup, delivery notes"></label>
                <button class="primary-button">Send order</button>
            </div>
        </form>
    </section>
@endsection
