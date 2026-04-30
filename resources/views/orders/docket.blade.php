@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
    @php
        $clientProfile = $order->client?->clientProfile;
        $businessName = $clientProfile?->business_name ?: $order->customerName();
        $phone = $order->customerPhone() ?: 'Not provided';
        $email = $order->client?->email ?: 'Not provided';
    @endphp

    <section class="delivery-docket">
        <div class="docket-actions no-print">
            <a class="secondary-button" href="{{ url()->previous() }}">Back</a>
            <button class="primary-button" type="button" data-docket-print data-docket-title="{{ $order->order_number }}">Print docket</button>
        </div>

        <div class="docket-sheet">
            <header class="docket-brand-head">
                <div class="docket-brand">
                    <div>
                        <strong class="docket-wordmark">Croissantly</strong>
                        <span class="docket-brand-kicker">bakery</span>
                        <small>105 Patrick St, Dún Laoghaire, DUN LAOGHAIRE, Co. Dublin, A96 RX31, Ireland</small>
                    </div>
                </div>
                <div class="docket-brand-meta">
                    <span>+353 89 438 2027</span>
                    <span>croissantlybakery.ie</span>
                </div>
            </header>

            <section class="docket-title-row">
                <div>
                    <span>Order number</span>
                    <h1>{{ $order->order_number }}</h1>
                </div>
                <div class="docket-hand-fields">
                    <label><span>Order date</span><strong>{{ $order->created_at?->format('d M Y, H:i') }}</strong></label>
                    <label><span>Delivery date</span><i></i></label>
                </div>
            </section>

            <section class="docket-customer-card">
                <dl>
                    <div>
                        <dt>Customer name</dt>
                        <dd>{{ $order->customerName() }}</dd>
                    </div>
                    <div>
                        <dt>Business name</dt>
                        <dd>{{ $businessName }}</dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ $phone }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $email }}</dd>
                    </div>
                </dl>
            </section>

            <section class="docket-items">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td><strong>{{ $item->quantity }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td><strong>{{ $order->items->sum('quantity') }} pcs</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </section>

            <footer class="docket-footer">
                <span>Checked by:</span>
                <span>Delivered by:</span>
                <span>Received by:</span>
            </footer>
        </div>
    </section>

    <script>
        (() => {
            const fileName = @json($order->order_number);
            document.title = fileName;

            const printButton = document.querySelector('[data-docket-print]');
            printButton?.addEventListener('click', () => {
                document.title = printButton.dataset.docketTitle || fileName;
                setTimeout(() => window.print(), 50);
            });

            window.addEventListener('beforeprint', () => {
                document.title = fileName;
            });
        })();
    </script>
@endsection
