<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\Menu;
use App\Models\Branch;
use App\Models\OperationalCost;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Sales Report
     */
    public function sales(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = Order::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ])->where('status', 'completed');

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $totalSales = $query->sum('total_amount');
        $totalOrders = $query->count();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // Sales by day
        $salesByDay = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top selling items
        $topSellingItems = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])->where('status', 'completed');
            if ($request->branch_id) {
                $q->where('branch_id', $request->branch_id);
            }
        })
        ->select('menu_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(subtotal) as total_revenue'))
        ->with('menu')
        ->groupBy('menu_id')
        ->orderByDesc('total_quantity')
        ->limit(10)
        ->get();

        // Sales by category
        $salesByCategory = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])->where('status', 'completed');
            if ($request->branch_id) {
                $q->where('branch_id', $request->branch_id);
            }
        })
        ->join('menus', 'order_items.menu_id', '=', 'menus.id')
        ->join('menu_categories', 'menus.menu_category_id', '=', 'menu_categories.id')
        ->select(
            'menu_categories.name as category',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.subtotal) as total_revenue')
        )
        ->groupBy('menu_categories.id', 'menu_categories.name')
        ->get();

        return response()->json([
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'average_order_value' => round($averageOrderValue, 2),
            ],
            'sales_by_day' => $salesByDay,
            'top_selling_items' => $topSellingItems,
            'sales_by_category' => $salesByCategory,
        ]);
    }

    /**
     * Profit Report (Sales - HPP)
     */
    public function profit(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $orderQuery = Order::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ])->where('status', 'completed');

        if ($request->branch_id) {
            $orderQuery->where('branch_id', $request->branch_id);
        }

        $totalRevenue = $orderQuery->sum('total_amount');

        // Calculate total COGS (Cost of Goods Sold)
        $orderItems = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])->where('status', 'completed');
            if ($request->branch_id) {
                $q->where('branch_id', $request->branch_id);
            }
        })->with('menu')->get();

        $totalCOGS = $orderItems->sum(function ($item) {
            return $item->quantity * ($item->menu->cost ?? 0);
        });

        // Get operational costs
        $costQuery = OperationalCost::whereBetween('cost_date', [
            $request->start_date,
            $request->end_date
        ]);

        if ($request->branch_id) {
            $costQuery->where('branch_id', $request->branch_id);
        }

        $operationalCosts = $costQuery->sum('amount');

        $grossProfit = $totalRevenue - $totalCOGS;
        $netProfit = $grossProfit - $operationalCosts;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Profit by item
        $profitByItem = $orderItems->groupBy('menu_id')->map(function ($items) {
            $menu = $items->first()->menu;
            $totalQty = $items->sum('quantity');
            $totalRevenue = $items->sum('subtotal');
            $totalCost = $totalQty * ($menu->cost ?? 0);
            $profit = $totalRevenue - $totalCost;

            return [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'quantity_sold' => $totalQty,
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'profit' => $profit,
                'profit_margin' => $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0,
            ];
        })->values()->sortByDesc('profit')->take(10);

        return response()->json([
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_cogs' => round($totalCOGS, 2),
                'gross_profit' => round($grossProfit, 2),
                'operational_costs' => round($operationalCosts, 2),
                'net_profit' => round($netProfit, 2),
                'profit_margin' => round($profitMargin, 2),
            ],
            'top_profit_items' => $profitByItem,
        ]);
    }

    /**
     * Inventory Report
     */
    public function inventory(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = Stock::with(['rawMaterial', 'branch']);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $stocks = $query->get();

        $totalValue = $stocks->sum(function ($stock) {
            return $stock->quantity * ($stock->rawMaterial->unit_price ?? 0);
        });

        $stocksByBranch = $stocks->groupBy('branch_id')->map(function ($branchStocks) {
            $branch = $branchStocks->first()->branch;
            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'total_items' => $branchStocks->count(),
                'total_value' => $branchStocks->sum(function ($stock) {
                    return $stock->quantity * ($stock->rawMaterial->unit_price ?? 0);
                }),
            ];
        })->values();

        return response()->json([
            'summary' => [
                'total_items' => $stocks->count(),
                'total_value' => round($totalValue, 2),
            ],
            'stocks' => $stocks,
            'stocks_by_branch' => $stocksByBranch,
        ]);
    }

    /**
     * Low Stock Alert
     */
    public function lowStock(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = Stock::with(['rawMaterial', 'branch'])
            ->whereHas('rawMaterial', function ($q) {
                $q->whereColumn('stocks.quantity', '<=', 'raw_materials.min_stock');
            });

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $lowStocks = $query->get()->map(function ($stock) {
            return [
                'raw_material' => $stock->rawMaterial->name,
                'branch' => $stock->branch->name,
                'current_quantity' => $stock->quantity,
                'min_stock' => $stock->rawMaterial->min_stock,
                'unit' => $stock->rawMaterial->unit,
                'deficit' => $stock->rawMaterial->min_stock - $stock->quantity,
            ];
        });

        return response()->json([
            'total_low_stock_items' => $lowStocks->count(),
            'items' => $lowStocks,
        ]);
    }

    /**
     * Financial Report (Cash Flow)
     */
    public function financial(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Cash In (Sales)
        $salesQuery = Order::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ])->where('status', 'completed');

        if ($request->branch_id) {
            $salesQuery->where('branch_id', $request->branch_id);
        }

        $totalCashIn = $salesQuery->sum('total_amount');

        // Cash Out (Operational Costs)
        $costQuery = OperationalCost::whereBetween('cost_date', [
            $request->start_date,
            $request->end_date
        ]);

        if ($request->branch_id) {
            $costQuery->where('branch_id', $request->branch_id);
        }

        $operationalCosts = $costQuery->get();
        $totalCashOut = $operationalCosts->sum('amount');

        // Breakdown by category
        $costsByCategory = $operationalCosts->groupBy('category')->map(function ($costs, $category) {
            return [
                'category' => $category,
                'total' => $costs->sum('amount'),
            ];
        })->values();

        $netCashFlow = $totalCashIn - $totalCashOut;

        return response()->json([
            'summary' => [
                'cash_in' => round($totalCashIn, 2),
                'cash_out' => round($totalCashOut, 2),
                'net_cash_flow' => round($netCashFlow, 2),
            ],
            'costs_by_category' => $costsByCategory,
        ]);
    }

    /**
     * Export Sales Report to PDF
     */
    public function salesPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Get report data (reuse sales method logic)
        $query = Order::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ])->where('status', 'completed');

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $totalSales = $query->sum('total_amount');
        $totalOrders = $query->count();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        $salesByDay = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSellingItems = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])->where('status', 'completed');
            if ($request->branch_id) {
                $q->where('branch_id', $request->branch_id);
            }
        })
        ->select('menu_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(subtotal) as total_revenue'))
        ->with('menu')
        ->groupBy('menu_id')
        ->orderByDesc('total_quantity')
        ->limit(10)
        ->get();

        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $data = [
            'title' => 'Sales Report',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch' => $branch,
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'average_order_value' => round($averageOrderValue, 2),
            'sales_by_day' => $salesByDay,
            'top_selling_items' => $topSellingItems,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('reports.sales', $data);
        return $pdf->download('sales-report-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Profit Report to PDF
     */
    public function profitPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $orderQuery = Order::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ])->where('status', 'completed');

        if ($request->branch_id) {
            $orderQuery->where('branch_id', $request->branch_id);
        }

        $totalRevenue = $orderQuery->sum('total_amount');

        $orderItems = OrderItem::whereHas('order', function ($q) use ($request) {
            $q->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])->where('status', 'completed');
            if ($request->branch_id) {
                $q->where('branch_id', $request->branch_id);
            }
        })->with('menu')->get();

        $totalCOGS = $orderItems->sum(function ($item) {
            return $item->quantity * ($item->menu->cost ?? 0);
        });

        $costQuery = OperationalCost::whereBetween('cost_date', [
            $request->start_date,
            $request->end_date
        ]);

        if ($request->branch_id) {
            $costQuery->where('branch_id', $request->branch_id);
        }

        $operationalCosts = $costQuery->sum('amount');
        $grossProfit = $totalRevenue - $totalCOGS;
        $netProfit = $grossProfit - $operationalCosts;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $profitByItem = $orderItems->groupBy('menu_id')->map(function ($items) {
            $menu = $items->first()->menu;
            $totalQty = $items->sum('quantity');
            $totalRevenue = $items->sum('subtotal');
            $totalCost = $totalQty * ($menu->cost ?? 0);
            $profit = $totalRevenue - $totalCost;

            return [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'quantity_sold' => $totalQty,
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'profit' => $profit,
                'profit_margin' => $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0,
            ];
        })->values()->sortByDesc('profit')->take(10);

        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $data = [
            'title' => 'Profit Report',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'branch' => $branch,
            'total_revenue' => round($totalRevenue, 2),
            'total_cogs' => round($totalCOGS, 2),
            'gross_profit' => round($grossProfit, 2),
            'operational_costs' => round($operationalCosts, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => round($profitMargin, 2),
            'top_profit_items' => $profitByItem,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $pdf = Pdf::loadView('reports.profit', $data);
        return $pdf->download('profit-report-' . date('Y-m-d') . '.pdf');
    }
}
