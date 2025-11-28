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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_number')->unique();
            $table->enum('payment_method', ['cash', 'qris', 'debit_card', 'credit_card', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->decimal('change', 15, 2)->nullable();
            $table->string('reference_number')->nullable(); // For digital payments
            $table->foreignId('processed_by')->constrained('users');
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
