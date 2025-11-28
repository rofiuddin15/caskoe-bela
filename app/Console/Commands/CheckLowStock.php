<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\LowStockAlert;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock items and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for low stock items...');

        $lowStocks = Stock::with(['rawMaterial', 'branch'])
            ->whereHas('rawMaterial', function ($q) {
                $q->whereColumn('stocks.quantity', '<=', 'raw_materials.min_stock');
            })
            ->get();

        if ($lowStocks->isEmpty()) {
            $this->info('No low stock items found.');
            return 0;
        }

        $this->warn('Found ' . $lowStocks->count() . ' low stock items.');

        // Format low stock data
        $lowStockData = $lowStocks->map(function ($stock) {
            return [
                'raw_material' => $stock->rawMaterial->name,
                'branch' => $stock->branch->name,
                'current_quantity' => $stock->quantity,
                'min_stock' => $stock->rawMaterial->min_stock,
                'unit' => $stock->rawMaterial->unit,
                'deficit' => $stock->rawMaterial->min_stock - $stock->quantity,
            ];
        })->toArray();

        // Get users with owner or admin_pusat roles
        $admins = User::role(['owner', 'admin_pusat'])->get();

        foreach ($admins as $admin) {
            $admin->notify(new LowStockAlert($lowStockData));
            $this->info('Notification sent to: ' . $admin->email);
        }

        $this->info('Low stock check completed!');
        return 0;
    }
}
