<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiService;
use App\Models\Branch;

class GenerateForecastCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:forecast
                            {--branch= : ID cabang spesifik}
                            {--days=7 : Jumlah hari forecast}
                            {--all : Generate untuk semua cabang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI sales forecast untuk cabang';

    /**
     * Execute the console command.
     */
    public function handle(AiService $aiService)
    {
        $branchId = $this->option('branch');
        $days = (int) $this->option('days');
        $all = $this->option('all');

        if ($all) {
            $this->info('Generating forecast untuk semua cabang...');
            $branches = Branch::where('is_active', true)->get();

            $this->withProgressBar($branches, function ($branch) use ($aiService, $days) {
                $forecast = $aiService->generateSalesForecast($branch->id, $days, true);

                if (!$forecast['success']) {
                    $this->newLine();
                    $this->error("Failed for branch {$branch->id}: {$forecast['error']}");
                }
            });

            $this->newLine(2);
            $this->info('Forecast generation completed!');
            return Command::SUCCESS;
        }

        if ($branchId) {
            $branch = Branch::find($branchId);

            if (!$branch) {
                $this->error("Branch dengan ID {$branchId} tidak ditemukan!");
                return Command::FAILURE;
            }

            $this->info("Generating forecast untuk {$branch->name}...");

            $forecast = $aiService->generateSalesForecast($branchId, $days, true);

            if ($forecast['success']) {
                $this->info("Forecast berhasil di-generate!");
                $this->line("Predicted Revenue: Rp " . number_format($forecast['historical_data']['total_sales']));
                $this->line("Total Transactions: " . $forecast['historical_data']['total_transactions']);
                return Command::SUCCESS;
            } else {
                $this->error("Failed: {$forecast['error']}");
                return Command::FAILURE;
            }
        }

        $this->error('Harap berikan --branch=ID atau --all');
        return Command::FAILURE;
    }
}
