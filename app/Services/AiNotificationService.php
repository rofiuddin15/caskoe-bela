<?php

namespace App\Services;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AiNotificationService
{
    /**
     * Send forecast notification to branch manager
     */
    public function sendForecastNotification(Branch $branch, array $forecast): void
    {
        try {
            // Get branch manager
            $manager = User::whereHas('roles', function ($query) {
                $query->where('name', 'manajer_cabang');
            })
            ->where('branch_id', $branch->id)
            ->first();

            if (!$manager) {
                Log::warning("No manager found for branch {$branch->id}");
                return;
            }

            // Extract key insights
            $insights = $this->extractKeyInsights($forecast);

            // Send notification (customize based on your notification channels)
            Log::info("Forecast notification sent to {$manager->email} for branch {$branch->name}", [
                'insights' => $insights,
            ]);

            // TODO: Implement actual notification
            // $manager->notify(new ForecastGeneratedNotification($branch, $insights));

        } catch (\Exception $e) {
            Log::error("Failed to send forecast notification: " . $e->getMessage());
        }
    }

    /**
     * Check for critical alerts in forecast
     */
    public function checkCriticalAlerts(Branch $branch, array $forecast): array
    {
        $alerts = [];

        if (!isset($forecast['forecast']['inventory_recommendations'])) {
            return $alerts;
        }

        $inventoryRecs = $forecast['forecast']['inventory_recommendations'];

        // High priority inventory alerts
        if (isset($inventoryRecs['high_priority']) && count($inventoryRecs['high_priority']) > 0) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'Stok Kritis!',
                'message' => 'Ada ' . count($inventoryRecs['high_priority']) . ' bahan baku yang perlu segera di-restock.',
                'items' => $inventoryRecs['high_priority'],
                'action_required' => true,
            ];
        }

        // Growth opportunity
        if (isset($forecast['forecast']['forecast_summary']['growth_percentage'])) {
            $growth = $forecast['forecast']['forecast_summary']['growth_percentage'];

            if (strpos($growth, '+') !== false) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Peluang Pertumbuhan',
                    'message' => "Prediksi pertumbuhan: {$growth}",
                    'action_required' => false,
                ];
            } elseif (strpos($growth, '-') !== false) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Penurunan Prediksi',
                    'message' => "Prediksi penurunan: {$growth}. Perlu strategi boost penjualan.",
                    'action_required' => true,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Extract key insights from forecast
     */
    private function extractKeyInsights(array $forecast): array
    {
        $insights = [];

        if (isset($forecast['forecast']['forecast_summary'])) {
            $summary = $forecast['forecast']['forecast_summary'];
            $insights['predicted_revenue'] = $summary['predicted_revenue'] ?? 'N/A';
            $insights['predicted_transactions'] = $summary['predicted_transactions'] ?? 'N/A';
            $insights['growth'] = $summary['growth_percentage'] ?? 'N/A';
            $insights['confidence'] = $summary['confidence_level'] ?? 'N/A';
        }

        if (isset($forecast['forecast']['demand_insights']['trending_products'])) {
            $insights['trending_products'] = array_slice(
                $forecast['forecast']['demand_insights']['trending_products'],
                0,
                3
            );
        }

        if (isset($forecast['forecast']['action_items'])) {
            $insights['action_items'] = array_slice(
                $forecast['forecast']['action_items'],
                0,
                3
            );
        }

        return $insights;
    }

    /**
     * Send low stock alert
     */
    public function sendLowStockAlert(Branch $branch, array $lowStockItems): void
    {
        if (empty($lowStockItems)) {
            return;
        }

        Log::warning("Low stock alert for branch {$branch->name}", [
            'items' => $lowStockItems,
        ]);

        // TODO: Send notification to inventory manager
    }

    /**
     * Send anomaly detection alert
     */
    public function sendAnomalyAlert(Branch $branch, string $anomalyType, array $details): void
    {
        Log::warning("Anomaly detected for branch {$branch->name}: {$anomalyType}", [
            'details' => $details,
        ]);

        // TODO: Send immediate notification to manager
    }
}
