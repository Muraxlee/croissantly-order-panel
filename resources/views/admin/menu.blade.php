@extends('layouts.app')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Admin menu</p>
            <h1>Menu items</h1>
        </div>
    </header>

    <section class="panel page-section">
        <div class="section-head"><div><h2>Add product</h2><p>Add the item clients can order.</p></div></div>
        <form method="post" action="{{ route('admin.products.store') }}" class="form-grid">
            @csrf
            <label><span>Product</span><input name="name" required></label>
            <label><span>Price</span><input type="number" step="0.01" name="price" required></label>
            <label class="wide"><span>Description</span><input name="description"></label>
            <button class="primary-button">Add product</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-head">
            <div><h2>Current menu</h2><p>Update price/details or remove products that are no longer sold.</p></div>
            <form method="get" action="{{ route('admin.menu.index') }}" class="search-filter">
                <label><span>Search menu</span><input name="q" value="{{ $search }}" placeholder="Product, description, price"></label>
                <button class="secondary-button">Search</button>
                @if($search !== '')
                    <a class="secondary-button" href="{{ route('admin.menu.index') }}">Clear</a>
                @endif
            </form>
        </div>
        <div class="menu-editor-list">
            @forelse($products as $product)
                <form method="post" action="{{ route('admin.products.update', $product) }}" class="menu-editor-row">
                    @csrf
                    @method('put')
                    <label><span>Product</span><input name="name" value="{{ $product->name }}" required></label>
                    <label><span>Price</span><input type="number" step="0.01" name="price" value="{{ $product->price }}" required></label>
                    <label><span>Description</span><input name="description" value="{{ $product->description }}"></label>
                    <button class="secondary-button">Update</button>
                    <button class="danger-button" form="delete-product-{{ $product->id }}">Remove</button>
                </form>
                <form id="delete-product-{{ $product->id }}" method="post" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Remove {{ $product->name }} from the menu?">
                    @csrf
                    @method('delete')
                </form>
            @empty
                <div class="empty">No products found{{ $search !== '' ? ' for "'.$search.'"' : '' }}.</div>
            @endforelse
        </div>
    </section>
@endsection
