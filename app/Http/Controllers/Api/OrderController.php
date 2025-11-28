<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Tax;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.menu', 'table', 'branch', 'cashier', 'payments']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'table_id' => 'nullable|exists:tables,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'branch_id' => $validated['branch_id'],
                'table_id' => $validated['table_id'] ?? null,
                'cashier_id' => auth()->id(),
                'order_type' => $validated['order_type'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            // Add order items
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $menu = \App\Models\Menu::find($item['menu_id']);
                $itemSubtotal = $menu->price * $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menu->price,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                $subtotal += $itemSubtotal;
            }

            // Calculate tax and service charge
            $taxes = Tax::where('is_active', true)->get();
            $taxAmount = 0;
            $serviceCharge = 0;

            foreach ($taxes as $tax) {
                if ($tax->type === 'tax') {
                    $taxAmount += ($subtotal * $tax->rate / 100);
                } elseif ($tax->type === 'service_charge') {
                    $serviceCharge += ($subtotal * $tax->rate / 100);
                }
            }

            $total = $subtotal + $taxAmount + $serviceCharge;

            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'service_charge' => $serviceCharge,
                'total_amount' => $total,
            ]);

            // Update table status if dine-in
            if ($validated['order_type'] === 'dine_in' && $validated['table_id']) {
                \App\Models\Table::find($validated['table_id'])->update(['status' => 'occupied']);
            }

            DB::commit();

            // Broadcast order created event
            broadcast(new OrderCreated($order))->toOthers();

            return response()->json($order->load(['items.menu', 'table']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['items.menu', 'table', 'branch', 'cashier', 'payments']);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete order that is not pending'
            ], 400);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Add item to existing order
     */
    public function addItem(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!in_array($order->status, ['pending', 'preparing'])) {
            return response()->json([
                'message' => 'Cannot add items to order with current status'
            ], 400);
        }

        $validated = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $menu = \App\Models\Menu::find($validated['menu_id']);
        $itemSubtotal = $menu->price * $validated['quantity'];

        OrderItem::create([
            'order_id' => $order->id,
            'menu_id' => $validated['menu_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $menu->price,
            'subtotal' => $itemSubtotal,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Recalculate order totals
        $this->recalculateOrderTotals($order);

        return response()->json($order->load(['items.menu']));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,completed,cancelled',
        ]);

        $oldStatus = $order->status;
        $order->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        // Update table status if order is completed
        if ($validated['status'] === 'completed' && $order->table_id) {
            $order->table->update(['status' => 'available']);
        }

        // Broadcast status update
        broadcast(new OrderStatusUpdated($order, $oldStatus, $validated['status']))->toOthers();

        return response()->json($order);
    }

    /**
     * Process payment for order
     */
    public function processPayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,qris,debit_card,credit_card,transfer',
            'amount' => 'required|numeric|min:0',
            'cash_received' => 'nullable|numeric',
            'reference_number' => 'nullable|string',
        ]);

        // Validate payment amount
        if ($validated['amount'] < $order->total_amount) {
            return response()->json([
                'message' => 'Payment amount is less than total'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(Payment::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $change = 0;
            if ($validated['payment_method'] === 'cash' && isset($validated['cash_received'])) {
                $change = $validated['cash_received'] - $order->total_amount;
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_number' => $paymentNumber,
                'payment_method' => $validated['payment_method'],
                'amount' => $order->total_amount,
                'cash_received' => $validated['cash_received'] ?? null,
                'change' => $change,
                'reference_number' => $validated['reference_number'] ?? null,
                'processed_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            $order->update(['status' => 'completed', 'completed_at' => now()]);

            // Update table status
            if ($order->table_id) {
                $order->table->update(['status' => 'available']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment processed successfully',
                'order' => $order->load('payments'),
                'change' => $change,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate order totals
     */
    private function recalculateOrderTotals(Order $order)
    {
        $subtotal = $order->items()->sum('subtotal');

        $taxes = Tax::where('is_active', true)->get();
        $taxAmount = 0;
        $serviceCharge = 0;

        foreach ($taxes as $tax) {
            if ($tax->type === 'tax') {
                $taxAmount += ($subtotal * $tax->rate / 100);
            } elseif ($tax->type === 'service_charge') {
                $serviceCharge += ($subtotal * $tax->rate / 100);
            }
        }

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'service_charge' => $serviceCharge,
            'total_amount' => $subtotal + $taxAmount + $serviceCharge,
        ]);
    }
}
