<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    public function index()
    {
        $this->authorizeKitchen();

        return view('dashboards.kitchen', [
            'orders' => Order::with(['client.clientProfile', 'items'])
                ->whereIn('status', ['pending', 'approved', 'cooking', 'packed'])
                ->oldest('required_at')
                ->latest()
                ->get(),
        ]);
    }

    public function approve(Order $order)
    {
        $this->authorizeKitchen();

        if ($order->status === 'pending') {
            $order->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'approved',
                'note' => 'Kitchen approved the order.',
            ]);
        }

        return back()->with('status', 'Order approved.');
    }

    public function reject(Order $order)
    {
        $this->authorizeKitchen();

        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'cancelled',
                'note' => 'Kitchen rejected the order.',
            ]);
        }

        return back()->with('status', 'Order rejected.');
    }

    public function start(Order $order)
    {
        $this->authorizeKitchen();

        if (in_array($order->status, ['approved', 'pending'], true)) {
            $order->update([
                'status' => 'cooking',
                'locked_at' => now(),
                'cooking_started_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'cooking',
                'note' => 'Kitchen started cooking. Client edits locked.',
            ]);
        }

        return back()->with('status', 'Order locked and cooking started.');
    }

    public function startAll()
    {
        $this->authorizeKitchen();

        $orders = Order::where('status', 'approved')->get();

        DB::transaction(function () use ($orders) {
            foreach ($orders as $order) {
                $order->update([
                    'status' => 'cooking',
                    'locked_at' => now(),
                    'cooking_started_at' => now(),
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'status' => 'cooking',
                    'note' => 'Kitchen started all current approved orders. Client edits locked.',
                ]);
            }
        });

        return back()->with('status', $orders->count().' current orders started.');
    }

    public function markPacked(Order $order)
    {
        $this->authorizeKitchen();
        $order->update(['status' => 'packed']);
        OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'status' => 'packed', 'note' => 'Kitchen packed the order.']);

        return back()->with('status', 'Order marked packed.');
    }

    public function complete(Order $order)
    {
        $this->authorizeKitchen();
        $order->update(['status' => 'completed']);
        OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'status' => 'completed', 'note' => 'Order completed.']);

        return back()->with('status', 'Order completed.');
    }

    private function authorizeKitchen(): void
    {
        abort_unless(auth()->user()?->isKitchen(), 403);
    }
}
