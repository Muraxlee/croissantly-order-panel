<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderDocketController extends Controller
{
    public function __invoke(Order $order)
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isKitchen(), 403);

        return view('orders.docket', [
            'order' => $order->load(['client.clientProfile', 'items']),
        ]);
    }
}
