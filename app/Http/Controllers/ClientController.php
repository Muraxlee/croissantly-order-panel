<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index()
    {
        $this->authorizeClient();

        return redirect()->route('client.place');
    }

    public function place()
    {
        $this->authorizeClient();

        return view('client.place', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function orders()
    {
        $this->authorizeClient();

        return view('client.orders', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'orders' => Order::with('items')
                ->where('client_id', auth()->id())
                ->latest()
                ->get(),
        ]);
    }

    public function storeOrder(Request $request)
    {
        $this->authorizeClient();

        $data = $request->validate([
            'required_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->map(fn ($quantity, $productId) => ['product_id' => (int) $productId, 'quantity' => (int) $quantity])
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one item to the order.']);
        }

        DB::transaction(function () use ($data, $items) {
            $order = Order::create([
                'order_number' => 'CR'.now()->format('ymdHis').random_int(10, 99),
                'client_id' => auth()->id(),
                'required_at' => $data['required_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            $this->syncItems($order, $items->all());

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'note' => 'Client placed order.',
            ]);
        });

        return back()->with('status', 'Order sent for approval.');
    }

    public function updateOrder(Request $request, Order $order)
    {
        $this->authorizeClient();
        abort_unless($order->client_id === auth()->id(), 403);

        if (! $order->canClientEdit()) {
            return back()->withErrors(['order' => 'Kitchen has started this order. It can no longer be changed.']);
        }

        $data = $request->validate([
            'required_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->map(fn ($quantity, $productId) => ['product_id' => (int) $productId, 'quantity' => (int) $quantity])
            ->values();

        $existingItems = $order->items()->get()->keyBy('product_id');

        foreach ($existingItems as $productId => $existingItem) {
            $requestedQuantity = (int) ($data['items'][$productId] ?? 0);

            if ($requestedQuantity < $existingItem->quantity) {
                return back()->withErrors(['items' => 'You can add more items, but you cannot remove or reduce existing items.']);
            }
        }

        DB::transaction(function () use ($order, $data, $items) {
            $order->update([
                'required_at' => $data['required_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $order->status === 'cancelled' ? 'pending' : $order->status,
            ]);

            $this->syncItems($order, $items->all());

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'status' => $order->status,
                'note' => 'Client updated quantities.',
            ]);
        });

        return back()->with('status', 'Order updated.');
    }

    private function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();
        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $lineTotal = (float) $product->price * $item['quantity'];
            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'cooking_instructions' => null,
                'packing_instructions' => null,
            ]);
        }

        $order->update(['total' => $total]);
    }

    private function authorizeClient(): void
    {
        abort_unless(auth()->user()?->isClient(), 403);
    }
}
