<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'waste']); // Movement types
            $table->decimal('quantity', 15, 2); // Can be negative for 'out'
            $table->decimal('balance_after', 15, 2); // Stock balance after this movement
            $table->string('reference_type')->nullable(); // e.g., PurchaseOrder, Order, StockTransfer
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the reference
            $table->foreignId('from_branch_id')->nullable()->constrained('branches'); // For transfers
            $table->foreignId('to_branch_id')->nullable()->constrained('branches'); // For transfers
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
