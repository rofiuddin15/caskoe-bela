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
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    /**
     * Daftar semua order
     *
     * Menampilkan daftar order dengan filter berdasarkan cabang, status, tipe, dan tanggal.
     *
     * @authenticated
     * @queryParam branch_id integer Filter berdasarkan ID cabang. Example: 1
     * @queryParam status string Filter berdasarkan status (pending, preparing, ready, served, completed, cancelled). Example: pending
     * @queryParam order_type string Filter berdasarkan tipe (dine_in, takeaway, delivery). Example: dine_in
     * @queryParam date string Filter berdasarkan tanggal (format: Y-m-d). Example: 2025-01-15
     * @queryParam per_page integer Jumlah data per halaman. Default: 15. Example: 10
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
     * Buat order baru (POS)
     *
     * Membuat order baru untuk dine-in, takeaway, atau delivery. Order number dibuat otomatis.
     * Mendukung WebSocket real-time untuk notifikasi dapur.
     *
     * @authenticated
     * @bodyParam branch_id integer required ID cabang. Example: 1
     * @bodyParam table_id integer ID meja (untuk dine-in). Example: 5
     * @bodyParam order_type string required Tipe order (dine_in, takeaway, delivery). Example: dine_in
     * @bodyParam customer_name string Nama customer. Example: John Doe
     * @bodyParam customer_phone string Nomor telepon customer. Example: 08123456789
     * @bodyParam delivery_address string Alamat pengiriman (untuk delivery). Example: Jl. Sudirman No. 10
     * @bodyParam notes string Catatan order. Example: Less sugar
     * @bodyParam items array required Array item yang dipesan (minimal 1).
     * @bodyParam items.*.menu_id integer required ID menu. Example: 1
     * @bodyParam items.*.quantity integer required Jumlah. Example: 2
     * @bodyParam items.*.notes string Catatan item. Example: Extra hot
     *
     * @response 201 {
     *   "id": 1,
     *   "order_number": "ORD-20250115-0001",
     *   "status": "pending",
     *   "subtotal": 50000,
     *   "tax_amount": 5000,
     *   "service_charge": 2500,
     *   "total_amount": 57500
     * }
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

            return new OrderResource($order->load(['items.menu', 'table']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail order
     *
     * Menampilkan detail order lengkap termasuk item, meja, cabang, kasir, dan pembayaran.
     *
     * @authenticated
     * @urlParam order integer required ID order. Example: 1
     */
    public function show(Order $order)
    {
        $order->load(['items.menu', 'table', 'branch', 'cashier', 'payments']);
        return new OrderResource($order);
    }

    /**
     * Update informasi order
     *
     * Memperbarui informasi customer dan catatan order.
     *
     * @authenticated
     * @urlParam order integer required ID order. Example: 1
     * @bodyParam notes string Catatan order. Example: Customer request
     * @bodyParam customer_name string Nama customer. Example: Jane Doe
     * @bodyParam customer_phone string Nomor telepon customer. Example: 08123456789
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        $order->update($validated);

        return new OrderResource($order);
    }

    /**
     * Hapus order
     *
     * Menghapus order dengan status pending. Order dengan status lain tidak dapat dihapus.
     *
     * @authenticated
     * @urlParam order integer required ID order. Example: 1
     *
     * @response 200 {
     *   "message": "Order deleted successfully"
     * }
     * @response 400 {
     *   "message": "Cannot delete order that is not pending"
     * }
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
     * Tambah item ke order
     *
     * Menambahkan item baru ke order yang sudah ada. Hanya bisa dilakukan pada order dengan status pending atau preparing.
     * Total order akan dihitung ulang otomatis.
     *
     * @authenticated
     * @urlParam id integer required ID order. Example: 1
     * @bodyParam menu_id integer required ID menu. Example: 2
     * @bodyParam quantity integer required Jumlah. Example: 1
     * @bodyParam notes string Catatan item. Example: No ice
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
     * Update status order
     *
     * Mengubah status order (pending → preparing → ready → served → completed).
     * Mendukung WebSocket real-time untuk update ke dapur dan kasir.
     *
     * @authenticated
     * @urlParam id integer required ID order. Example: 1
     * @bodyParam status string required Status baru (pending, preparing, ready, served, completed, cancelled). Example: preparing
     *
     * @response 200 {
     *   "id": 1,
     *   "order_number": "ORD-20250115-0001",
     *   "status": "preparing",
     *   "completed_at": null
     * }
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
     * Proses pembayaran order
     *
     * Memproses pembayaran untuk order. Mendukung multiple metode pembayaran (cash, QRIS, kartu).
     * Setelah pembayaran sukses, status order otomatis menjadi completed.
     *
     * @authenticated
     * @urlParam id integer required ID order. Example: 1
     * @bodyParam payment_method string required Metode pembayaran (cash, qris, debit_card, credit_card, transfer). Example: cash
     * @bodyParam amount numeric required Jumlah pembayaran. Example: 60000
     * @bodyParam cash_received numeric Uang diterima (untuk metode cash). Example: 100000
     * @bodyParam reference_number string Nomor referensi (untuk non-cash). Example: TRX123456
     *
     * @response 200 {
     *   "message": "Payment processed successfully",
     *   "order": {...},
     *   "change": 42500
     * }
     * @response 400 {
     *   "message": "Payment amount is less than total"
     * }
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
