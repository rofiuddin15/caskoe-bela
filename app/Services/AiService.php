<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AiService
{
    /**
     * Generate sales forecast and demand prediction
     *
     * @param int $branchId
     * @param int $days - number of days to forecast
     * @param bool $forceRefresh - force refresh cache
     * @return array
     */
    public function generateSalesForecast(int $branchId, int $days = 7, bool $forceRefresh = false): array
    {
        $cacheKey = "sales_forecast_{$branchId}_{$days}";

        // Check cache first (cache for 6 hours)
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['from_cache'] = true;
            return $cached;
        }

        // Get historical sales data
        $salesData = $this->getHistoricalSalesData($branchId, 30);
        $topProducts = $this->getTopSellingProducts($branchId, 30);

        // Prepare prompt for OpenAI
        $prompt = $this->buildForecastPrompt($salesData, $topProducts, $days);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah AI analyst yang ahli dalam sales forecasting untuk bisnis cafe. Berikan analisis dalam bahasa Indonesia dengan format JSON yang terstruktur.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);

            $forecast = [
                'success' => true,
                'branch_id' => $branchId,
                'forecast_days' => $days,
                'generated_at' => now()->toISOString(),
                'forecast' => $result,
                'historical_data' => [
                    'total_sales' => $salesData['total_sales'],
                    'total_transactions' => $salesData['total_transactions'],
                    'average_transaction' => $salesData['average_transaction'],
                ],
                'from_cache' => false,
            ];

            // Cache for 6 hours
            Cache::put($cacheKey, $forecast, now()->addHours(6));

            return $forecast;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate smart menu recommendations for a customer
     *
     * @param int $branchId
     * @param array $orderHistory - previous orders (optional)
     * @param array $currentCart - items in current cart (optional)
     * @param bool $useCache - use cache if available
     * @return array
     */
    public function generateMenuRecommendations(int $branchId, array $orderHistory = [], array $currentCart = [], bool $useCache = true): array
    {
        // Only cache if no personalization (no history/cart)
        $canCache = empty($orderHistory) && empty($currentCart);
        $cacheKey = "menu_recommendations_{$branchId}";

        if ($canCache && $useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['from_cache'] = true;
            return $cached;
        }

        $availableMenus = Menu::where('is_available', true)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'category' => $menu->category->name ?? 'Uncategorized',
                    'price' => $menu->price,
                    'description' => $menu->description,
                ];
            })
            ->toArray();

        $popularMenus = $this->getPopularMenus($branchId);

        $prompt = $this->buildRecommendationPrompt($availableMenus, $popularMenus, $orderHistory, $currentCart);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah AI assistant untuk cafe yang ahli dalam memberikan rekomendasi menu. Berikan rekomendasi yang personal, relevan, dan menarik dalam bahasa Indonesia. Gunakan format JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.8,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);

            $recommendations = [
                'success' => true,
                'branch_id' => $branchId,
                'generated_at' => now()->toISOString(),
                'recommendations' => $result,
                'from_cache' => false,
            ];

            // Cache general recommendations (without personalization) for 2 hours
            if ($canCache) {
                Cache::put($cacheKey, $recommendations, now()->addHours(2));
            }

            return $recommendations;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get historical sales data
     */
    private function getHistoricalSalesData(int $branchId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);

        $orders = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get();

        $dailySales = Order::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'total_sales' => $orders->sum('total_amount'),
            'total_transactions' => $orders->count(),
            'average_transaction' => $orders->count() > 0 ? $orders->avg('total_amount') : 0,
            'daily_breakdown' => $dailySales,
        ];
    }

    /**
     * Get top selling products
     */
    private function getTopSellingProducts(int $branchId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days);

        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menus', 'order_items.menu_id', '=', 'menus.id')
            ->where('orders.branch_id', $branchId)
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', $startDate)
            ->select(
                'menus.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('menus.id', 'menus.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get popular menus at branch
     */
    private function getPopularMenus(int $branchId): array
    {
        return $this->getTopSellingProducts($branchId, 7);
    }

    /**
     * Build forecast prompt
     */
    private function buildForecastPrompt(array $salesData, array $topProducts, int $days): string
    {
        $dailyData = json_encode($salesData['daily_breakdown'], JSON_PRETTY_PRINT);
        $topProductsData = json_encode($topProducts, JSON_PRETTY_PRINT);

        return <<<PROMPT
Berdasarkan data historis penjualan cafe selama 30 hari terakhir berikut:

**Summary Penjualan:**
- Total Revenue: Rp {$salesData['total_sales']}
- Total Transaksi: {$salesData['total_transactions']}
- Average per Transaksi: Rp {$salesData['average_transaction']}

**Data Harian:**
{$dailyData}

**Top 10 Produk Terlaris:**
{$topProductsData}

Tolong buatkan sales forecast untuk {$days} hari kedepan dengan format JSON berikut:

{
  "forecast_summary": {
    "predicted_revenue": "estimasi total revenue",
    "predicted_transactions": "estimasi jumlah transaksi",
    "growth_percentage": "persentase pertumbuhan vs periode sebelumnya",
    "confidence_level": "tingkat kepercayaan prediksi (high/medium/low)"
  },
  "daily_predictions": [
    {
      "date": "YYYY-MM-DD",
      "predicted_revenue": 0,
      "predicted_transactions": 0,
      "day_type": "weekday/weekend",
      "notes": "catatan khusus untuk hari ini"
    }
  ],
  "demand_insights": {
    "trending_products": ["list produk yang diprediksi naik"],
    "declining_products": ["list produk yang diprediksi turun"],
    "peak_hours": "jam-jam tersibuk",
    "recommendations": ["rekomendasi strategi penjualan"]
  },
  "inventory_recommendations": {
    "high_priority": ["bahan baku yang perlu di-restock segera"],
    "medium_priority": ["bahan baku yang perlu dimonitor"],
    "low_priority": ["bahan baku yang masih aman"]
  },
  "action_items": ["list action items konkret untuk owner/manager"]
}

Berikan analisis yang detail, realistis, dan actionable dalam bahasa Indonesia.
PROMPT;
    }

    /**
     * Build recommendation prompt
     */
    private function buildRecommendationPrompt(array $menus, array $popularMenus, array $orderHistory, array $currentCart): string
    {
        $menusData = json_encode($menus, JSON_PRETTY_PRINT);
        $popularData = json_encode($popularMenus, JSON_PRETTY_PRINT);
        $historyData = !empty($orderHistory) ? json_encode($orderHistory, JSON_PRETTY_PRINT) : 'Tidak ada riwayat';
        $cartData = !empty($currentCart) ? json_encode($currentCart, JSON_PRETTY_PRINT) : 'Keranjang kosong';

        return <<<PROMPT
Sebagai AI assistant cafe, berikan rekomendasi menu yang personal dan menarik.

**Menu yang Tersedia:**
{$menusData}

**Menu Populer:**
{$popularData}

**Riwayat Order Customer:**
{$historyData}

**Isi Keranjang Saat Ini:**
{$cartData}

Buatkan rekomendasi dengan format JSON berikut:

{
  "primary_recommendations": [
    {
      "menu_id": 0,
      "menu_name": "nama menu",
      "reason": "alasan rekomendasi yang personal dan menarik",
      "upsell_value": "nilai upsell (rupiah)",
      "confidence": "high/medium/low"
    }
  ],
  "combo_deals": [
    {
      "name": "nama combo",
      "menu_ids": [1, 2, 3],
      "total_price": 0,
      "savings": "berapa hemat",
      "description": "deskripsi combo yang menarik"
    }
  ],
  "personalized_message": "pesan personal untuk customer berdasarkan histori dan keranjangnya",
  "trending_now": ["menu yang sedang trending"],
  "special_offers": "penawaran spesial yang relevan (jika ada)"
}

Berikan rekomendasi yang:
1. Personal berdasarkan history customer
2. Complementary dengan isi keranjang
3. Mengutamakan upselling dan cross-selling
4. Menarik dan persuasif dalam bahasa Indonesia
PROMPT;
    }
}
