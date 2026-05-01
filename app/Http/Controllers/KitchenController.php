<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
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

    public function completed(Request $request)
    {
        $this->authorizeKitchen();

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($data['q'] ?? ''));

        $orders = Order::with(['client.clientProfile', 'items'])
            ->where('status', 'completed')
            ->when(! empty($data['from']), fn ($query) => $query->whereDate('updated_at', '>=', $data['from']))
            ->when(! empty($data['to']), fn ($query) => $query->whereDate('updated_at', '<=', $data['to']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_address', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhereHas('clientProfile', function ($query) use ($search) {
                                    $query->where('business_name', 'like', "%{$search}%")
                                        ->orWhere('contact_name', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('items', fn ($query) => $query->where('product_name', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->get();

        return view('dashboards.kitchen-completed', [
            'orders' => $orders,
            'ordersByDate' => $orders->groupBy(fn ($order) => $order->updated_at->toDateString()),
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'search' => $search,
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

        if ($order->status === 'cooking') {
            $order->update(['status' => 'packed']);
            OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'status' => 'packed', 'note' => 'Kitchen packed the order.']);
        }

        return back()->with('status', 'Order marked packed.');
    }

    public function complete(Order $order)
    {
        $this->authorizeKitchen();

        if ($order->status === 'packed') {
            $order->update(['status' => 'completed']);
            OrderStatusHistory::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'status' => 'completed', 'note' => 'Order completed.']);
        }

        return back()->with('status', 'Order completed.');
    }

    private function authorizeKitchen(): void
    {
        abort_unless(auth()->user()?->isKitchen(), 403);
    }
}
