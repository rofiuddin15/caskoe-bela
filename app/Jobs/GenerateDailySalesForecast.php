<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\AiService;
use App\Services\AiNotificationService;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class GenerateDailySalesForecast implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AiService $aiService, AiNotificationService $notificationService): void
    {
        Log::info('Starting daily sales forecast generation...');

        // Get all active branches
        $branches = Branch::where('is_active', true)->get();

        foreach ($branches as $branch) {
            try {
                Log::info("Generating forecast for branch: {$branch->name} (ID: {$branch->id})");

                // Generate 7-day forecast with force refresh
                $forecast = $aiService->generateSalesForecast($branch->id, 7, true);

                if ($forecast['success']) {
                    Log::info("Forecast generated successfully for branch {$branch->id}");

                    // Check for critical alerts
                    $alerts = $notificationService->checkCriticalAlerts($branch, $forecast);

                    if (!empty($alerts)) {
                        Log::warning("Critical alerts found for branch {$branch->id}", [
                            'alerts' => $alerts,
                        ]);
                    }

                    // Send notification to manager
                    $notificationService->sendForecastNotification($branch, $forecast);
                } else {
                    Log::error("Failed to generate forecast for branch {$branch->id}: {$forecast['error']}");
                }

                // Sleep 2 seconds between branches to avoid rate limiting
                sleep(2);
            } catch (\Exception $e) {
                Log::error("Error generating forecast for branch {$branch->id}: " . $e->getMessage());
            }
        }

        Log::info('Daily sales forecast generation completed.');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Daily sales forecast job failed: ' . $exception->getMessage());
    }
}
