<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiService;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate sales forecast dan demand prediction
     *
     * Menggunakan AI untuk memprediksi penjualan dan demand produk berdasarkan data historis.
     * Memberikan insights tentang trending products, peak hours, dan rekomendasi inventory.
     *
     * @authenticated
     * @queryParam branch_id integer required ID cabang untuk forecast. Example: 1
     * @queryParam days integer Jumlah hari untuk forecast (default 7). Example: 7
     * @queryParam force_refresh boolean Force refresh cache (default false). Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "branch_id": 1,
     *   "forecast_days": 7,
     *   "from_cache": false,
     *   "forecast": {
     *     "forecast_summary": {...},
     *     "daily_predictions": [...],
     *     "demand_insights": {...},
     *     "inventory_recommendations": {...}
     *   }
     * }
     */
    public function salesForecast(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'days' => 'nullable|integer|min:1|max:30',
            'force_refresh' => 'nullable|boolean',
        ]);

        $branchId = $validated['branch_id'];
        $days = $validated['days'] ?? 7;
        $forceRefresh = $validated['force_refresh'] ?? false;

        $forecast = $this->aiService->generateSalesForecast($branchId, $days, $forceRefresh);

        return response()->json($forecast);
    }

    /**
     * Generate menu recommendations untuk customer
     *
     * Menggunakan AI untuk memberikan rekomendasi menu yang personal berdasarkan:
     * - Riwayat order customer
     * - Isi keranjang saat ini
     * - Menu populer di cabang
     * - Upselling dan cross-selling opportunities
     *
     * @authenticated
     * @bodyParam branch_id integer required ID cabang. Example: 1
     * @bodyParam order_history array Riwayat order customer (optional). Example: [{"menu_id": 1, "menu_name": "Cappuccino"}]
     * @bodyParam current_cart array Isi keranjang saat ini (optional). Example: [{"menu_id": 2, "menu_name": "Latte", "quantity": 1}]
     *
     * @response 200 {
     *   "success": true,
     *   "branch_id": 1,
     *   "recommendations": {
     *     "primary_recommendations": [...],
     *     "combo_deals": [...],
     *     "personalized_message": "...",
     *     "trending_now": [...]
     *   }
     * }
     */
    public function menuRecommendations(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'order_history' => 'nullable|array',
            'current_cart' => 'nullable|array',
        ]);

        $branchId = $validated['branch_id'];
        $orderHistory = $validated['order_history'] ?? [];
        $currentCart = $validated['current_cart'] ?? [];

        $recommendations = $this->aiService->generateMenuRecommendations(
            $branchId,
            $orderHistory,
            $currentCart
        );

        return response()->json($recommendations);
    }

    /**
     * Get AI-powered business insights
     *
     * Analisis komprehensif menggunakan AI untuk business intelligence:
     * - Performance analysis
     * - Anomaly detection
     * - Strategic recommendations
     *
     * @authenticated
     * @queryParam branch_id integer required ID cabang. Example: 1
     * @queryParam period string Periode analisis (week/month/quarter). Example: month
     *
     * @response 200 {
     *   "success": true,
     *   "insights": {
     *     "performance": {...},
     *     "anomalies": [...],
     *     "recommendations": [...]
     *   }
     * }
     */
    public function businessInsights(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'period' => 'nullable|in:week,month,quarter',
        ]);

        // Placeholder for future implementation
        // Could include: anomaly detection, performance analysis, strategic recommendations

        return response()->json([
            'success' => true,
            'message' => 'Business insights feature coming soon',
            'branch_id' => $validated['branch_id'],
        ]);
    }

    /**
     * Chatbot assistant untuk cafe
     *
     * AI chatbot untuk menjawab pertanyaan seputar:
     * - Menu dan harga
     * - Ketersediaan stok
     * - Rekomendasi produk
     * - Informasi operasional
     *
     * @authenticated
     * @bodyParam question string required Pertanyaan customer. Example: "Apa menu kopi yang paling enak?"
     * @bodyParam branch_id integer required ID cabang. Example: 1
     * @bodyParam context array Additional context (optional). Example: {"previous_questions": [...]}
     *
     * @response 200 {
     *   "success": true,
     *   "answer": "Berdasarkan data kami...",
     *   "related_menus": [...],
     *   "follow_up_suggestions": [...]
     * }
     */
    public function chatbot(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'branch_id' => 'required|exists:branches,id',
            'context' => 'nullable|array',
        ]);

        // Placeholder for chatbot implementation
        // Could use OpenAI with RAG (Retrieval-Augmented Generation)

        return response()->json([
            'success' => true,
            'message' => 'Chatbot feature coming soon',
            'question' => $validated['question'],
        ]);
    }
}
